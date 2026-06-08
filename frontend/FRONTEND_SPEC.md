# Frontend Specification: Smart Buyer's Guide (Laravel + MCP Architecture)

## 1. Architectural Overview
- **Framework:** Laravel (PHP)
- **Pattern:** Modified MVC (Strictly View + Controller, **No Eloquent/Database Models**).
- **Data/Agent Layer:** Model Context Protocol (MCP). The Laravel Controller will bypass traditional database models and communicate directly with the local MCP server/client inside this repository to orchestrate the AI Agent pipeline, fetch status updates, and stream reasoning logs.
- **Frontend Engine:** Laravel Blade templates mixed with Vanilla JS/Alpine.js for dynamic client-side DOM updates.

## 2. Objective
Build a dynamic, highly responsive UI/UX for an AI-driven hardware checking app. The interface must gather user input (Hardware specs + Target game), hand execution over to the Controller -> MCP bridge, and display the Agent's reasoning logs dynamically *while* the AI is processing, culminating in a clear "Buy" or "Wait" recommendation.

## 3. Core UI/UX Requirements (UI Only - No Deployment)

### View A: The Request Form (`resources/views/index.blade.php`)
- Clean, modern layout (prefer Tailwind CSS utility classes).
- **Inputs Required:**
  - CPU (Dropdown or Text Input)
  - GPU (Dropdown or Text Input)
  - RAM (Dropdown/Select: 8GB, 16GB, 32GB, etc.)
  - Target Game (Text Input or Search Select)
- **Behavior:** On submit, prevent standard full-page reload. Transition gracefully to the loading/results state or pass a generated Job/Session ID to the Results view.

### View B: The Dynamic Dashboard (`resources/views/results.blade.php`)
- **Initial State:** Displays an active loading/processing layout.
- **Log Stream Container:** A dedicated scrollable panel or accordion list titled "Agent Thought Process".
- **Dynamic Polling Mechanic:** - Contains a client-side JavaScript routine that queries the Laravel Controller route (e.g., `GET /check-status/{id}`) every 2 seconds.
  - As new log array values are returned from the Controller via the MCP interface, JavaScript must instantly inject them into the DOM as individual list elements without wiping out existing ones.
- **Final Reveal State:** Once the MCP returns a status of `completed`, hide the processing indicator and reveal:
  - Big, unambiguous Badge: **BUY** (Green highlight) or **WAIT** (Red/Yellow highlight).
  - Summary Paragraph explaining the verdict.

## 4. Controller Layer Logic Blueprint (`app/Http/Controllers/AgentController.php`)
- `index()`: Renders the initial input form view.
- `initiateCheck(Request $request)`: Accepts form inputs, generates a unique job tracking ID, initiates contact with the MCP client to kick off the background agent routine, and redirects/returns the tracking ID.
- `checkStatus($id)`: Acts as an API route for the frontend polling script. It talks to the MCP to pull the latest array of generated thought logs and execution status (`processing` vs. `completed`), returning it as a clean JSON response.