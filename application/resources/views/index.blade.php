<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>GAME://BUY-GUIDE — AI Hardware Compatibility Checker</title>
    <meta name="description" content="AI agent that checks your hardware against a game's requirements and tells you whether to buy or wait.">
    <meta property="og:title" content="GAME://BUY-GUIDE">
    <meta property="og:description" content="AI-powered buy-or-wait verdicts for your hardware.">
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
                    },
                    borderRadius: {
                        none: '0px',
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
            --radius: 0px;
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
        .dim-hover { transition: all 0.15s ease; cursor: pointer; }
        .dim-hover:hover { opacity: 0.5; }
        .loader-ring {
            border: 2px solid rgba(31,34,40,0.2);
            border-top: 2px solid #1f2228;
            width: 16px;
            height: 16px;
            border-radius: 50% !important;
            animation: spin 1s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        select.b-input option { background: #1f2228; color: #ffffff; }
    </style>
</head>
<body class="font-sans flex flex-col items-center justify-center p-4 md:p-8 selection:bg-white selection:text-[#1f2228]">

    <div class="w-full max-w-2xl">
        <!-- Header -->
        <div class="mb-12">
            <div class="inline-flex items-center gap-2 border border-[rgba(255,255,255,0.1)] px-3 py-1.5 mb-8">
                <svg class="w-3.5 h-3.5 text-white/50" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 14.25h13.5m-13.5 0a3 3 0 0 1-3-3m3 3a3 3 0 1 0 0 6h13.5a3 3 0 1 0 0-6m-16.5-3a3 3 0 0 1 3-3h13.5a3 3 0 0 1 3 3m-19.5 0a4.5 4.5 0 0 1 .9-2.7L5.737 5.1a3.375 3.375 0 0 1 2.7-1.35h7.126c1.062 0 2.062.5 2.7 1.35l2.587 3.45a4.5 4.5 0 0 1 .9 2.7m0 0a3 3 0 0 1-3 3" />
                </svg>
                <span class="font-mono text-[10px] uppercase tracking-[1.4px] text-white/50">AI-Powered Gaming Diagnostics</span>
            </div>
            <h1 class="font-mono font-light text-5xl md:text-7xl tracking-tight leading-[0.95] mb-6">
                BUY<span class="text-white/30">_</span>OR<span class="text-white/30">_</span>WAIT
            </h1>
            <p class="text-white/50 text-base font-light leading-relaxed max-w-lg">
                Enter your target game and system specifications. Our AI agent compiles requirement metrics to deliver a comprehensive purchase recommendation.
            </p>
        </div>

        <!-- Main Form Card -->
        <div id="form-card" class="b-panel p-6 md:p-8 transition-all duration-300">
            <form id="check-form" class="space-y-7">
                @csrf

                <!-- Target Game -->
                <div>
                    <label for="game" class="b-label block mb-2">[01] Target Game</label>
                    <input type="text" id="game" name="game" required placeholder="e.g., Cyberpunk 2077"
                        class="b-input w-full py-3.5 px-4 text-base">
                </div>

                <!-- Specs Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-7">
                    <div>
                        <label for="cpu" class="b-label block mb-2">[02] CPU Spec</label>
                        <input type="text" id="cpu" name="cpu" list="cpu-suggestions" required placeholder="e.g., AMD Ryzen 7 7800X3D"
                            class="b-input w-full py-3.5 px-4 text-base">
                        <datalist id="cpu-suggestions">
                            <option value="AMD Ryzen 7 7800X3D">
                            <option value="Intel Core i7-14700K">
                            <option value="AMD Ryzen 5 7600X">
                            <option value="Intel Core i5-13600K">
                            <option value="Intel Core i9-14900K">
                            <option value="AMD Ryzen 9 7950X">
                            <option value="Intel Core i3-12100F">
                            <option value="Integrated Graphics CPU">
                        </datalist>
                    </div>

                    <div>
                        <label for="gpu" class="b-label block mb-2">[03] GPU Spec</label>
                        <input type="text" id="gpu" name="gpu" list="gpu-suggestions" required placeholder="e.g., NVIDIA RTX 4070"
                            class="b-input w-full py-3.5 px-4 text-base">
                        <datalist id="gpu-suggestions">
                            <option value="NVIDIA GeForce RTX 4090">
                            <option value="NVIDIA GeForce RTX 4070 Super">
                            <option value="AMD Radeon RX 7800 XT">
                            <option value="NVIDIA GeForce RTX 3060">
                            <option value="AMD Radeon RX 6600">
                            <option value="NVIDIA GeForce GTX 1650">
                            <option value="Intel UHD Graphics 770">
                            <option value="AMD Radeon RX 580">
                        </datalist>
                    </div>
                </div>

                <!-- RAM Selection -->
                <div>
                    <label for="ram" class="b-label block mb-2">[04] System RAM Capacity</label>
                    <div class="relative">
                        <select id="ram" name="ram" required
                            class="b-input w-full py-3.5 px-4 text-base appearance-none cursor-pointer">
                            <option value="" disabled selected>Select RAM size...</option>
                            <option value="8GB">8 GB DDR4 / DDR5 (Basic)</option>
                            <option value="16GB">16 GB DDR4 / DDR5 (Recommended)</option>
                            <option value="32GB">32 GB DDR5 (Performance)</option>
                            <option value="64GB">64 GB DDR5 (Enthusiast)</option>
                        </select>
                        <span class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
                            <svg class="w-4 h-4 text-white/30" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                            </svg>
                        </span>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" id="submit-btn" class="b-btn w-full py-4 text-sm font-medium flex items-center justify-center gap-3">
                    <span>Initialize AI Diagnostic Check</span>
                    <div id="loader" class="loader-ring hidden"></div>
                </button>
            </form>
        </div>

        <!-- Footer -->
        <p class="font-mono text-[10px] uppercase tracking-[1.4px] text-white/30 mt-8">
            Smart Buyer's Guide &copy; 2026 / Laravel + Model Context Protocol
        </p>
    </div>

    <!-- AJAX Submission Script -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('check-form');
            const submitBtn = document.getElementById('submit-btn');
            const btnText = submitBtn.querySelector('span');
            const loader = document.getElementById('loader');
            const card = document.getElementById('form-card');

            form.addEventListener('submit', async (e) => {
                e.preventDefault();

                submitBtn.disabled = true;
                btnText.textContent = 'Contacting MCP Client...';
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
                        btnText.textContent = 'Establishing Link...';
                        card.style.opacity = '0';
                        card.style.transform = 'translateY(-10px)';

                        setTimeout(() => {
                            window.location.href = `/results/${data.id}`;
                        }, 300);
                    } else {
                        throw new Error(data.message || 'Failed to initialize diagnostic check.');
                    }

                } catch (err) {
                    alert('Error: ' + err.message);
                    submitBtn.disabled = false;
                    btnText.textContent = 'Initialize AI Diagnostic Check';
                    loader.classList.add('hidden');
                }
            });
        });
    </script>
</body>
</html>
