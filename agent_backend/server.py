import os
import sys

# Make sure agent_backend/ is on the path so "from agent import ..." works
# when uvicorn imports this file as a module from the project root
sys.path.insert(0, os.path.dirname(__file__))

from dotenv import load_dotenv

# Load the API key from .env before anything else
load_dotenv(os.path.join(os.path.dirname(__file__), '.env'))

from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
from agent import run_agent

# FastAPI is the web framework — it handles incoming HTTP requests
app = FastAPI(title="Game Buy Guide API", version="1.0.0")

# CORS lets the frontend (running on a different port) talk to this server
# allow_origins=["*"] means any website/app can call it — fine for development
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_methods=["POST", "GET"],
    allow_headers=["*"],
)


# These classes define the shape of the JSON the server expects and returns
# Pydantic automatically validates the incoming request against this
class GuideRequest(BaseModel):
    game_name: str   # e.g. "Elden Ring"
    hardware: str    # e.g. "RTX 3070, 16GB RAM"


class GuideResponse(BaseModel):
    verdict: str     # the BUY / WAIT text from the agent


# Simple health check endpoint — useful to confirm the server is running
@app.get("/health")
def health():
    return {"status": "ok"}


# Main endpoint — Person 3's frontend sends a POST request here
@app.post("/guide", response_model=GuideResponse)
def guide(req: GuideRequest):
    # Reject requests with blank fields before bothering the AI
    if not req.game_name.strip():
        raise HTTPException(status_code=400, detail="game_name cannot be empty")
    if not req.hardware.strip():
        raise HTTPException(status_code=400, detail="hardware cannot be empty")

    try:
        result = run_agent(req.game_name.strip(), req.hardware.strip())
    except Exception as exc:
        # If the agent crashes for any reason, return a 500 error with the message
        raise HTTPException(status_code=500, detail=str(exc))

    return GuideResponse(verdict=result)
