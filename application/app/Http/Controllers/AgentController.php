<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class AgentController extends Controller
{
    /**
     * Render the initial request form view.
     */
    public function index()
    {
        return view('index');
    }

    /**
     * Initiate hardware check.
     * Validates inputs, generates job ID, triggers external API, returns JSON tracking info.
     */
    public function initiateCheck(Request $request)
    {
        $request->validate([
            'cpu'  => 'required|string|max:100',
            'gpu'  => 'required|string|max:100',
            'ram'  => 'required|string|max:20',
            'game' => 'required|string|max:100',
        ]);

        $trackingId = (string) Str::uuid();

        $specs = [
            'id'   => $trackingId,
            'cpu'  => $request->input('cpu'),
            'gpu'  => $request->input('gpu'),
            'ram'  => $request->input('ram'),
            'game' => $request->input('game'),
        ];

        // Store specifications in Cache (valid for 10 minutes) for status/mock checks
        Cache::put("job_specs_{$trackingId}", $specs, 600);
        
        // Also register the start time for mock simulation fallback
        Cache::put("job_start_{$trackingId}", time(), 600);

        try {
            $backendUrl = config('services.backend_api.url');
            
            // Format the body to match Person 2's GuideRequest Pydantic Model
            $payload = [
                'game_name' => $request->input('game'),
                'hardware'  => "CPU: " . $request->input('cpu') . ", GPU: " . $request->input('gpu') . ", RAM: " . $request->input('ram'),
            ];

            // Hit Person 2's exact endpoint path: /guide.
            // The agent runs synchronously (Steam fetch + sentiment model + several
            // Mistral calls), so allow plenty of time before giving up.
            $response = Http::timeout(180)->post($backendUrl . '/guide', $payload);
            
            if ($response->successful()) {
                // Since the backend is synchronous, store the final verdict in cache 
                // so your checkStatus method can instantly read it when called.
                Cache::put("job_verdict_{$trackingId}", $response->json()['verdict'], 600);

                return response()->json([
                    'status' => 'success',
                    'id' => $trackingId,
                    'mode' => 'api'
                ]);
            }

            Log::warning("Backend API responded with error: " . $response->status() . ".");
        } catch (\Exception $e) {
            Log::info("External backend API unreachable: " . $e->getMessage());
        }

        // Backend unreachable or errored. Do NOT fabricate a verdict — tell the
        // user honestly. HTTP 200 so the form handler reads our JSON `status`
        // and surfaces the message (it throws a generic error on non-2xx).
        return response()->json([
            'status'  => 'error',
            'message' => 'The AI analysis engine is offline. Make sure the backend is running (uvicorn on port 8000) and try again.',
        ]);
    }

    /**
     * Render the dynamic results dashboard view.
     */
    public function results($id)
    {
        // Retrieve specs from Cache (fallback) or pass ID to view
        $specs = Cache::get("job_specs_{$id}", [
            'cpu' => 'Unknown CPU',
            'gpu' => 'Unknown GPU',
            'ram' => 'Unknown RAM',
            'game' => 'Unknown Game'
        ]);

        return view('results', [
            'id' => $id,
            'specs' => $specs
        ]);
    }

    /**
     * API endpoint for dynamic status polling.
     * Connects to external API status check or simulates progressive log updates.
     */
    public function checkStatus($id)
    {
        $specs = Cache::get("job_specs_{$id}");

        try {
            $backendUrl = config('services.backend_api.url');
            $response = Http::timeout(30)->get($backendUrl . '/status/' . $id);

            if ($response->successful()) {
                return response()->json($response->json());
            }
        } catch (\Exception $e) {
            // Unreachable external API, proceed to simulation mode
        }

        // If the synchronous /guide call already produced a real verdict, use it.
        // The presence of this cache key means initiateCheck() reached the backend
        // successfully, so we surface the genuine AI answer instead of the simulator.
        $verdictText = Cache::get("job_verdict_{$id}");
        if ($verdictText) {
            $upper   = strtoupper($verdictText);
            $buyPos  = strpos($upper, 'BUY');
            $waitPos = strpos($upper, 'WAIT');

            // Pick whichever keyword appears first; default to WAIT if neither is
            // found (safer to advise caution than to show a false BUY).
            if ($buyPos !== false && ($waitPos === false || $buyPos < $waitPos)) {
                $verdict = 'BUY';
            } else {
                $verdict = 'WAIT';
            }

            return response()->json([
                'status'  => 'completed',
                'verdict' => $verdict,
                'summary' => $verdictText,
                'logs'    => [
                    "[System] Diagnostics job {$id} initialised.",
                    "[Agent] AI backend contacted - analysing Steam reviews & patch notes...",
                    "[Agent] Verdict received from Game Buy Guide agent.",
                ],
            ]);
        }

        // No real verdict in cache. Because /guide runs synchronously during
        // initiateCheck(), a successful run always caches the verdict before this
        // page polls — so reaching here means the job is unknown/expired or the
        // backend was offline. Be honest rather than fabricating a result.
        if (!$specs) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Job not found or expired. Please run the check again.',
            ]);
        }

        return response()->json([
            'status'  => 'error',
            'message' => 'Verdict unavailable — the AI analysis engine may be offline. Please try again.',
        ]);
    }
}
