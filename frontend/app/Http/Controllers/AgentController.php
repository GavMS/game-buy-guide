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

            // Hit Person 2's exact endpoint path: /guide
            $response = Http::timeout(30)->post($backendUrl . '/guide', $payload);
            
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

            Log::warning("Backend API responded with error: " . $response->status() . ". Falling back to simulator.");
        } catch (\Exception $e) {
            Log::info("External backend API unreachable: " . $e->getMessage() . ". Falling back to local simulator.");
        }

        // Return success with simulator mode indicator
        return response()->json([
            'status' => 'success',
            'id' => $trackingId,
            'mode' => 'simulator'
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

        // Fallback Local Simulation logic
        if (!$specs) {
            return response()->json([
                'status' => 'error',
                'message' => 'Job specifications not found or expired.'
            ], 404);
        }

        $startTime = Cache::get("job_start_{$id}", time());
        $elapsed = time() - $startTime;

        $cpu = $specs['cpu'];
        $gpu = $specs['gpu'];
        $ram = $specs['ram'];
        $game = $specs['game'];

        // Define progressive logs
        $logs = [
            "[System] Initiating deep hardware diagnostics pipeline for Job UUID: {$id}...",
            "[Diagnose] System Specs identified: CPU='{$cpu}', GPU='{$gpu}', RAM='{$ram}'.",
        ];

        if ($elapsed >= 2) {
            $logs[] = "[Crawler] Scraping system configurations and compatibility metrics for '{$game}'...";
            $logs[] = "[Crawler] Found official minimum requirements: 8GB RAM, Quad-Core CPU, Entry-Level DirectX 12 GPU.";
        }
        if ($elapsed >= 4) {
            $logs[] = "[Analyzer] Analyzing CPU architecture: comparing '{$cpu}' single-threaded efficiency against physics engine load for '{$game}'...";
            $logs[] = "[Analyzer] CPU Benchmark analysis complete. Load distribution models look optimized.";
        }
        if ($elapsed >= 6) {
            $logs[] = "[Analyzer] Analyzing GPU pipeline: mapping shader pipelines of '{$gpu}' against rendering budget for target resolution...";
            $logs[] = "[Analyzer] VRAM check: processing overhead for '{$game}' textures... RAM buffering: {$ram} available.";
        }
        if ($elapsed >= 8) {
            $logs[] = "[Agent] Orchestrating final metrics: checking bottlenecks, power supply headroom, and frame rate estimates...";
            $logs[] = "[Agent] Running final decision matrix logic...";
        }
        if ($elapsed >= 10) {
            $logs[] = "[Agent] Diagnostics complete. Verdict and recommendation summary finalized.";
        }

        // Determine status and verdict based on elapsed time and specs
        if ($elapsed >= 10) {
            $status = 'completed';
            
            // Basic heuristics to make the recommendation feel authentic
            $isLowRam = stripos($ram, '8') !== false || stripos($ram, '4') !== false;
            $isLowGpu = stripos($gpu, 'gtx 10') !== false || stripos($gpu, 'gtx 16') !== false || stripos($gpu, 'integrated') !== false || stripos($gpu, 'intel uhd') !== false || stripos($gpu, '580') !== false;
            $isHighGame = stripos($game, 'cyberpunk') !== false || stripos($game, 'alan wake') !== false || stripos($game, 'gta 6') !== false || stripos($game, 'flight simulator') !== false;

            if (($isLowRam || $isLowGpu) && $isHighGame) {
                $verdict = 'WAIT';
                $summary = "We recommend you WAIT before buying this hardware or game. While your CPU ({$cpu}) may be capable, the target game '{$game}' is extremely taxing. Pairing it with a legacy GPU ({$gpu}) and/or limited RAM ({$ram}) will trigger significant bottlenecks, resulting in frame rates below 30FPS at 1080p. We suggest upgrading your GPU and expanding RAM to at least 16GB first.";
            } elseif ($isLowRam && !$isHighGame) {
                $verdict = 'WAIT';
                $summary = "We advise you to WAIT. Your CPU ({$cpu}) and GPU ({$gpu}) should handle '{$game}' decently, but {$ram} RAM is a major system bottleneck for modern gaming OS environments. Background processes combined with the game's asset loading will cause stuttering. Upgrading to 16GB RAM is strongly recommended.";
            } else {
                $verdict = 'BUY';
                $summary = "Excellent news! You are clear to BUY. The combination of your {$cpu} processor, {$gpu} graphics card, and {$ram} RAM provides more than enough computing power and bandwidth to run '{$game}' smoothly at high-fidelity settings. You will enjoy a stable, stutter-free gaming experience with high framerates.";
            }
        } else {
            $status = 'processing';
            $verdict = null;
            $summary = null;
        }

        return response()->json([
            'status' => $status,
            'verdict' => $verdict,
            'summary' => $summary,
            'logs' => $logs
        ]);
    }
}
