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

For the **backend** (the AI):

- **Python 3.10 or newer** — [download here](https://www.python.org/downloads/) if you don't have it
- **A Mistral API key** — [sign up free at console.mistral.ai](https://console.mistral.ai/), go to API Keys, and create one

For the **frontend** (the web UI):

- **PHP 8.3 or newer** — comes with [Laravel Herd](https://herd.laravel.com/) (easiest on Windows/Mac), or install PHP directly
- **Composer** — [getcomposer.org](https://getcomposer.org/download/) (PHP's package manager)

Plus a terminal / command prompt open in the `game-buy-guide` folder.

> You can run and test the backend on its own without the frontend — see *Option: test the backend on its own* below. You only need PHP/Composer when you want the clickable web UI.

---

## Step-by-step setup (do this once)

### Step 1 — Install the required Python packages

Open a terminal in the `game-buy-guide` folder and run:

```
pip install -r agent_backend/requirements.txt
```

This installs everything the project needs (LangChain, FastAPI, scikit-learn, PyTorch, Transformers, etc). PyTorch is a large download, so this may take several minutes the first time.

### Step 2 — Put the sentiment model weights in place

The sentiment model lives in `ml_models/sentiment_model/`. The big weights file
(`model.safetensors`, ~670 MB) is **not** stored in git, so you need to supply it
once. Make sure `ml_models/sentiment_model/` contains all four files:

```
ml_models/sentiment_model/
├── config.json
├── model.safetensors      ← ~670 MB, the actual weights
├── tokenizer.json
└── tokenizer_config.json
```

If `model.safetensors` is missing, copy it in from wherever the team shares the
model weights. The backend loads this model at startup, so it must be present
before you run the server.

### Step 3 — Add your Mistral API key

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

The full app has **two parts** that run at the same time, each in its own terminal:

- **the backend** — the Python AI server (runs on port **8000**)
- **the frontend** — the Laravel web UI you actually click around in (runs on port **8080**)

Start the backend first, then the frontend, then open the UI in your browser.

### Part 1 — Start the backend (Python AI server)

From the `game-buy-guide` folder:

```
python -m uvicorn agent_backend.server:app --reload --port 8000
```

Leave this terminal open. The server runs at `http://localhost:8000`. To confirm it's alive, open `http://localhost:8000/docs` in your browser — you should see an interactive API page.

### Part 2 — Start the frontend (Laravel web UI)

Open a **second** terminal. Do the one-time setup first:

```
cd application
composer install
```

Create your config file:

**Windows (PowerShell):**
```
copy .env.example .env
```
**Mac / Linux:**
```
cp .env.example .env
```

Generate the app key and set up the local database (the app uses SQLite + a cache table to pass the verdict between pages):

```
php artisan key:generate
php artisan migrate
```

> If `migrate` complains that the database file is missing, create the empty SQLite file first — on Windows: `New-Item database\database.sqlite`, on Mac/Linux: `touch database/database.sqlite` — then run `php artisan migrate` again.

Open `application/.env` and make sure this line points at the backend, **with no `/api` on the end**:

```
BACKEND_API_URL=http://localhost:8000
```

Now start the web server on port **8080** (port 8000 is already taken by the backend):

```
php artisan serve --port=8080
```

Open your browser at **`http://localhost:8080`**, enter a game and your PC specs, and you'll get a BUY / WAIT verdict from the AI.

> **If your results look generic / instant**, the frontend couldn't reach the backend and fell back to a built-in demo simulator. Check that Part 1 is still running and that `BACKEND_API_URL` is correct (no `/api`).

### Option — test the backend on its own (no frontend needed)

You don't have to run the UI to try the AI. Two quick ways:

**A. One-off terminal test** — runs a built-in check of **Cyberpunk 2077** on an RTX 2060:
```
python agent_backend/agent.py
```
You'll see the agent's thinking process printed out, then a final verdict at the bottom. To test a different game, open `agent_backend/agent.py` and change the `game_name` and `hardware` values near the bottom of the file.

**B. Via the API docs page** — with the backend running (Part 1), open `http://localhost:8000/docs`:
1. Click **POST /guide** → **Try it out**
2. Replace the placeholder with a real game and hardware, for example:
```json
{
  "game_name": "Elden Ring",
  "hardware": "RTX 3070, 16GB RAM"
}
```
3. Click **Execute** and wait 20–30 seconds — the agent fetches reviews, runs the models, and calls Mistral. The verdict appears under **Response body**.

> See `test_cases.txt` in the project root for a full list of games and hardware combos to test with.

---

## Files at a glance

```
game-buy-guide/
├── .env                          ← YOU create this (your secret API key lives here)
├── ml_models/
│   ├── sentiment_model/          ← 5-star BERT model (transformers) — detects positive/negative
│   │                               (model.safetensors is ~670MB & gitignored — see note below)
│   └── meme_detector_model.pkl   ← trained by Person 1 — detects joke reviews
├── agent_backend/
│   ├── tools.py                  ← the 3 actions the AI can take (fetch reviews, classify, check patches)
│   ├── agent.py                  ← wires the AI + tools together, run_agent() lives here
│   ├── server.py                 ← the web server Person 3's frontend talks to
│   ├── requirements.txt          ← list of packages to install
│   └── .env.example              ← template showing what your .env file should look like
└── application/                  ← Person 3's Laravel web UI (run with: php artisan serve --port=8080)
    ├── app/Http/Controllers/AgentController.php  ← talks to the backend, shows the verdict
    ├── resources/views/          ← the pages (index = form, results = verdict dashboard)
    └── .env.example              ← set BACKEND_API_URL here (must point at the backend)
```

---

## Common problems

| Problem | Fix |
|---|---|
| `ModuleNotFoundError` | You forgot to run `pip install -r agent_backend/requirements.txt` |
| `AuthenticationError` or `401` | Your `MISTRAL_API_KEY` in `.env` is wrong or missing |
| `No Steam game found` | The game name didn't match anything — try the exact name from the Steam store page |
| Port 8000 already in use | Change `--port 8000` to `--port 8001` in the uvicorn command (and update `BACKEND_API_URL` in `application/.env` to match) |
| `uvicorn: command not found` | Use `python -m uvicorn` instead of just `uvicorn` |
| Frontend results look generic / appear instantly | The frontend couldn't reach the backend and used its demo simulator. Make sure the backend (Part 1) is running and `BACKEND_API_URL` in `application/.env` is `http://localhost:8000` with **no** `/api` suffix |
| `composer: command not found` | Composer isn't installed — get it from [getcomposer.org](https://getcomposer.org/download/) |
| `php artisan serve` fails: port 8080 in use | Pick another port, e.g. `php artisan serve --port=8081`, and open that port in your browser |
| Frontend page is blank or errors on load | You probably skipped `php artisan key:generate` or `php artisan migrate` — run both inside the `application` folder |
