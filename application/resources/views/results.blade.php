<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Diagnostics Dashboard - Smart Buyer's Guide</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fira+Code:wght@400;500&family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Alpine.js CDN -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'sans-serif'],
                        outfit: ['Outfit', 'sans-serif'],
                        mono: ['Fira Code', 'monospace'],
                    },
                    colors: {
                        darkBg: '#090d16',
                        glassBg: 'rgba(15, 23, 42, 0.65)',
                        glassBorder: 'rgba(255, 255, 255, 0.08)',
                    }
                }
            }
        }
    </script>
    
    <style>
        body {
            background: radial-gradient(circle at 10% 20%, rgba(99, 102, 241, 0.12) 0%, transparent 45%),
                        radial-gradient(circle at 90% 80%, rgba(236, 72, 153, 0.12) 0%, transparent 45%),
                        #070a13;
            min-height: 100vh;
        }
        .glass-panel {
            background: rgba(15, 23, 42, 0.45);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.06);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
        }
        .terminal-box {
            background: rgba(8, 12, 24, 0.85);
            border: 1px solid rgba(255, 255, 255, 0.05);
            box-shadow: inset 0 0 20px rgba(0, 0, 0, 0.6);
        }
        .text-glow-green {
            text-shadow: 0 0 15px rgba(16, 185, 129, 0.4);
        }
        .text-glow-red {
            text-shadow: 0 0 15px rgba(239, 68, 68, 0.4);
        }
        .glow-border-green {
            box-shadow: 0 0 25px rgba(16, 185, 129, 0.25);
            border-color: rgba(16, 185, 129, 0.4);
        }
        .glow-border-red {
            box-shadow: 0 0 25px rgba(239, 68, 68, 0.25);
            border-color: rgba(239, 68, 68, 0.4);
        }
        .pulse-slow {
            animation: pulse 2.5s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; filter: drop-shadow(0 0 8px rgba(99, 102, 241, 0.6)); }
            50% { opacity: .4; filter: drop-shadow(0 0 2px rgba(99, 102, 241, 0.1)); }
        }
        .log-item {
            animation: slideIn 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(6px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="text-slate-100 flex flex-col items-center justify-center p-4 md:p-8 font-sans selection:bg-indigo-500/30 selection:text-indigo-200">

    <div class="w-full max-w-3xl z-10" x-data="diagnosticsHandler('{{ $id }}')" x-init="startPolling()" x-cloak>
        <!-- Header Info -->
        <div class="text-center mb-6">
            <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold tracking-wider text-pink-400 bg-pink-500/10 border border-pink-500/20 mb-4 outfit-font uppercase">
                🔍 Diagnostics Pipeline
            </span>
            <h1 class="text-3xl md:text-4xl font-bold font-outfit tracking-tight">
                Evaluating Hardware Capacity
            </h1>
            <p class="text-slate-400 text-sm mt-1">
                Job Session: <span class="font-mono text-xs text-indigo-400 font-semibold">{{ $id }}</span>
            </p>
        </div>

        <!-- Target System Specs Tag Panel -->
        <div class="glass-panel rounded-2xl p-4 mb-6 grid grid-cols-2 md:grid-cols-4 gap-4 text-center text-xs border border-white/5 relative overflow-hidden">
            <div class="border-r border-white/5 last:border-0 pr-2">
                <span class="text-slate-500 block uppercase font-semibold mb-1">Target Game</span>
                <span class="text-slate-200 font-bold block truncate text-sm" title="{{ $specs['game'] }}">🎮 {{ $specs['game'] }}</span>
            </div>
            <div class="border-r border-white/5 last:border-0 px-2">
                <span class="text-slate-500 block uppercase font-semibold mb-1">CPU Spec</span>
                <span class="text-slate-200 font-bold block truncate text-sm" title="{{ $specs['cpu'] }}">💻 {{ $specs['cpu'] }}</span>
            </div>
            <div class="border-r border-white/5 last:border-0 px-2">
                <span class="text-slate-500 block uppercase font-semibold mb-1">GPU Spec</span>
                <span class="text-slate-200 font-bold block truncate text-sm" title="{{ $specs['gpu'] }}">🔌 {{ $specs['gpu'] }}</span>
            </div>
            <div class="last:border-0 pl-2">
                <span class="text-slate-500 block uppercase font-semibold mb-1">System RAM</span>
                <span class="text-slate-200 font-bold block text-sm">💾 {{ $specs['ram'] }}</span>
            </div>
        </div>

        <!-- Processing Status / Verdict Area -->
        <div class="mb-6">
            <!-- Processing Layout -->
            <div x-show="status === 'processing'" class="glass-panel rounded-2xl p-6 text-center border-indigo-500/10">
                <div class="flex items-center justify-center gap-3 mb-3">
                    <span class="flex h-3 w-3 relative">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-indigo-500"></span>
                    </span>
                    <span class="text-sm font-semibold tracking-widest text-indigo-400 font-outfit uppercase pulse-slow">
                        Analyzing System Architecture & Requirements...
                    </span>
                </div>
                
                <!-- Animated Progress Bar -->
                <div class="w-full bg-slate-900/60 rounded-full h-2 overflow-hidden border border-white/5 mt-4">
                    <div class="bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 h-full rounded-full animate-[progress_15s_ease-out-in_infinite]" 
                         :style="'width: ' + (logs.length * 10) + '%'"></div>
                </div>
            </div>

            <!-- Verdict Reveal Layout (Completed State) -->
            <div x-show="status === 'completed'" 
                 x-transition:enter="transition ease-out duration-700 transform"
                 x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 class="glass-panel rounded-3xl p-6 md:p-8 border relative overflow-hidden"
                 :class="verdict === 'BUY' ? 'glow-border-green' : 'glow-border-red'">
                
                <div class="absolute -right-24 -bottom-24 w-48 h-48 rounded-full blur-3xl opacity-20"
                     :class="verdict === 'BUY' ? 'bg-emerald-500' : 'bg-red-500'"></div>

                <div class="flex flex-col md:flex-row items-center gap-6">
                    <!-- Verdict Badge -->
                    <div class="flex-shrink-0">
                        <template x-if="verdict === 'BUY'">
                            <div class="w-28 h-28 md:w-32 md:h-32 rounded-full border-4 border-emerald-500/30 flex flex-col items-center justify-center bg-emerald-500/10 shadow-lg shadow-emerald-500/20">
                                <span class="text-xs uppercase tracking-widest font-semibold text-emerald-400">Verdict</span>
                                <span class="text-3xl md:text-4xl font-extrabold font-outfit text-emerald-400 text-glow-green tracking-wide">BUY</span>
                                <span class="text-xl mt-1">✨</span>
                            </div>
                        </template>
                        <template x-if="verdict === 'WAIT'">
                            <div class="w-28 h-28 md:w-32 md:h-32 rounded-full border-4 border-red-500/30 flex flex-col items-center justify-center bg-red-500/10 shadow-lg shadow-red-500/20">
                                <span class="text-xs uppercase tracking-widest font-semibold text-red-400">Verdict</span>
                                <span class="text-3xl md:text-4xl font-extrabold font-outfit text-red-400 text-glow-red tracking-wide">WAIT</span>
                                <span class="text-xl mt-1">⚠️</span>
                            </div>
                        </template>
                    </div>

                    <!-- Summary details -->
                    <div class="flex-grow text-center md:text-left">
                        <h3 class="text-lg md:text-xl font-bold font-outfit mb-2"
                            :class="verdict === 'BUY' ? 'text-emerald-300' : 'text-red-300'">
                            Recommendation Summary
                        </h3>
                        <p class="text-slate-300 text-sm md:text-base font-light leading-relaxed" style="white-space: pre-line;" x-text="summary"></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Terminal Panel - Agent Thought Process -->
        <div class="glass-panel rounded-2xl overflow-hidden shadow-2xl flex flex-col border border-white/5">
            <!-- Window Header -->
            <div class="bg-slate-900/90 px-4 py-3 flex items-center justify-between border-b border-white/5 select-none">
                <div class="flex items-center gap-2">
                    <span class="w-3 w-3 h-3 rounded-full bg-red-500/80 block"></span>
                    <span class="w-3 w-3 h-3 rounded-full bg-yellow-500/80 block"></span>
                    <span class="w-3 w-3 h-3 rounded-full bg-green-500/80 block"></span>
                </div>
                <span class="text-xs tracking-wider text-slate-400 uppercase font-mono font-medium">Agent Thought Process</span>
                <span class="text-xs text-slate-500 font-mono">mcp_client.log</span>
            </div>

            <!-- Terminal Content Box -->
            <div id="log-container" class="terminal-box h-72 overflow-y-auto p-4 font-mono text-xs md:text-sm text-slate-300 space-y-2.5 leading-relaxed scroll-smooth scrollbar-thin scrollbar-thumb-slate-800">
                <template x-for="(log, index) in logs" :key="index">
                    <div class="log-item flex gap-2">
                        <span class="text-indigo-500/80 select-none">&gt;</span>
                        <span x-text="log" :class="{
                            'text-emerald-400': log.includes('[Agent] Diagnostics complete') || log.includes('BUY'),
                            'text-yellow-400/90': log.includes('[Agent] Running final') || log.includes('WAIT'),
                            'text-indigo-400': log.includes('[Diagnose]')
                        }"></span>
                    </div>
                </template>
                <div x-show="logs.length === 0" class="text-slate-500 animate-pulse py-2 italic">
                    Establishing handshakes with agent pipeline...
                </div>
            </div>
        </div>

        <!-- Dashboard Action Controls -->
        <div class="mt-6 flex justify-center gap-4">
            <a href="{{ route('agent.index') }}" class="px-6 py-3.5 rounded-xl font-bold bg-slate-900 border border-white/10 text-slate-300 hover:text-white hover:bg-slate-800/80 transition duration-150 flex items-center gap-2 text-sm uppercase tracking-wide">
                <span>↩ New Diagnostics Scan</span>
            </a>
        </div>
    </div>

    <!-- Alpine.js Diagnostics Poll Controller -->
    <script>
        function diagnosticsHandler(jobId) {
            return {
                id: jobId,
                status: 'processing',
                verdict: null,
                summary: null,
                logs: [],
                pollingInterval: null,

                startPolling() {
                    // Poll immediately on load
                    this.poll();
                    
                    // Setup interval to poll status every 2 seconds
                    this.pollingInterval = setInterval(() => {
                        this.poll();
                    }, 2000);
                },

                async poll() {
                    try {
                        const response = await fetch(`/check-status/${this.id}`, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            }
                        });

                        if (!response.ok) {
                            throw new Error('Unsuccessful status fetch');
                        }

                        const data = await response.json();

                        // Append logs incrementally to prevent DOM replacement flickering
                        if (data.logs && Array.isArray(data.logs)) {
                            data.logs.forEach(line => {
                                if (!this.logs.includes(line)) {
                                    this.logs.push(line);
                                    // Scroll terminal container to the bottom after Alpine updates DOM
                                    this.$nextTick(() => {
                                        this.scrollTerminal();
                                    });
                                }
                            });
                        }

                        if (data.status === 'completed') {
                            clearInterval(this.pollingInterval);
                            this.status = 'completed';
                            this.verdict = data.verdict;
                            this.summary = data.summary;
                        } else if (data.status === 'error') {
                            clearInterval(this.pollingInterval);
                            this.status = 'error';
                            alert('Diagnostic Error: ' + data.message);
                        }

                    } catch (err) {
                        console.warn('Network issue encountered during polling:', err.message);
                    }
                },

                scrollTerminal() {
                    const container = document.getElementById('log-container');
                    if (container) {
                        container.scrollTop = container.scrollHeight;
                    }
                }
            }
        }
    </script>
</body>
</html>
