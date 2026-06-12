import os
import re
import sys
import threading
import uuid

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
app = FastAPI(title="Game Buy Guide API", version="2.0.0")

# CORS lets the frontend (running on a different port) talk to this server
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_methods=["POST", "GET"],
    allow_headers=["*"],
)

# In-memory job store: {job_id: {"status", "logs", "verdict", "summary", "message"}}
# Fine for a single-process dev server; restartting the server clears jobs.
_jobs = {}
_jobs_lock = threading.Lock()


class GuideRequest(BaseModel):
    game_name: str        # e.g. "Elden Ring"
    priorities: str = ""  # e.g. "Performance & optimization, Story" (optional)
    concerns: str = ""    # e.g. "Bugs, Crashes" (optional)
    job_id: str = ""      # optional — Laravel passes its tracking UUID so both sides share an ID


def _parse_verdict(text: str) -> str:
    """Pull BUY/WAIT/AVOID out of the agent's answer.

    Primary contract: a "VERDICT: <word>" line at the END of the answer (the
    last such line wins, so reasoning that mentions the words earlier can't
    interfere). Fallbacks: a bare verdict word on its own line, then keyword
    scanning with negated-BUY protection ("don't buy" must not parse as BUY)."""
    verdict_line = re.compile(r"VERDICT\s*[:\-]?\s*\**\s*(BUY|WAIT|AVOID)", re.IGNORECASE)
    matches = verdict_line.findall(text)
    if matches:
        return matches[-1].upper()

    # Fallback 1: a line that is nothing but a verdict word
    for line in text.strip().splitlines():
        word = line.strip().upper().strip('*# .:!')
        if word in ("BUY", "WAIT", "AVOID"):
            return word

    upper = text.upper()
    positions = {v: upper.find(v) for v in ("AVOID", "WAIT", "BUY")}
    # Negated BUY ("DON'T BUY", "DO NOT BUY", "AVOID BUYING") must not count
    buy_pos = positions["BUY"]
    if buy_pos != -1:
        preceding = upper[max(0, buy_pos - 12):buy_pos]
        if "DON'T" in preceding or "NOT" in preceding or "AVOID" in preceding:
            positions["BUY"] = -1
    found = {v: p for v, p in positions.items() if p != -1}
    if not found:
        return "WAIT"  # safer default than a false BUY
    return min(found, key=found.get)


def _run_job(job_id: str, req: GuideRequest):
    """Background thread: run the agent, streaming log lines into the job store."""
    def log(line: str):
        with _jobs_lock:
            _jobs[job_id]["logs"].append(line)

    try:
        result = run_agent(req.game_name.strip(), req.priorities.strip(),
                           req.concerns.strip(), log=log)
        with _jobs_lock:
            _jobs[job_id].update({
                "status": "completed",
                "verdict": _parse_verdict(result),
                "summary": result,
            })
    except Exception as exc:
        with _jobs_lock:
            _jobs[job_id].update({
                "status": "error",
                "message": str(exc),
            })


# Simple health check endpoint — useful to confirm the server is running
@app.get("/health")
def health():
    return {"status": "ok"}


# Main endpoint — starts the analysis in the background and returns immediately.
# The frontend then polls GET /status/{job_id} for live logs and the verdict.
@app.post("/guide")
def guide(req: GuideRequest):
    if not req.game_name.strip():
        raise HTTPException(status_code=400, detail="game_name cannot be empty")

    job_id = req.job_id.strip() or str(uuid.uuid4())
    with _jobs_lock:
        _jobs[job_id] = {"status": "processing", "logs": [], "verdict": None,
                         "summary": None, "message": None}

    threading.Thread(target=_run_job, args=(job_id, req), daemon=True).start()
    return {"status": "started", "job_id": job_id}


# Polled by Laravel's checkStatus() — returns live logs while processing,
# and verdict + summary once completed.
@app.get("/status/{job_id}")
def status(job_id: str):
    with _jobs_lock:
        job = _jobs.get(job_id)
        if job is None:
            raise HTTPException(status_code=404, detail="Unknown job id")
        return {
            "status": job["status"],
            "logs": list(job["logs"]),
            "verdict": job["verdict"],
            "summary": job["summary"],
            "message": job["message"],
        }
