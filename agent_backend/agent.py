import os
from dotenv import load_dotenv

# Read the MISTRAL_API_KEY from the .env file in the project root
load_dotenv(os.path.join(os.path.dirname(__file__), '.env'))

from langchain_mistralai import ChatMistralAI
from langchain_core.messages import HumanMessage   # represents a message from the user
from langgraph.prebuilt import create_react_agent  # replaces the old create_tool_calling_agent
from tools import get_steam_reviews, classify_reviews, get_recent_patches

# Instructions given to the AI at the start. {hardware} gets filled in at runtime.
_SYSTEM_PROMPT_TEMPLATE = """You are a game purchasing advisor. Your verdict must be driven primarily by the GAME'S CURRENT STATE (player reviews, bugginess, recent patches), with the user's hardware as a secondary factor — never base the verdict on hardware alone.

Follow these steps:

1. Use get_steam_reviews to fetch recent reviews for the game.
2. Use classify_reviews to analyse their sentiment and filter out joke reviews. Note the positive vs negative counts and what genuine reviewers are actually saying.
3. If the genuine negative reviews mention bugs, crashes, or performance issues, use get_recent_patches to check whether the developers have addressed those issues recently.

Then write your final answer in EXACTLY this structure:

BUY
or
WAIT

(The first line must be the single word BUY or WAIT — nothing else on that line.)

Current state: 2–3 sentences describing the game's situation RIGHT NOW. State the overall player sentiment (reference the positive/negative review counts, e.g. "most recent reviews are positive"), whether genuine reviewers report bugs, crashes, or performance problems, and whether recent patches have fixed them (cite a patch title/date if relevant). If the game is currently buggy or unstable, say so plainly.

Reasons:
- 2–3 concise bullet points justifying the verdict. Each bullet must reference the review data or patch history. Mention whether the user's hardware ({hardware}) is likely to be affected by any performance issues only where relevant.

Be direct and factual. Do not pad your answer with lengthy preamble. Base BUY/WAIT on the game's state first; only let hardware tip a borderline call."""

# The three tools the AI is allowed to call
tools = [get_steam_reviews, classify_reviews, get_recent_patches]


def run_agent(game_name: str, hardware: str) -> str:
    # Create the AI model — temperature 0.3 keeps answers consistent, not too random
    llm = ChatMistralAI(model="mistral-small-latest", temperature=0.3)

    # Fill the hardware placeholder into the system prompt
    system_prompt = _SYSTEM_PROMPT_TEMPLATE.format(hardware=hardware)

    # create_react_agent builds a LangGraph agent that loops:
    #   think → call a tool → read result → think again → ... → final answer
    # prompt= sets the system instructions; recursion_limit caps the number of steps
    agent = create_react_agent(llm, tools, prompt=system_prompt)

    # Stream each step to the terminal so you can watch the agent work
    print("\n--- Agent is thinking ---")
    final_message = None
    for step in agent.stream(
        {"messages": [HumanMessage(content=f"Should I buy {game_name}?")]},
        config={"recursion_limit": 15},  # ~6 tool calls max before it must give an answer
        stream_mode="values",
    ):
        msg = step["messages"][-1]
        msg.pretty_print()   # built-in LangChain formatter — shows role + content cleanly
        final_message = msg

    # The last message in the conversation is always the agent's final answer
    return final_message.content if final_message else "No verdict produced."


# This block only runs when you do: python agent.py
# It won't run when server.py imports this file
if __name__ == "__main__":
    verdict = run_agent(
        game_name="Cyberpunk 2077",
        hardware="RTX 2060, Ryzen 5 3600, 16GB RAM",
    )
    print("\n=== VERDICT ===")
    print(verdict)
