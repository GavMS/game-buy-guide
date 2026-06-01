# Game Buy Guide

You type in a game name and your PC specs. The app tells you **BUY** or **WAIT**, with reasons — based on real Steam reviews and recent patch notes.

---

## What it actually does (plain English)

1. It searches Steam and grabs the 20 most recent player reviews for the game.
2. It runs those reviews through two AI models we trained:
   - One decides if a review is **positive or negative**.
   - One decides if a review is a **genuine opinion or a joke/meme** (so joke reviews get ignored).
3. If a lot of genuine reviews complain about bugs or bad performance, it checks Steam's patch notes to see if the developers have fixed anything recently.
4. A Mistral AI (like ChatGPT but from a different company) reads all of that and gives you a final **BUY or WAIT** verdict with 2–3 bullet-point reasons — and it takes your specific PC hardware into account.

---

## What you need before starting

- **Python 3.10 or newer** — [download here](https://www.python.org/downloads/) if you don't have it
- **A Mistral API key** — [sign up free at console.mistral.ai](https://console.mistral.ai/), go to API Keys, and create one
- A terminal / command prompt open in the `game-buy-guide` folder

---

## Step-by-step setup (do this once)

### Step 1 — Install the required Python packages

Open a terminal in the `game-buy-guide` folder and run:

```
pip install -r agent_backend/requirements.txt
```

This installs everything the project needs (LangChain, FastAPI, scikit-learn, etc). It may take a minute.

### Step 2 — Add your Mistral API key

The project needs a `.env` file inside `agent_backend/` with your key inside.

Copy the example template:

**On Windows (PowerShell):**
```
copy agent_backend\.env.example agent_backend\.env
```

**On Mac / Linux:**
```
cp agent_backend/.env.example agent_backend/.env
```

Now open `agent_backend/.env` in any text editor and replace `your_key_here` with your actual key:

```
MISTRAL_API_KEY=paste_your_real_key_here
```

Save and close. That's it — the app reads the key from there automatically.

> The `.env` file is in `.gitignore` so your key will never be accidentally pushed to GitHub.

---

## How to run it

### Option A — Quick test in the terminal (no server needed)

This runs a built-in test that checks **Cyberpunk 2077** for an RTX 2060 system:

```
python agent_backend/agent.py
```

You'll see the agent's thinking process printed out, then a final verdict at the bottom. To test a different game, open `agent_backend/agent.py` and change the `game_name` and `hardware` values near the bottom of the file.

### Option B — Start the API server (this is what the frontend connects to)

```
python -m uvicorn agent_backend.server:app --reload --port 8000
```

The server starts at `http://localhost:8000`. Leave this terminal open while you use the app.

**To test it's working:**

Open your browser and go to:
```
http://localhost:8000/docs
```

You'll see an interactive page like this:

1. Click **POST /guide**
2. Click **Try it out**
3. Replace the placeholder text with a real game and hardware, for example:
```json
{
  "game_name": "Elden Ring",
  "hardware": "RTX 3070, 16GB RAM"
}
```
4. Click **Execute**
5. Wait 20–30 seconds — the agent is fetching reviews, running the models, and calling Mistral
6. The verdict appears under **Response body**

> See `test_cases.txt` in the project root for a full list of games and hardware combos to test with.

---

## Files at a glance

```
game-buy-guide/
├── .env                          ← YOU create this (your secret API key lives here)
├── ml_models/
│   ├── sentiment_model.pkl       ← trained by Person 1 — detects positive/negative
│   └── meme_detector_model.pkl   ← trained by Person 1 — detects joke reviews
├── agent_backend/
│   ├── tools.py                  ← the 3 actions the AI can take (fetch reviews, classify, check patches)
│   ├── agent.py                  ← wires the AI + tools together, run_agent() lives here
│   ├── server.py                 ← the web server Person 3's frontend talks to
│   ├── requirements.txt          ← list of packages to install
│   └── .env.example              ← template showing what your .env file should look like
└── frontend/                     ← Person 3's UI
```

---

## Common problems

| Problem | Fix |
|---|---|
| `ModuleNotFoundError` | You forgot to run `pip install -r agent_backend/requirements.txt` |
| `AuthenticationError` or `401` | Your `MISTRAL_API_KEY` in `.env` is wrong or missing |
| `No Steam game found` | The game name didn't match anything — try the exact name from the Steam store page |
| Port 8000 already in use | Change `--port 8000` to `--port 8001` in the uvicorn command |
| `uvicorn: command not found` | Use `python -m uvicorn` instead of just `uvicorn` |
