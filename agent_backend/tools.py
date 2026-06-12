import datetime
import json
import os
import re
import requests
import joblib
from transformers import pipeline

# Build the path to the ml_models folder (one level up from this file)
_MODEL_DIR = os.path.join(os.path.dirname(__file__), '..', 'ml_models')

# Load the models once when the file is imported — not inside every function call.
# The meme detector is still the trained sklearn model (joblib).
meme_model = joblib.load(os.path.join(_MODEL_DIR, 'meme_detector_model.pkl'))

# Sentiment now uses a fine-tuned 5-star BERT model loaded via transformers.
# The model lives in ml_models/sentiment_model/ as a directory (config + weights
# + tokenizer); its labels are "1 star".."5 stars". device=-1 forces CPU.
_SENTIMENT_DIR = os.path.join(_MODEL_DIR, 'sentiment_model')
sentiment_analyzer = pipeline(
    "sentiment-analysis",
    model=_SENTIMENT_DIR,
    tokenizer=_SENTIMENT_DIR,
    device=-1,
)

from langchain_core.tools import tool  # @tool turns a normal function into an AI-usable tool


def _sentiment_scores(text: str):
    """Collapse the 5-star model's output into (positive_pct, negative_pct).

    Adapted from NEW PYTHON CODE/RunSentimentTransformerModel.py: each star band
    is weighted (1*->0, 2*->25, ... 5*->100) and combined into an expected value,
    giving a 0-100 'how positive' score. negative is just the complement."""
    raw = sentiment_analyzer(text, top_k=None)  # list of {label, score} for all 5 stars
    star_probs = {1: 0.0, 2: 0.0, 3: 0.0, 4: 0.0, 5: 0.0}
    for result in raw:
        star_num = int(result["label"].split()[0])  # "4 stars" -> 4
        star_probs[star_num] = result["score"]

    positive_pct = round(
        (star_probs[1] * 0) + (star_probs[2] * 25) + (star_probs[3] * 50)
        + (star_probs[4] * 75) + (star_probs[5] * 100), 1)
    return positive_pct, round(100.0 - positive_pct, 1)


def _clean(text: str) -> str:
    """Replace ALL control characters (incl. tabs/newlines/CR) with a space and
    trim review text to 300 chars. Control characters survive in review text and
    corrupt the JSON when the LLM relays it between tools, causing 'Invalid
    control character' errors — so we strip them entirely, then collapse the
    leftover whitespace."""
    text = re.sub(r'[\x00-\x1f\x7f]', ' ', text)
    text = re.sub(r'\s+', ' ', text)
    return text[:300].strip()


@tool
def get_steam_reviews(game_name: str) -> str:
    """Search the Steam store for a game and fetch up to 50 recent English reviews."""

    # Step 1: search Steam for the game name to get its numeric app ID
    search_url = "https://store.steampowered.com/api/storesearch/"
    search_params = {"term": game_name, "l": "english", "cc": "US"}
    search_resp = requests.get(search_url, params=search_params, timeout=10)
    search_resp.raise_for_status()  # crashes loudly if Steam returns an error status
    search_data = search_resp.json()

    items = search_data.get("items", [])
    if not items:
        # Return an error dict as a JSON string — the agent will read this
        return json.dumps({"error": f"No Steam game found for '{game_name}'"})

    # Take the top search result
    app_id = items[0]["id"]
    found_name = items[0]["name"]

    # Step 2: fetch actual reviews using that app ID
    reviews_url = f"https://store.steampowered.com/appreviews/{app_id}"
    reviews_params = {
        "json": 1,
        "language": "english",
        "review_type": "all",       # include both positive and negative
        "purchase_type": "all",
        "num_per_page": 50,
        "filter": "recent",         # most recent first
    }
    reviews_resp = requests.get(reviews_url, params=reviews_params, timeout=10)
    reviews_resp.raise_for_status()
    reviews_data = reviews_resp.json()

    # Pull out just the text of each review, cleaned and trimmed to 300 chars
    review_texts = [
        _clean(r["review"]) for r in reviews_data.get("reviews", []) if r.get("review")
    ]

    # LangChain tools must return strings, so we convert the dict to a JSON string
    return json.dumps({
        "game_name": found_name,
        "app_id": app_id,
        "reviews": review_texts,
    })


# Maps the frontend's priority/concern chips (and common synonyms) to regexes
# that detect when a review actually talks about that topic.
_TOPIC_KEYWORDS = {
    "performance": r"\bfps\b|stutter|lag|frame ?rate|frame ?drop|optimi[sz]|performance|low.?end|high.?end",
    "optimization": r"\bfps\b|stutter|lag|frame ?rate|optimi[sz]|performance",
    "bugs": r"\bbug|glitch|broken|janky?\b",
    "crashes": r"crash|freeze|\bctd\b|black screen|blue ?screen",
    "story": r"story|plot|writing|narrative|characters?|dialogue|ending",
    "multiplayer": r"multiplayer|co.?op|online|pvp|server",
    "population": r"player ?base|population|dead game|matchmaking|queue",
    "balance": r"balance|overpowered|\bop\b|nerf|broken (class|build|weapon)",
    "repetitive": r"repetitive|grind|boring|stale|same thing",
    "community": r"community|toxic|griefing|cheat|hacker|trolls?|chat\b",
    "endgame": r"end ?game|post.?game|late game|replay",
    "content": r"content|hours of|short game|length|amount of",
    "value": r"price|worth|value|expensive|refund|sale",
    "quality": r"polish|quality|masterpiece|unfinished|rushed",
}


def _relevance_pattern(focus_topics: str):
    """Build one regex matching any of the user's topics; None if no topics given."""
    if not focus_topics.strip():
        return None
    patterns = []
    lowered = focus_topics.lower()
    for key, pattern in _TOPIC_KEYWORDS.items():
        if key in lowered:
            patterns.append(pattern)
    # Also match any literal word the user gave that we don't have a mapping for
    for word in re.findall(r"[a-z]{4,}", lowered):
        if not any(word in k for k in _TOPIC_KEYWORDS):
            patterns.append(re.escape(word))
    return re.compile("|".join(patterns), re.IGNORECASE) if patterns else None


@tool
def classify_reviews(reviews_json: str, focus_topics: str = "") -> str:
    """Run sentiment and meme-detection models on Steam reviews.

    Input: reviews_json — JSON string from get_steam_reviews.
           focus_topics — optional comma-separated topics the user cares about
           (e.g. "Performance, Bugs"); matching reviews are preferred in the output.
    Output: JSON with counts and up to 8 genuine review details, preferring
            reviews relevant to focus_topics (marked with matches_your_topics).
    """
    # Parse the JSON string back into a Python dict.
    # strict=False tolerates raw control characters (newlines/tabs) that can
    # survive in Steam review text when the LLM relays this JSON between tools —
    # otherwise json.loads raises "Invalid control character" and the run 500s.
    data = json.loads(reviews_json, strict=False)

    if "error" in data:
        return reviews_json  # pass the error straight through

    reviews = data.get("reviews", [])
    if not reviews:
        return json.dumps({"error": "No reviews to classify."})

    total = len(reviews)
    genuine_count = 0
    positive_count = 0
    negative_count = 0
    all_genuine = []  # every genuine review with metadata; we pick the top 8 after

    relevance = _relevance_pattern(focus_topics)

    for text in reviews:
        # meme_model returns 1 if the review is a joke/meme, 0 if genuine
        is_meme = bool(meme_model.predict([text])[0])

        # 5-star transformer -> positive/negative percentages
        positive_pct, negative_pct = _sentiment_scores(text)

        # Collapse to the same binary signal the agent already expects:
        # >=50% positive leaning counts as a positive review.
        is_positive = positive_pct >= 50.0

        # confidence = the winning side's strength, as a 0-1 fraction
        confidence = (positive_pct if is_positive else negative_pct) / 100.0

        if not is_meme:
            genuine_count += 1
            if is_positive:
                positive_count += 1
            else:
                negative_count += 1

            all_genuine.append({
                "snippet": text[:200],  # first 200 chars as a preview
                "sentiment": "positive" if is_positive else "negative",
                "confidence_pct": round(confidence * 100, 1),
                "is_genuine": True,
                "matches_your_topics": bool(relevance and relevance.search(text)),
            })

    # Pick up to 8 snippets to show the agent: topic-relevant reviews first
    # (so the answer can quote reviewers talking about what the user asked),
    # then fill the rest in original (most recent) order.
    relevant = [r for r in all_genuine if r["matches_your_topics"]]
    others = [r for r in all_genuine if not r["matches_your_topics"]]
    top_reviews = (relevant + others)[:8]

    return json.dumps({
        "total_count": total,
        "genuine_count": genuine_count,
        "positive_count": positive_count,
        "negative_count": negative_count,
        "relevant_to_user_topics_count": len(relevant),
        "top_genuine_reviews": top_reviews,
    })


@tool
def get_recent_patches(game_name: str) -> str:
    """Fetch the 5 most recent Steam patch/update news items for a game."""

    # Same search step as get_steam_reviews — find the app ID first
    search_url = "https://store.steampowered.com/api/storesearch/"
    search_params = {"term": game_name, "l": "english", "cc": "US"}
    search_resp = requests.get(search_url, params=search_params, timeout=10)
    search_resp.raise_for_status()
    search_data = search_resp.json()

    items = search_data.get("items", [])
    if not items:
        return json.dumps({"error": f"No Steam game found for '{game_name}'"})

    app_id = items[0]["id"]
    found_name = items[0]["name"]

    # Steam News API — filtered to the "steam_updates" feed (official patch notes only)
    news_url = "https://api.steampowered.com/ISteamNews/GetNewsForApp/v2/"
    news_params = {
        "appid": app_id,
        "count": 10,
        "maxlength": 300,   # limit each article to 300 characters
        "format": "json",
        "feeds": "steam_updates",
    }
    news_resp = requests.get(news_url, params=news_params, timeout=10)
    news_resp.raise_for_status()
    news_data = news_resp.json()

    # Take only the 5 most recent patch entries
    news_items = news_data.get("appnews", {}).get("newsitems", [])[:5]

    patches = []
    for item in news_items:
        # The date comes back as a Unix timestamp (seconds since 1970), convert to readable date
        ts = item.get("date", 0)
        date_str = datetime.datetime.fromtimestamp(ts).strftime("%Y-%m-%d") if ts else "unknown"
        patches.append({
            "title": item.get("title", ""),
            "date": date_str,
            "content_preview": item.get("contents", "")[:300],
        })

    return json.dumps({
        "game_name": found_name,
        "patches": patches,
    })
