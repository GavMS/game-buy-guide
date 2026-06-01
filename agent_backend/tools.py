import datetime
import json
import os
import re
import requests
import joblib

# Build the path to the ml_models folder (one level up from this file)
_MODEL_DIR = os.path.join(os.path.dirname(__file__), '..', 'ml_models')

# Load both models once when the file is imported — not inside every function call
# joblib.load reads a saved sklearn model from disk
sentiment_model = joblib.load(os.path.join(_MODEL_DIR, 'sentiment_model.pkl'))
meme_model = joblib.load(os.path.join(_MODEL_DIR, 'meme_detector_model.pkl'))

from langchain_core.tools import tool  # @tool turns a normal function into an AI-usable tool


def _clean(text: str) -> str:
    """Remove control characters and trim review text to 300 chars.
    Control characters (invisible special chars) break JSON parsing."""
    text = re.sub(r'[\x00-\x08\x0b\x0c\x0e-\x1f\x7f]', '', text)
    return text[:300].strip()


@tool
def get_steam_reviews(game_name: str) -> str:
    """Search the Steam store for a game and fetch 15-20 recent English reviews."""

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
        "num_per_page": 20,
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


@tool
def classify_reviews(reviews_json: str) -> str:
    """Run sentiment and meme-detection models on Steam reviews.

    Input: JSON string from get_steam_reviews.
    Output: JSON with counts and top 8 genuine review details.
    """
    # Parse the JSON string back into a Python dict
    data = json.loads(reviews_json)

    if "error" in data:
        return reviews_json  # pass the error straight through

    reviews = data.get("reviews", [])
    if not reviews:
        return json.dumps({"error": "No reviews to classify."})

    total = len(reviews)
    genuine_count = 0
    positive_count = 0
    negative_count = 0
    genuine_reviews = []  # we'll collect the top 8 genuine ones here

    for text in reviews:
        # meme_model returns 1 if the review is a joke/meme, 0 if genuine
        is_meme = bool(meme_model.predict([text])[0])

        # sentiment_model returns 1 for positive, -1 for negative
        sentiment_label = int(sentiment_model.predict([text])[0])

        # predict_proba gives confidence scores for each class (e.g. [0.2, 0.8])
        sentiment_proba = sentiment_model.predict_proba([text])[0]

        # Find which index in the probability array corresponds to the positive class
        classes = list(sentiment_model.classes_)
        pos_idx = classes.index(1) if 1 in classes else -1
        confidence = float(sentiment_proba[pos_idx]) if pos_idx >= 0 else 0.5

        if sentiment_label == -1:
            # For negative reviews, show the confidence of the negative class instead
            neg_idx = classes.index(-1) if -1 in classes else -1
            confidence = float(sentiment_proba[neg_idx]) if neg_idx >= 0 else 0.5

        is_positive = sentiment_label == 1

        if not is_meme:
            genuine_count += 1
            if is_positive:
                positive_count += 1
            else:
                negative_count += 1

            # Save details for up to 8 genuine reviews to show the agent
            if len(genuine_reviews) < 8:
                genuine_reviews.append({
                    "snippet": text[:200],  # first 200 chars as a preview
                    "sentiment": "positive" if is_positive else "negative",
                    "confidence_pct": round(confidence * 100, 1),
                    "is_genuine": True,
                })

    return json.dumps({
        "total_count": total,
        "genuine_count": genuine_count,
        "positive_count": positive_count,
        "negative_count": negative_count,
        "top_genuine_reviews": genuine_reviews,
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
