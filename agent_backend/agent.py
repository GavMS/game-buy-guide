import json
import os
from dotenv import load_dotenv

# Read the MISTRAL_API_KEY from the .env file in the project root
load_dotenv(os.path.join(os.path.dirname(__file__), '.env'))

from langchain_mistralai import ChatMistralAI
from langchain_core.messages import HumanMessage   # represents a message from the user
from langgraph.prebuilt import create_react_agent  # replaces the old create_tool_calling_agent
from tools import get_steam_reviews, classify_reviews, get_recent_patches

# Instructions given to the AI at the start. {priorities_block} is filled at runtime.
_SYSTEM_PROMPT_TEMPLATE = """You are a game purchasing advisor. You answer ONE question: "Should the user buy this game RIGHT NOW?" Your verdict must be driven entirely by the GAME'S CURRENT STATE: recent player reviews, recurring complaints and praise, bugginess, and recent patch activity. You do NOT assess hardware compatibility or system requirements.

CRITICAL: Always pass the user's game name to the tools EXACTLY as the user wrote it. Never substitute, correct, or replace it with a different title — even if you do not recognize the game or believe it does not exist (it may have been released after your training data). Base your answer ONLY on the tool output, never on prior knowledge of the game or its franchise.

Follow these steps:

1. Use get_steam_reviews to fetch recent reviews for the game.
2. Use classify_reviews to analyse their sentiment and filter out joke reviews. If the user stated priorities or concerns, pass them as the focus_topics argument (e.g. focus_topics="Performance, Bugs") — the tool will then surface reviews about those topics, marked with matches_your_topics. Note the positive vs negative counts and what genuine reviewers are actually saying.
3. Identify the recurring themes: what do genuine reviewers repeatedly complain about, and what do they repeatedly praise?
4. If the genuine negative reviews mention bugs, crashes, or performance issues, use get_recent_patches to check whether the developers have addressed those issues recently.
{priorities_block}
Calibration — apply these thresholds to the counts from classify_reviews:
- BUY is the default verdict when a clear majority of genuine reviews are positive (roughly 70% or more) and the complaints are about preferences (difficulty, art style, grind, price) rather than stability.
- WAIT only when negative reviews are a substantial share (roughly 40% or more) OR genuine reviewers repeatedly report crashes, broken saves, or severe performance problems that recent patches have NOT addressed.
- In between (60-70% positive), lean BUY unless the dominant complaint is an unfixed stability issue or hits one of the user's stated priorities/concerns.
- Scattered or minor complaints are normal for every game and are NOT a reason to WAIT. Do not choose WAIT "just to be safe" — an unjustified WAIT is as wrong as an unjustified BUY.

Then write your final answer in EXACTLY this structure:

BUY
or
WAIT

(The first line must be the single word BUY or WAIT — nothing else on that line.)

Current state: 2–3 sentences describing the game's situation RIGHT NOW. State the overall player sentiment (reference the positive/negative review counts, e.g. "most recent reviews are positive"), the most common complaints and praise, and whether recent patches have addressed the complaints (cite a patch title/date if relevant). If the game is currently buggy or unstable, say so plainly.

Reasons:
- 2–3 concise bullet points justifying the verdict. Each bullet must reference the review data or patch history.
{user_focus_section}
Be direct and factual. Do not pad your answer with lengthy preamble. If the reviews say nothing about a topic the user asked about, state that explicitly rather than guessing."""

# Extra reasoning step injected when the user picked priorities/concerns
_PRIORITIES_STEP = """5. The user told you what matters to them. Weight your analysis accordingly:
{details}   When reviews speak to these topics, they should influence the verdict more than unrelated complaints or praise. A game with great reviews overall but recurring complaints about exactly what the user cares about may deserve WAIT — and vice versa.
"""

_FOCUS_SECTION = """
What you asked about: 1–3 sentences directly addressing the user's selected priorities/concerns, grounded in what reviewers actually say (quote or paraphrase where possible). If reviews don't mention one of their topics, say so explicitly.
"""

# The three tools the AI is allowed to call
tools = [get_steam_reviews, classify_reviews, get_recent_patches]


def _summarize_tool_result(tool_name: str, content: str, log):
    """Turn a tool's raw JSON output into human-readable log lines for the UI."""
    try:
        data = json.loads(content, strict=False)
    except (json.JSONDecodeError, TypeError):
        log(f"[{tool_name}] Result received.")
        return

    if isinstance(data, dict) and "error" in data:
        log(f"[{tool_name}] {data['error']}")
        return

    if tool_name == "get_steam_reviews":
        reviews = data.get("reviews", [])
        log(f"[Steam] Matched '{data.get('game_name', '?')}' (app {data.get('app_id', '?')}).")
        log(f"[Steam] Pulled {len(reviews)} recent English reviews.")
        # Show a preview of each review scrolling by, like the agent is reading them
        for i, r in enumerate(reviews, 1):
            snippet = r[:90] + ('...' if len(r) > 90 else '')
            log(f"[Steam] Review {i:02d}: \"{snippet}\"")

    elif tool_name == "classify_reviews":
        log(f"[Classify] {data.get('genuine_count', 0)} genuine / "
            f"{data.get('total_count', 0) - data.get('genuine_count', 0)} meme reviews filtered out.")
        log(f"[Classify] Sentiment among genuine reviews: "
            f"{data.get('positive_count', 0)} positive, {data.get('negative_count', 0)} negative.")
        if data.get("relevant_to_user_topics_count"):
            log(f"[Classify] {data['relevant_to_user_topics_count']} reviews mention the topics you asked about.")
        for r in data.get("top_genuine_reviews", []):
            snippet = r.get("snippet", "")[:80]
            tag = " [topic match]" if r.get("matches_your_topics") else ""
            log(f"[Classify] {r.get('sentiment', '?').upper()} ({r.get('confidence_pct', 0)}%){tag}: \"{snippet}\"")

    elif tool_name == "get_recent_patches":
        patches = data.get("patches", [])
        log(f"[Patches] Found {len(patches)} recent official patch notes.")
        for p in patches:
            log(f"[Patches] {p.get('date', '?')} — {p.get('title', '')}")


def run_agent(game_name: str, priorities: str = "", concerns: str = "", log=None) -> str:
    """Run the buy-or-wait agent. `log` is an optional callback(str) that receives
    progress lines as the agent works — used by server.py to stream the thought
    process to the frontend. Falls back to printing when not provided."""
    if log is None:
        log = lambda line: print(line)

    # Create the AI model — temperature 0.3 keeps answers consistent, not too random
    llm = ChatMistralAI(model="mistral-small-latest", temperature=0.3)

    # Build the optional priorities/concerns step for the system prompt
    details = ""
    if priorities:
        details += f"   - Priorities (what they care about most): {priorities}\n"
    if concerns:
        details += f"   - Concerns (what they're worried about): {concerns}\n"

    if details:
        priorities_block = _PRIORITIES_STEP.format(details=details)
        user_focus_section = _FOCUS_SECTION
    else:
        priorities_block = ""
        user_focus_section = ""

    system_prompt = _SYSTEM_PROMPT_TEMPLATE.format(
        priorities_block=priorities_block,
        user_focus_section=user_focus_section,
    )

    # Put priorities/concerns in the user message too — models weight the user
    # turn more heavily than system-prompt details, so this makes them reliably
    # show up in the answer.
    question = f"Should I buy {game_name} right now?"
    if priorities:
        question += f" What matters most to me: {priorities}."
    if concerns:
        question += f" I'm specifically worried about: {concerns}."

    log(f"[Agent] Target game: {game_name}")
    if priorities:
        log(f"[Agent] User priorities: {priorities}")
    if concerns:
        log(f"[Agent] User concerns: {concerns}")
    log("[Agent] Starting analysis...")

    # create_react_agent builds a LangGraph agent that loops:
    #   think → call a tool → read result → think again → ... → final answer
    agent = create_react_agent(llm, tools, prompt=system_prompt)

    final_message = None
    seen = 0
    for step in agent.stream(
        {"messages": [HumanMessage(content=question)]},
        config={"recursion_limit": 15},  # ~6 tool calls max before it must give an answer
        stream_mode="values",
    ):
        messages = step["messages"]
        # Log only the messages we haven't seen in a previous step
        for msg in messages[seen:]:
            msg_type = msg.__class__.__name__
            if msg_type == "AIMessage" and getattr(msg, "tool_calls", None):
                for tc in msg.tool_calls:
                    args = json.dumps(tc.get("args", {}))[:120]
                    log(f"[Agent] Calling tool {tc['name']}({args})")
            elif msg_type == "ToolMessage":
                _summarize_tool_result(getattr(msg, "name", "tool"), msg.content, log)
            msg.pretty_print()  # keep the full trace in the server terminal
        seen = len(messages)
        final_message = messages[-1]

    log("[Agent] Analysis complete — writing verdict.")

    # The last message in the conversation is always the agent's final answer
    return final_message.content if final_message else "No verdict produced."


# This block only runs when you do: python agent.py
# It won't run when server.py imports this file
if __name__ == "__main__":
    verdict = run_agent(
        game_name="Cyberpunk 2077",
        priorities="Performance & optimization",
        concerns="Bugs, Crashes",
    )
    print("\n=== VERDICT ===")
    print(verdict)
