from transformers import pipeline

# Point to the UNZIPPED local directory
local_sentiment_analyzer = pipeline(
    "sentiment-analysis", 
    model="./models/sentiment_model", # No .zip extension!
    device=-1 # -1 forces it to use the local CPU. 0 uses a local GPU if they have one.
)

from langchain.tools import tool
import json

# 1. We take your exact wrapper function and add the @tool decorator
@tool
def analyze_steam_review(review_text: str) -> str:
    """
    USE THIS TOOL EVERY TIME YOU NEED TO ANALYZE A STEAM REVIEW.
    Pass the raw text of the user's game review into this tool.
    It will return a JSON dictionary containing the true Sentiment (Positive, Negative, or Mixed) 
    and the exact confidence percentages based on a 5-Star machine learning analysis.
    """
    # This is your exact Expected Value logic!
    raw_results = local_sentiment_analyzer([review_text], top_k=None)[0]
    
    star_probs = {1: 0.0, 2: 0.0, 3: 0.0, 4: 0.0, 5: 0.0}
    for result in raw_results:
        star_num = int(result['label'].split()[0])
        star_probs[star_num] = result['score']
        
    positive_percent = round((star_probs[1]*0) + (star_probs[2]*25) + (star_probs[3]*50) + (star_probs[4]*75) + (star_probs[5]*100), 1)
    negative_percent = round(100.0 - positive_percent, 1)
    
    if positive_percent >= 60.0: primary = "Positive"
    elif positive_percent <= 40.0: primary = "Negative"
    else: primary = "Mixed"
        
    analysis_dict = {
        "text_analyzed": review_text,
        "ml_insights": {
            "prediction": primary,
            "positive_percent": positive_percent,
            "negative_percent": negative_percent
        }
    }
    
    # LangChain tools communicate best with strings, so we dump the dict to JSON
    return json.dumps(analysis_dict)

# 2. We package the tool into a list for the Agent
agent_tools = [analyze_steam_review]