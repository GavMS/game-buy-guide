<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>BUY_OR_WAIT — Should you buy this game right now?</title>
    <meta name="description" content="AI agent that reads recent Steam reviews and patch notes to tell you whether a game is worth buying right now.">
    <meta property="og:title" content="BUY_OR_WAIT">
    <meta property="og:description" content="Recent Steam reviews + patch history, analyzed. One verdict: buy or wait.">
    <meta property="og:type" content="website">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geist+Mono:wght@300;400;500&family=Inter:wght@300;400;500&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        mono: ['Geist Mono', 'monospace'],
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        bg: '#1f2228',
                    }
                }
            }
        }
    </script>

    <style>
        :root {
            --bg: #1f2228;
            --text: #ffffff;
            --text-secondary: rgba(255,255,255,0.7);
            --text-muted: rgba(255,255,255,0.5);
            --text-disabled: rgba(255,255,255,0.3);
            --border: rgba(255,255,255,0.1);
            --border-strong: rgba(255,255,255,0.2);
            --surface: rgba(255,255,255,0.03);
        }
        * { border-radius: 0 !important; box-shadow: none !important; }
        body {
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
        }
        .b-panel {
            background: var(--surface);
            border: 1px solid var(--border);
        }
        .b-input {
            background: rgba(255,255,255,0.05);
            border: 1px solid var(--border);
            color: var(--text);
            transition: all 0.15s ease;
        }
        .b-input::placeholder { color: var(--text-disabled); }
        .b-input:focus {
            outline: none;
            border-color: var(--border-strong);
        }
        .b-label {
            font-family: 'Geist Mono', monospace;
            text-transform: uppercase;
            letter-spacing: 1.4px;
            font-size: 11px;
            color: var(--text-muted);
        }
        .b-btn {
            font-family: 'Geist Mono', monospace;
            text-transform: uppercase;
            letter-spacing: 1.4px;
            background: #ffffff;
            color: #1f2228;
            border: 1px solid #ffffff;
            transition: all 0.15s ease;
            cursor: pointer;
        }
        .b-btn:hover { opacity: 0.5; }
        .b-btn:disabled { opacity: 0.3; cursor: wait; }
        .chip {
            font-family: 'Geist Mono', monospace;
            text-transform: uppercase;
            letter-spacing: 1.4px;
            font-size: 10px;
            padding: 8px 14px;
            border: 1px solid var(--border);
            color: var(--text-muted);
            background: transparent;
            cursor: pointer;
            transition: all 0.15s ease;
            user-select: none;
        }
        .chip:hover { opacity: 0.5; }
        .chip.active {
            background: #ffffff;
            color: #1f2228;
            border-color: #ffffff;
        }
        .loader-ring {
            border: 2px solid rgba(31,34,40,0.2);
            border-top: 2px solid #1f2228;
            width: 16px;
            height: 16px;
            border-radius: 50% !important;
            animation: spin 1s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body class="font-sans flex flex-col items-center justify-center p-4 md:p-8 selection:bg-white selection:text-[#1f2228]">

    <div class="w-full max-w-2xl">
        <!-- Header -->
        <div class="mb-12">
            <div class="inline-flex items-center gap-2 border border-[rgba(255,255,255,0.1)] px-3 py-1.5 mb-8">
                <svg class="w-3.5 h-3.5 text-white/50" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.076-4.076a1.526 1.526 0 0 1 1.037-.443 48.282 48.282 0 0 0 5.68-.494c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" />
                </svg>
                <span class="font-mono text-[10px] uppercase tracking-[1.4px] text-white/50">Steam Review Intelligence</span>
            </div>
            <h1 class="font-mono font-light text-5xl md:text-7xl tracking-tight leading-[0.95] mb-6">
                BUY<span class="text-white/30">_</span>OR<span class="text-white/30">_</span>WAIT
            </h1>
            <p class="text-white/50 text-base font-light leading-relaxed max-w-lg">
                Our AI reads the latest Steam reviews and patch notes for any game, filters out the joke reviews, and tells you whether it's worth buying <span class="text-white/70">right now</span> — or waiting for fixes.
            </p>
        </div>

        <!-- Main Form Card -->
        <div id="form-card" class="b-panel p-6 md:p-8 transition-all duration-300">
            <form id="check-form" class="space-y-8">
                @csrf

                <!-- Game Name (required) -->
                <div>
                    <label for="game" class="b-label block mb-2">[01] Which game? <span class="text-white/70">*</span></label>
                    <input type="text" id="game" name="game" required placeholder="e.g., Cyberpunk 2077"
                        class="b-input w-full py-3.5 px-4 text-base">
                    <p class="text-white/30 text-xs mt-2 font-light">Any game on Steam — new releases included.</p>
                </div>

                <!-- Priorities (optional) -->
                <div>
                    <label class="b-label block mb-1">[02] What matters most to you? <span class="normal-case tracking-normal text-white/30">— optional</span></label>
                    <p class="text-white/30 text-xs mb-3 font-light">We'll weight the review analysis toward what you pick.</p>
                    <div class="flex flex-wrap gap-2" id="priority-chips" data-target="priorities">
                        <button type="button" class="chip" data-value="Overall quality">Overall quality</button>
                        <button type="button" class="chip" data-value="Performance & optimization">Performance</button>
                        <button type="button" class="chip" data-value="Value for money">Value for money</button>
                        <button type="button" class="chip" data-value="Story">Story</button>
                        <button type="button" class="chip" data-value="Multiplayer">Multiplayer</button>
                        <button type="button" class="chip" data-value="Endgame content">Endgame</button>
                        <button type="button" class="chip" data-value="Content quantity">Content quantity</button>
                    </div>
                    <input type="hidden" name="priorities" id="priorities">
                </div>

                <!-- Concerns (optional) -->
                <div>
                    <label class="b-label block mb-1">[03] Any specific concerns? <span class="normal-case tracking-normal text-white/30">— optional</span></label>
                    <p class="text-white/30 text-xs mb-3 font-light">We'll check whether reviewers mention these.</p>
                    <div class="flex flex-wrap gap-2" id="concern-chips" data-target="concerns">
                        <button type="button" class="chip" data-value="Bugs">Bugs</button>
                        <button type="button" class="chip" data-value="Crashes">Crashes</button>
                        <button type="button" class="chip" data-value="Performance problems">Performance</button>
                        <button type="button" class="chip" data-value="Multiplayer population">Player population</button>
                        <button type="button" class="chip" data-value="Balance issues">Balance</button>
                        <button type="button" class="chip" data-value="Repetitive gameplay">Repetitive gameplay</button>
                        <button type="button" class="chip" data-value="Toxic community">Community</button>
                    </div>
                    <input type="hidden" name="concerns" id="concerns">
                </div>

                <!-- Submit Button -->
                <button type="submit" id="submit-btn" class="b-btn w-full py-4 text-sm font-medium flex items-center justify-center gap-3">
                    <span>Analyze Recent Reviews</span>
                    <div id="loader" class="loader-ring hidden"></div>
                </button>

                <p class="font-mono text-[10px] uppercase tracking-[1.4px] text-white/30 text-center">
                    Sources: Recent Steam reviews + official patch notes
                </p>
            </form>
        </div>

        <!-- Footer -->
        <p class="font-mono text-[10px] uppercase tracking-[1.4px] text-white/30 mt-8">
            Buy or Wait &copy; 2026 / Review analysis only — not a hardware benchmark
        </p>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Chip groups: toggle selection, write joined values into the hidden input
            document.querySelectorAll('[data-target]').forEach(group => {
                const hidden = document.getElementById(group.dataset.target);
                group.addEventListener('click', (e) => {
                    const chip = e.target.closest('.chip');
                    if (!chip) return;
                    chip.classList.toggle('active');
                    hidden.value = [...group.querySelectorAll('.chip.active')]
                        .map(c => c.dataset.value).join(', ');
                });
            });

            const form = document.getElementById('check-form');
            const submitBtn = document.getElementById('submit-btn');
            const btnText = submitBtn.querySelector('span');
            const loader = document.getElementById('loader');
            const card = document.getElementById('form-card');

            form.addEventListener('submit', async (e) => {
                e.preventDefault();

                submitBtn.disabled = true;
                btnText.textContent = 'Reading recent reviews...';
                loader.classList.remove('hidden');

                const formData = new FormData(form);

                try {
                    const response = await fetch("{{ route('agent.initiate') }}", {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: formData
                    });

                    if (!response.ok) {
                        throw new Error('API request failed');
                    }

                    const data = await response.json();

                    if (data.status === 'success' && data.id) {
                        btnText.textContent = 'Opening report...';
                        card.style.opacity = '0';
                        card.style.transform = 'translateY(-10px)';

                        setTimeout(() => {
                            window.location.href = `/results/${data.id}`;
                        }, 300);
                    } else {
                        throw new Error(data.message || 'Failed to start the review analysis.');
                    }

                } catch (err) {
                    alert('Error: ' + err.message);
                    submitBtn.disabled = false;
                    btnText.textContent = 'Analyze Recent Reviews';
                    loader.classList.add('hidden');
                }
            });
        });
    </script>
</body>
</html>
