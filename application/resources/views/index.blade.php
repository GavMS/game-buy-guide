<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>AI Hardware Compatibility Checker - Smart Buyer's Guide</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'sans-serif'],
                        outfit: ['Outfit', 'sans-serif'],
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
        .glass-input {
            background: rgba(10, 15, 30, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.08);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .glass-input:focus {
            border-color: rgba(99, 102, 241, 0.6);
            box-shadow: 0 0 15px rgba(99, 102, 241, 0.25);
            background: rgba(10, 15, 30, 0.9);
        }
        .text-glow {
            text-shadow: 0 0 15px rgba(99, 102, 241, 0.5);
        }
        .btn-gradient {
            background: linear-gradient(135deg, #6366f1 0%, #a855f7 50%, #ec4899 100%);
            background-size: 200% auto;
            transition: 0.5s;
        }
        .btn-gradient:hover {
            background-position: right center;
            box-shadow: 0 0 20px rgba(168, 85, 247, 0.4);
        }
        .loader-ring {
            border: 3px solid rgba(255, 255, 255, 0.1);
            border-top: 3px solid #ffffff;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="text-slate-100 flex flex-col items-center justify-center p-4 md:p-8 font-sans selection:bg-indigo-500/30 selection:text-indigo-200">

    <div class="w-full max-w-2xl z-10">
        <!-- Header -->
        <div class="text-center mb-10">
            <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold tracking-wider text-indigo-400 bg-indigo-500/10 border border-indigo-500/20 mb-4 outfit-font uppercase">
                ⚡ AI-Powered Gaming Diagnostics
            </span>
            <h1 class="text-4xl md:text-5xl font-black font-outfit tracking-tight mb-3">
                Should you <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 via-purple-400 to-pink-500 text-glow">BUY</span> or <span class="text-transparent bg-clip-text bg-gradient-to-r from-pink-500 to-amber-500">WAIT</span>?
            </h1>
            <p class="text-slate-400 text-base md:text-lg max-w-lg mx-auto font-light leading-relaxed">
                Enter your target game and system specifications. Our AI agent compiles requirement metrics to deliver a comprehensive purchase recommendation.
            </p>
        </div>

        <!-- Main Form Card -->
        <div id="form-card" class="glass-panel rounded-3xl p-6 md:p-8 relative overflow-hidden transition-all duration-500">
            <!-- Glow background blob inside the card -->
            <div class="absolute -right-16 -top-16 w-32 h-32 bg-indigo-500/10 rounded-full blur-3xl"></div>
            <div class="absolute -left-16 -bottom-16 w-32 h-32 bg-pink-500/10 rounded-full blur-3xl"></div>

            <form id="check-form" class="space-y-6">
                @csrf
                
                <!-- Target Game -->
                <div>
                    <label for="game" class="block text-sm font-semibold tracking-wide text-slate-300 mb-2 uppercase font-outfit">Target Game</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-500 pointer-events-none">
                            🎮
                        </span>
                        <input type="text" id="game" name="game" required placeholder="e.g., Cyberpunk 2077" 
                            class="w-full glass-input rounded-xl py-3.5 pl-11 pr-4 text-slate-100 placeholder-slate-500 outline-none text-base focus:ring-0">
                    </div>
                </div>

                <!-- Specs Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- CPU Specification -->
                    <div>
                        <label for="cpu" class="block text-sm font-semibold tracking-wide text-slate-300 mb-2 uppercase font-outfit">CPU Spec</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-500 pointer-events-none">
                                💻
                            </span>
                            <input type="text" id="cpu" name="cpu" list="cpu-suggestions" required placeholder="e.g., AMD Ryzen 7 7800X3D" 
                                class="w-full glass-input rounded-xl py-3.5 pl-11 pr-4 text-slate-100 placeholder-slate-500 outline-none text-base focus:ring-0">
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
                    </div>

                    <!-- GPU Specification -->
                    <div>
                        <label for="gpu" class="block text-sm font-semibold tracking-wide text-slate-300 mb-2 uppercase font-outfit">GPU Spec</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-500 pointer-events-none">
                                🔌
                            </span>
                            <input type="text" id="gpu" name="gpu" list="gpu-suggestions" required placeholder="e.g., NVIDIA RTX 4070" 
                                class="w-full glass-input rounded-xl py-3.5 pl-11 pr-4 text-slate-100 placeholder-slate-500 outline-none text-base focus:ring-0">
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
                </div>

                <!-- RAM Selection -->
                <div>
                    <label for="ram" class="block text-sm font-semibold tracking-wide text-slate-300 mb-2 uppercase font-outfit">System RAM Capacity</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-500 pointer-events-none">
                            💾
                        </span>
                        <select id="ram" name="ram" required 
                            class="w-full glass-input rounded-xl py-3.5 pl-11 pr-4 text-slate-100 outline-none text-base focus:ring-0 appearance-none cursor-pointer">
                            <option value="" disabled selected class="bg-slate-900 text-slate-400">Select RAM size...</option>
                            <option value="8GB" class="bg-slate-900 text-slate-100">8 GB DDR4 / DDR5 (Basic)</option>
                            <option value="16GB" class="bg-slate-900 text-slate-100">16 GB DDR4 / DDR5 (Recommended)</option>
                            <option value="32GB" class="bg-slate-900 text-slate-100">32 GB DDR5 (Performance)</option>
                            <option value="64GB" class="bg-slate-900 text-slate-100">64 GB DDR5 (Enthusiast)</option>
                        </select>
                        <span class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-slate-400 text-xs">
                            ▼
                        </span>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" id="submit-btn" class="w-full btn-gradient py-4 rounded-xl font-bold tracking-wider uppercase font-outfit text-white shadow-lg shadow-indigo-500/20 hover:shadow-indigo-500/35 active:scale-[0.98] transition duration-150 flex items-center justify-center gap-3">
                    <span>Initialize AI Diagnostic Check</span>
                    <div id="loader" class="loader-ring hidden"></div>
                </button>
            </form>
        </div>
        
        <!-- Footer -->
        <p class="text-center text-slate-600 text-xs mt-8">
            Smart Buyer's Guide &copy; 2026. Powered by Laravel & Model Context Protocol routing.
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
                
                // Show loading state
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
                        // Smoothly transition card out before redirecting
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
                    // Reset button
                    submitBtn.disabled = false;
                    btnText.textContent = 'Initialize AI Diagnostic Check';
                    loader.classList.add('hidden');
                }
            });
        });
    </script>
</body>
</html>
