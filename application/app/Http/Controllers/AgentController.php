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
            'game'       => 'required|string|max:100',
            'priorities' => 'nullable|string|max:300',
            'concerns'   => 'nullable|string|max:300',
        ]);

        $trackingId = (string) Str::uuid();

        $specs = [
            'id'         => $trackingId,
            'game'       => $request->input('game'),
            'priorities' => $request->input('priorities', ''),
            'concerns'   => $request->input('concerns', ''),
        ];

        // Store specifications in Cache (valid for 10 minutes) for status/mock checks
        Cache::put("job_specs_{$trackingId}", $specs, 600);
        
        // Also register the start time for mock simulation fallback
        Cache::put("job_start_{$trackingId}", time(), 600);

        try {
            $backendUrl = config('services.backend_api.url');
            
            // Format the body to match the backend's GuideRequest Pydantic model.
            // We pass our tracking ID as job_id so /status/{id} polling lines up.
            $payload = [
                'game_name'  => $request->input('game'),
                'priorities' => $request->input('priorities', '') ?? '',
                'concerns'   => $request->input('concerns', '') ?? '',
                'job_id'     => $trackingId,
            ];

            // The backend now runs the agent in a background thread and returns
            // immediately — the results page polls /check-status/{id} for live
            // progress logs and the final verdict.
            $response = Http::timeout(15)->post($backendUrl . '/guide', $payload);

            if ($response->successful()) {
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
            'game'       => 'Unknown Game',
            'priorities' => '',
            'concerns'   => '',
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

        // Reaching here means the backend's /status endpoint was unreachable
        // (server offline or restarted mid-job). Be honest rather than fabricating.
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
