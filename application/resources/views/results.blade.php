<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>REVIEW_ANALYSIS — Buy or Wait</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geist+Mono:wght@300;400;500&family=Inter:wght@300;400;500&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Alpine.js CDN -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

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
        .b-label {
            font-family: 'Geist Mono', monospace;
            text-transform: uppercase;
            letter-spacing: 1.4px;
            font-size: 10px;
            color: var(--text-disabled);
        }
        .b-btn-ghost {
            font-family: 'Geist Mono', monospace;
            text-transform: uppercase;
            letter-spacing: 1.4px;
            border: 1px solid var(--border);
            color: var(--text-secondary);
            transition: all 0.15s ease;
            cursor: pointer;
        }
        .b-btn-ghost:hover { opacity: 0.5; border-color: var(--border-strong); }
        .terminal-box {
            background: rgba(0, 0, 0, 0.25);
            border-top: 1px solid var(--border);
        }
        .blink {
            animation: blink 1.2s steps(2, start) infinite;
        }
        @keyframes blink { to { visibility: hidden; } }
        .log-item {
            animation: slideIn 0.25s ease-out forwards;
        }
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(4px); }
            to { opacity: 1; transform: translateY(0); }
        }
        [x-cloak] { display: none !important; }
        .summary-md strong { color: #ffffff; font-weight: 500; }
        .summary-md ul { list-style: none; padding-left: 0; }
        .summary-md li { padding-left: 1.25rem; position: relative; }
        .summary-md li::before { content: '>'; position: absolute; left: 0; color: rgba(255,255,255,0.3); font-family: 'Geist Mono', monospace; }
    </style>
</head>
<body class="font-sans flex flex-col items-center justify-center p-4 md:p-8 selection:bg-white selection:text-[#1f2228]">

    <div class="w-full max-w-3xl" x-data="diagnosticsHandler('{{ $id }}')" x-init="startPolling()" x-cloak>
        <!-- Header Info -->
        <div class="mb-8">
            <div class="inline-flex items-center gap-2 border border-[rgba(255,255,255,0.1)] px-3 py-1.5 mb-6">
                <svg class="w-3.5 h-3.5 text-white/50" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
                <span class="font-mono text-[10px] uppercase tracking-[1.4px] text-white/50">Review Analysis</span>
            </div>
            <h1 class="font-mono font-light text-4xl md:text-5xl tracking-tight mb-3">
                READING<span class="text-white/30">_</span>THE<span class="text-white/30">_</span>REVIEWS
            </h1>
            <p class="font-mono text-xs text-white/30 uppercase tracking-[1.4px]">
                Session: <span class="text-white/70">{{ $id }}</span>
            </p>
        </div>

        <!-- Request Summary Panel -->
        <div class="b-panel mb-6 grid grid-cols-1 md:grid-cols-3">
            <div class="p-4 border-b md:border-b-0 md:border-r border-[rgba(255,255,255,0.1)]">
                <span class="b-label block mb-1.5">Game</span>
                <span class="text-white/70 text-sm block truncate" title="{{ $specs['game'] }}">{{ $specs['game'] }}</span>
            </div>
            <div class="p-4 border-b md:border-b-0 md:border-r border-[rgba(255,255,255,0.1)]">
                <span class="b-label block mb-1.5">Your Priorities</span>
                <span class="text-white/70 text-sm block truncate" title="{{ $specs['priorities'] ?? '' }}">{{ ($specs['priorities'] ?? '') !== '' ? $specs['priorities'] : '—' }}</span>
            </div>
            <div class="p-4">
                <span class="b-label block mb-1.5">Your Concerns</span>
                <span class="text-white/70 text-sm block truncate" title="{{ $specs['concerns'] ?? '' }}">{{ ($specs['concerns'] ?? '') !== '' ? $specs['concerns'] : '—' }}</span>
            </div>
        </div>

        <!-- Processing Status / Verdict Area -->
        <div class="mb-6">
            <!-- Processing Layout -->
            <div x-show="status === 'processing'" class="b-panel p-6">
                <div class="flex items-center gap-3 mb-5">
                    <span class="w-2 h-2 bg-white blink"></span>
                    <span class="font-mono text-xs uppercase tracking-[1.4px] text-white/70">
                        Reading recent Steam reviews &amp; patch notes...
                    </span>
                </div>
                <!-- Progress Bar -->
                <div class="w-full h-1 bg-[rgba(255,255,255,0.05)] border border-[rgba(255,255,255,0.1)] overflow-hidden">
                    <div class="bg-white h-full transition-all duration-700"
                         :style="'width: ' + Math.min(logs.length * 10, 95) + '%'"></div>
                </div>
            </div>

            <!-- Verdict Reveal Layout (Completed State) -->
            <div x-show="status === 'completed'"
                 x-transition:enter="transition ease-out duration-500 transform"
                 x-transition:enter-start="opacity-0 translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="b-panel p-6 md:p-8"
                 :class="verdict === 'BUY' ? 'border-[rgba(255,255,255,0.2)]' : ''">

                <div class="flex flex-col md:flex-row items-start md:items-center gap-6 md:gap-8">
                    <!-- Verdict Badge -->
                    <div class="flex-shrink-0 border px-8 py-6 text-center"
                         :class="{
                             'border-white bg-white text-[#1f2228]': verdict === 'BUY',
                             'border-[rgba(255,255,255,0.2)] text-white': verdict === 'WAIT',
                             'border-[rgba(255,255,255,0.2)] border-dashed text-white/40': verdict === 'AVOID'
                         }">
                        <span class="font-mono text-[10px] uppercase tracking-[1.4px] block mb-1 opacity-50">Verdict</span>
                        <span class="font-mono font-light text-4xl md:text-5xl tracking-tight" x-text="verdict"></span>
                    </div>

                    <!-- Summary details -->
                    <div class="flex-grow">
                        <h3 class="font-mono text-xs uppercase tracking-[1.4px] text-white/50 mb-3">
                            What the reviews say
                        </h3>
                        <div class="text-white/70 text-sm md:text-base font-light leading-relaxed space-y-2 summary-md" x-html="renderMarkdown(summary)"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Terminal Panel - Agent Thought Process -->
        <div class="b-panel flex flex-col">
            <!-- Window Header -->
            <div class="px-4 py-3 flex items-center justify-between select-none">
                <div class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 border border-[rgba(255,255,255,0.3)] block"></span>
                    <span class="w-2.5 h-2.5 border border-[rgba(255,255,255,0.3)] block"></span>
                    <span class="w-2.5 h-2.5 border border-[rgba(255,255,255,0.3)] block"></span>
                </div>
                <span class="font-mono text-[10px] uppercase tracking-[1.4px] text-white/50">Agent Thought Process</span>
                <span class="font-mono text-[10px] text-white/30">mcp_client.log</span>
            </div>

            <!-- Terminal Content Box -->
            <div id="log-container" class="terminal-box h-72 overflow-y-auto p-4 font-mono text-xs md:text-sm text-white/70 space-y-2.5 leading-relaxed scroll-smooth">
                <template x-for="(log, index) in logs" :key="index">
                    <div class="log-item flex gap-2">
                        <span class="text-white/30 select-none">&gt;</span>
                        <span x-text="log" :class="{
                            'text-white': log.includes('[Agent] Diagnostics complete') || log.includes('BUY'),
                            'text-white/90': log.includes('[Agent] Running final') || log.includes('WAIT'),
                            'text-white/50': log.includes('[Diagnose]')
                        }"></span>
                    </div>
                </template>
                <div x-show="logs.length === 0" class="text-white/30 py-2">
                    Establishing handshakes with agent pipeline...<span class="blink">_</span>
                </div>
            </div>
        </div>

        <!-- Dashboard Action Controls -->
        <div class="mt-6">
            <a href="{{ route('agent.index') }}" class="b-btn-ghost inline-flex items-center gap-2 px-6 py-3.5 text-xs">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3" />
                </svg>
                <span>Check Another Game</span>
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
                    this.poll();
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

                        if (data.logs && Array.isArray(data.logs)) {
                            data.logs.forEach(line => {
                                if (!this.logs.includes(line)) {
                                    this.logs.push(line);
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

                renderMarkdown(text) {
                    if (!text) return '';
                    // Escape HTML first so review/LLM text can't inject markup
                    const esc = text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                    const lines = esc.split(/\r?\n/);
                    let html = '', inList = false;
                    for (let line of lines) {
                        line = line.trim();
                        // Inline: **bold**, *italic*, ### headings stripped to bold
                        line = line.replace(/^#{1,6}\s+(.*)$/, '<strong>$1</strong>')
                                   .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
                                   .replace(/(^|\s)\*([^*]+)\*(?=\s|$|[.,;:])/g, '$1<em>$2</em>');
                        if (/^[-*]\s+/.test(line)) {
                            if (!inList) { html += '<ul>'; inList = true; }
                            html += '<li>' + line.replace(/^[-*]\s+/, '') + '</li>';
                        } else {
                            if (inList) { html += '</ul>'; inList = false; }
                            if (line) html += '<p>' + line + '</p>';
                        }
                    }
                    if (inList) html += '</ul>';
                    return html;
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
