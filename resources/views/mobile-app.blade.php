<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Mobile App - iCard Maker</title>
        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:300,400,500,600,700,800&display=swap" rel="stylesheet" />
        <!-- Styles -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="antialiased font-sans bg-slate-950 text-white min-h-screen flex flex-col justify-between selection:bg-amber-500 selection:text-slate-950">
        <!-- Glow effects -->
        <div class="absolute top-0 left-1/4 w-96 h-96 bg-indigo-900/20 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute top-40 right-1/4 w-96 h-96 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <!-- Header -->
        <!-- Header -->
        <header class="sticky top-0 z-50 backdrop-blur-md bg-slate-950/80 border-b border-slate-900 w-full px-4 py-4">
            <div class="max-w-7xl mx-auto flex flex-col lg:flex-row justify-between items-center gap-4">
                <div class="flex items-center justify-between w-full lg:w-auto">
                    <a href="/" class="flex items-center space-x-2 hover:opacity-90 transition duration-200 shrink-0">
                        <img src="{{ asset('images/logo.png') }}" class="h-8 w-auto shrink-0" alt="iCard Maker Logo">
                        <span class="text-lg font-bold tracking-tight bg-gradient-to-r from-white via-slate-200 to-amber-400 bg-clip-text text-transparent whitespace-nowrap">iCard Maker</span>
                    </a>
                    
                    <!-- Hamburger Button for Mobile -->
                    <button id="mobileMenuToggle" class="text-slate-300 hover:text-white focus:outline-none p-1 lg:hidden">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path id="hamburgerIcon" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            <path id="closeIcon" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Navigation Links -->
                <nav id="navMenu" class="hidden lg:flex flex-col lg:flex-row items-center gap-4 w-full lg:w-auto mt-4 lg:mt-0 border-t border-slate-900 lg:border-none pt-4 lg:pt-0">
                    <a href="/features" class="text-sm font-semibold text-slate-300 hover:text-amber-400 transition duration-200 w-full lg:w-auto text-center py-2 lg:py-0">Features</a>
                    <a href="/how-it-works" class="text-sm font-semibold text-slate-300 hover:text-amber-400 transition duration-200 w-full lg:w-auto text-center py-2 lg:py-0">How It Works</a>
                    
                    <!-- Solutions Dropdown -->
                    <div class="relative group/solutions w-full lg:w-auto text-center lg:text-left">
                        <button type="button" class="dropdown-trigger flex items-center justify-center lg:justify-start gap-1 mx-auto lg:mx-0 text-sm font-semibold text-slate-300 hover:text-amber-400 focus:outline-none transition duration-200 w-full lg:w-auto py-2 lg:py-0">
                            <span>Solutions</span>
                            <svg class="dropdown-arrow w-4 h-4 transition-transform duration-200 group-hover/solutions:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div class="dropdown-menu hidden group-hover/solutions:block lg:absolute lg:left-1/2 lg:-translate-x-1/2 lg:top-full lg:mt-2 w-full lg:w-48 bg-slate-950 lg:border lg:border-slate-900 rounded-xl py-2 lg:shadow-xl z-50">
                            <a href="/for-schools" class="block px-4 py-2 text-sm text-slate-300 hover:text-amber-400 hover:bg-slate-900 transition duration-150 text-center lg:text-left">For Schools</a>
                            <a href="/for-printing-companies" class="block px-4 py-2 text-sm text-slate-300 hover:text-amber-400 hover:bg-slate-900 transition duration-150 text-center lg:text-left">For Printing Companies</a>
                        </div>
                    </div>

                    <a href="/mobile-app" class="text-sm font-semibold text-amber-400 hover:text-amber-350 transition duration-200 w-full lg:w-auto text-center py-2 lg:py-0">Mobile App</a>
                    <a href="/pricing" class="text-sm font-semibold text-slate-300 hover:text-amber-400 transition duration-200 w-full lg:w-auto text-center py-2 lg:py-0">Pricing</a>
                    <a href="/contact" class="text-sm font-semibold text-slate-300 hover:text-amber-400 transition duration-200 w-full lg:w-auto text-center py-2 lg:py-0">Contact</a>
                    
                    <!-- Auth Actions on Mobile (hidden on desktop) -->
                    <div class="flex flex-col items-center gap-3 w-full border-t border-slate-900 pt-4 mt-2 lg:hidden">
                        @if (Route::has('login'))
                            @auth
                                <a href="{{ url('/dashboard') }}" class="text-sm font-semibold text-amber-400 hover:text-amber-300 transition duration-200">Dashboard</a>
                            @else
                                <a href="{{ route('login') }}" class="text-sm font-semibold text-slate-300 hover:text-white transition duration-200">Log in</a>
                                @if (Route::has('register'))
                                    <a href="{{ route('register') }}" class="inline-flex items-center justify-center w-full max-w-[200px] py-2 text-sm font-bold text-slate-950 bg-gradient-to-r from-amber-400 to-amber-500 rounded-lg">Register</a>
                                @endif
                            @endauth
                        @endif
                    </div>
                </nav>

                <!-- Auth / Call to Action on Desktop -->
                <div class="hidden lg:flex items-center space-x-4 shrink-0">
                    @if (Route::has('login'))
                        @auth
                            <a href="{{ url('/dashboard') }}" class="text-sm font-semibold text-amber-400 hover:text-amber-300 transition duration-200">Dashboard</a>
                        @else
                            <a href="{{ route('login') }}" class="text-sm font-semibold text-slate-300 hover:text-white transition duration-200">Log in</a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="inline-flex items-center justify-center px-4 py-2 text-sm font-bold text-slate-950 bg-gradient-to-r from-amber-400 to-amber-500 hover:from-amber-300 hover:to-amber-400 rounded-xl transition duration-200 shadow-md shadow-amber-500/10">Register</a>
                            @endif
                        @endauth
                    @endif
                </div>
            </div>

            <script>
                document.getElementById('mobileMenuToggle').addEventListener('click', function() {
                    const navMenu = document.getElementById('navMenu');
                    const hamburgerIcon = document.getElementById('hamburgerIcon');
                    const closeIcon = document.getElementById('closeIcon');
                    
                    if (navMenu.classList.contains('hidden')) {
                        navMenu.classList.remove('hidden');
                        navMenu.classList.add('flex');
                        hamburgerIcon.classList.add('hidden');
                        closeIcon.classList.remove('hidden');
                    } else {
                        navMenu.classList.add('hidden');
                        navMenu.classList.remove('flex');
                        hamburgerIcon.classList.remove('hidden');
                        closeIcon.classList.add('hidden');
                    }
                });

                // Toggle solutions dropdown on mobile click
                document.querySelector('.dropdown-trigger').addEventListener('click', function(e) {
                    if (window.innerWidth < 1024) {
                        const dropdownMenu = this.nextElementSibling;
                        const dropdownArrow = this.querySelector('.dropdown-arrow');
                        dropdownMenu.classList.toggle('hidden');
                        dropdownArrow.classList.toggle('rotate-180');
                    }
                });
            </script>
        </header>

        <!-- Main Content -->
        <main class="relative z-10 max-w-7xl mx-auto w-full px-6 py-16 flex-grow">
            <!-- Hero Header -->
            <div class="max-w-4xl mx-auto space-y-6 text-center mb-24">
                <span class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-slate-900 border border-slate-800 text-xs font-semibold tracking-wide text-amber-400">
                    📱 On-The-Go Registration Portal
                </span>
                <h1 class="text-4xl sm:text-6xl font-black tracking-tight leading-tight text-white">
                    Student Data Collection <br class="hidden sm:inline" />Made Easy With <span class="bg-gradient-to-r from-amber-400 via-amber-500 to-orange-400 bg-clip-text text-transparent">Mobile</span>
                </h1>
                <p class="text-lg sm:text-xl text-slate-400 leading-relaxed max-w-2xl mx-auto">
                    Give parents a simple way to submit student information, photographs and other required details from their phones.
                </p>
                <div class="pt-4 flex flex-col sm:flex-row items-center justify-center gap-4">
                    <a href="https://play.google.com/store/apps/details?id=com.infoleena.icard.maker&hl=en_IN" target="_blank" class="inline-flex items-center space-x-3 px-6 py-3 rounded-2xl bg-black border border-slate-800 hover:border-slate-700 transition duration-200 shadow-xl w-full sm:w-auto max-w-xs text-left group">
                        <svg class="w-8 h-8 text-white group-hover:scale-105 transition" viewBox="0 0 512 512" fill="currentColor">
                            <path d="M325.3 234.3L104.6 14l280.8 161.2-60.1 59.1zM47 0C34 6.8 25.3 19.2 25.3 35.3v441.3c0 16.1 8.7 28.5 21.7 35.3l256.6-256L47 0zm425.2 225.6l-58 33.3-60.7-59.7 60.1-59.1 58.6 33.6c24.8 14.2 24.8 37.6 0 51.9zM104.6 498L325.3 277.7l60.7 59.7L104.6 498z" />
                        </svg>
                        <div>
                            <span class="text-[10px] text-slate-400 uppercase tracking-widest font-semibold block leading-none mb-1">Get it on</span>
                            <span class="text-base font-bold text-white leading-none">Google Play</span>
                        </div>
                    </a>
                    <a href="https://apps.apple.com/in/app/icard-maker/id6792461176" target="_blank" class="inline-flex items-center space-x-3 px-6 py-3 rounded-2xl bg-black border border-slate-800 hover:border-slate-700 transition duration-200 shadow-xl w-full sm:w-auto max-w-xs text-left group">
                        <svg class="w-8 h-8 text-white group-hover:scale-105 transition" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M18.71 19.5C17.88 20.74 17 21.95 15.66 21.97C14.32 22 13.89 21.18 12.37 21.18C10.84 21.18 10.37 21.95 9.1 22C7.79 22.05 6.8 20.68 5.96 19.47C4.25 17 2.94 12.45 4.7 9.39C5.57 7.87 7.13 6.91 8.82 6.88C10.1 6.86 11.32 7.75 12.11 7.75C12.89 7.75 14.37 6.68 15.92 6.84C16.57 6.87 18.39 7.1 19.56 8.82C19.47 8.88 17.39 10.1 17.41 12.63C17.44 15.65 20.06 16.66 20.1 16.67C20.08 16.74 19.67 18.11 18.71 19.5M15.97 4.17C16.63 3.37 17.07 2.28 16.95 1C16 1.04 14.9 1.6 14.24 2.38C13.68 3.04 13.19 4.14 13.34 5.39C14.39 5.47 15.4 4.88 15.97 4.17Z" />
                        </svg>
                        <div>
                            <span class="text-[10px] text-slate-400 uppercase tracking-widest font-semibold block leading-none mb-1">Download on the</span>
                            <span class="text-base font-bold text-white leading-none">App Store</span>
                        </div>
                    </a>
                </div>
            </div>

            <!-- How Parents Use the App Section -->
            <section class="max-w-5xl mx-auto mb-16 border-t border-slate-900 pt-16 text-center">
                <div class="space-y-3 mb-16">
                    <span class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-slate-900 border border-slate-800 text-[10px] font-bold tracking-widest text-amber-400 uppercase">
                        Simple Step-by-Step Flow
                    </span>
                    <h2 class="text-2xl sm:text-4xl font-extrabold text-white leading-tight">
                        How Parents Use the App
                    </h2>
                </div>

                <div class="relative bg-slate-900/40 border border-slate-850 rounded-3xl p-8 overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-r from-amber-500/5 to-transparent blur-3xl pointer-events-none"></div>
                    
                    <div class="relative grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6 text-left">
                        <!-- Step 1 -->
                        <div class="p-5 rounded-2xl bg-slate-950/60 border border-slate-900 shadow group flex flex-col justify-between">
                            <div>
                                <span class="flex items-center justify-center w-8 h-8 rounded-xl bg-amber-500/10 border border-amber-500/20 text-xs font-black text-amber-400 mb-4 group-hover:scale-105 transition">1</span>
                                <h3 class="text-sm font-bold text-white mb-1.5">Login</h3>
                                <p class="text-[11px] text-slate-400 leading-relaxed">Parent logs in using authorized mobile number.</p>
                            </div>
                        </div>

                        <!-- Step 2 -->
                        <div class="p-5 rounded-2xl bg-slate-950/60 border border-slate-900 shadow group flex flex-col justify-between">
                            <div>
                                <span class="flex items-center justify-center w-8 h-8 rounded-xl bg-amber-500/10 border border-amber-500/20 text-xs font-black text-amber-400 mb-4 group-hover:scale-105 transition">2</span>
                                <h3 class="text-sm font-bold text-white mb-1.5">Select Student</h3>
                                <p class="text-[11px] text-slate-400 leading-relaxed">Choose student profile from the classroom roster.</p>
                            </div>
                        </div>

                        <!-- Step 3 -->
                        <div class="p-5 rounded-2xl bg-slate-950/60 border border-slate-900 shadow group flex flex-col justify-between">
                            <div>
                                <span class="flex items-center justify-center w-8 h-8 rounded-xl bg-amber-500/10 border border-amber-500/20 text-xs font-black text-amber-400 mb-4 group-hover:scale-105 transition">3</span>
                                <h3 class="text-sm font-bold text-white mb-1.5">Enter Information</h3>
                                <p class="text-[11px] text-slate-400 leading-relaxed">Fill required student demographic details.</p>
                            </div>
                        </div>

                        <!-- Step 4 -->
                        <div class="p-5 rounded-2xl bg-slate-950/60 border border-slate-900 shadow group flex flex-col justify-between">
                            <div>
                                <span class="flex items-center justify-center w-8 h-8 rounded-xl bg-amber-500/10 border border-amber-500/20 text-xs font-black text-amber-400 mb-4 group-hover:scale-105 transition">4</span>
                                <h3 class="text-sm font-bold text-white mb-1.5">Upload Photo</h3>
                                <p class="text-[11px] text-slate-400 leading-relaxed">Take or upload child's clean portrait photograph.</p>
                            </div>
                        </div>

                        <!-- Step 5 -->
                        <div class="p-5 rounded-2xl bg-emerald-950/10 border border-emerald-900/30 shadow group flex flex-col justify-between">
                            <div>
                                <span class="flex items-center justify-center w-8 h-8 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-xs font-black text-emerald-400 mb-4 group-hover:scale-105 transition">5</span>
                                <h3 class="text-sm font-bold text-emerald-400 mb-1.5">Submit</h3>
                                <p class="text-[11px] text-emerald-300 leading-relaxed">Send the verified information package to the school admin.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Benefits Section -->
            <section class="max-w-5xl mx-auto mb-24 border-t border-slate-900 pt-16 text-center">
                <div class="space-y-3 mb-16">
                    <span class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-slate-900 border border-slate-800 text-[10px] font-bold tracking-widest text-amber-400 uppercase">
                        Parent & Admin Benefits
                    </span>
                    <h2 class="text-2xl sm:text-4xl font-extrabold text-white leading-tight">
                        Benefits
                    </h2>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6 text-left">
                    <!-- Benefit 1 -->
                    <div class="p-6 rounded-2xl bg-slate-900/40 border border-slate-850 hover:border-slate-800 transition duration-300 shadow group">
                        <div class="w-9 h-9 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 mb-4 group-hover:scale-105 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                        </div>
                        <h3 class="text-sm font-bold text-white mb-1.5">Mobile-First</h3>
                        <p class="text-[11px] text-slate-400 leading-relaxed">Perfectly optimized for screens of all sizes. No computer setup needed.</p>
                    </div>

                    <!-- Benefit 2 -->
                    <div class="p-6 rounded-2xl bg-slate-900/40 border border-slate-850 hover:border-slate-800 transition duration-300 shadow group">
                        <div class="w-9 h-9 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 mb-4 group-hover:scale-105 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                        <h3 class="text-sm font-bold text-white mb-1.5">Easy to Use</h3>
                        <p class="text-[11px] text-slate-400 leading-relaxed">Friendly forms and automatic image cropping for parents of all tech levels.</p>
                    </div>

                    <!-- Benefit 3 -->
                    <div class="p-6 rounded-2xl bg-slate-900/40 border border-slate-850 hover:border-slate-800 transition duration-300 shadow group">
                        <div class="w-9 h-9 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 mb-4 group-hover:scale-105 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                        </div>
                        <h3 class="text-sm font-bold text-white mb-1.5">Faster Collection</h3>
                        <p class="text-[11px] text-slate-400 leading-relaxed">Gather hundreds of student details and verified images in a couple of days.</p>
                    </div>

                    <!-- Benefit 4 -->
                    <div class="p-6 rounded-2xl bg-slate-900/40 border border-slate-850 hover:border-slate-800 transition duration-300 shadow group">
                        <div class="w-9 h-9 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 mb-4 group-hover:scale-105 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                        </div>
                        <h3 class="text-sm font-bold text-white mb-1.5">Less Paperwork</h3>
                        <p class="text-[11px] text-slate-400 leading-relaxed">No paper forms, physical photos to glue, or hand-written spreadsheets.</p>
                    </div>

                    <!-- Benefit 5 -->
                    <div class="p-6 rounded-2xl bg-slate-900/40 border border-slate-850 hover:border-slate-800 transition duration-300 shadow group">
                        <div class="w-9 h-9 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 mb-4 group-hover:scale-105 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                        </div>
                        <h3 class="text-sm font-bold text-white mb-1.5">Better Data Accuracy</h3>
                        <p class="text-[11px] text-slate-400 leading-relaxed">Parents check and submit their child's spelling directly, avoiding mistakes.</p>
                    </div>
                </div>
            </section>

            <!-- Download CTA Section -->
            <section class="max-w-4xl mx-auto mb-16 text-center border-t border-slate-900 pt-16">
                <div class="space-y-6">
                    <h2 class="text-2xl sm:text-4xl font-extrabold text-white leading-tight">
                        Download the App
                    </h2>
                    <p class="text-slate-400 text-sm max-w-md mx-auto">
                        Get the official iCard app to submit, update, and manage your school profile assets directly from your mobile phone.
                    </p>
                    <div class="pt-4 flex flex-col sm:flex-row items-center justify-center gap-4">
                        <a href="https://play.google.com/store/apps/details?id=com.infoleena.icard.maker&hl=en_IN" target="_blank" class="inline-flex items-center space-x-3 px-6 py-3 rounded-2xl bg-black border border-slate-800 hover:border-slate-700 transition duration-200 shadow-xl w-full sm:w-auto max-w-xs text-left group">
                            <svg class="w-8 h-8 text-white group-hover:scale-105 transition" viewBox="0 0 512 512" fill="currentColor">
                                <path d="M325.3 234.3L104.6 14l280.8 161.2-60.1 59.1zM47 0C34 6.8 25.3 19.2 25.3 35.3v441.3c0 16.1 8.7 28.5 21.7 35.3l256.6-256L47 0zm425.2 225.6l-58 33.3-60.7-59.7 60.1-59.1 58.6 33.6c24.8 14.2 24.8 37.6 0 51.9zM104.6 498L325.3 277.7l60.7 59.7L104.6 498z" />
                            </svg>
                            <div>
                                <span class="text-[10px] text-slate-400 uppercase tracking-widest font-semibold block leading-none mb-1">Get it on</span>
                                <span class="text-base font-bold text-white leading-none">Google Play</span>
                            </div>
                        </a>
                        <a href="https://apps.apple.com/in/app/icard-maker/id6792461176" target="_blank" class="inline-flex items-center space-x-3 px-6 py-3 rounded-2xl bg-black border border-slate-800 hover:border-slate-700 transition duration-200 shadow-xl w-full sm:w-auto max-w-xs text-left group">
                            <svg class="w-8 h-8 text-white group-hover:scale-105 transition" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M18.71 19.5C17.88 20.74 17 21.95 15.66 21.97C14.32 22 13.89 21.18 12.37 21.18C10.84 21.18 10.37 21.95 9.1 22C7.79 22.05 6.8 20.68 5.96 19.47C4.25 17 2.94 12.45 4.7 9.39C5.57 7.87 7.13 6.91 8.82 6.88C10.1 6.86 11.32 7.75 12.11 7.75C12.89 7.75 14.37 6.68 15.92 6.84C16.57 6.87 18.39 7.1 19.56 8.82C19.47 8.88 17.39 10.1 17.41 12.63C17.44 15.65 20.06 16.66 20.1 16.67C20.08 16.74 19.67 18.11 18.71 19.5M15.97 4.17C16.63 3.37 17.07 2.28 16.95 1C16 1.04 14.9 1.6 14.24 2.38C13.68 3.04 13.19 4.14 13.34 5.39C14.39 5.47 15.4 4.88 15.97 4.17Z" />
                            </svg>
                            <div>
                                <span class="text-[10px] text-slate-400 uppercase tracking-widest font-semibold block leading-none mb-1">Download on the</span>
                                <span class="text-base font-bold text-white leading-none">App Store</span>
                            </div>
                        </a>
                    </div>
                </div>
            </section>
        </main>

        <!-- Footer -->
        <footer class="relative z-10 w-full border-t border-slate-900 bg-slate-950/80 backdrop-blur pt-12 pb-8">
            <div class="max-w-7xl mx-auto px-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 pb-8 border-b border-slate-900 text-sm text-slate-400">
                    <!-- Brand Section -->
                    <div class="space-y-3">
                        <div class="flex items-center space-x-2">
                            <img src="{{ asset('images/logo.png') }}" class="h-6 w-auto" alt="Logo">
                            <span class="text-lg font-bold text-white tracking-tight">iCard Maker</span>
                        </div>
                        <p class="text-xs text-slate-500 leading-relaxed max-w-xs">
                            Simplify student profile photo collection and custom printable ID card sheet generation for modern campuses.
                        </p>
                    </div>

                    <!-- Navigation Links -->
                    <div class="space-y-3">
                        <h4 class="text-xs font-bold text-white uppercase tracking-wider">Quick Links</h4>
                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <a href="/" class="hover:text-amber-400 transition duration-200">Home</a>
                            <a href="/features" class="hover:text-amber-400 transition duration-200">Features</a>
                            <a href="/pricing" class="hover:text-amber-400 transition duration-200">Pricing</a>
                            <a href="/contact" class="hover:text-amber-400 transition duration-200">Contact</a>
                            <a href="{{ route('privacy') }}" class="hover:text-amber-400 transition duration-200">Privacy Policy</a>
                        </div>
                    </div>

                    <!-- Contact Details -->
                    <div class="space-y-3">
                        <h4 class="text-xs font-bold text-white uppercase tracking-wider">Support</h4>
                        <p class="text-xs text-slate-500">Need help or customized designs?</p>
                        <a href="mailto:leenaitsolutions@gmail.com" class="text-xs font-semibold text-amber-400 hover:text-amber-300 transition duration-200 block">leenaitsolutions@gmail.com</a>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row justify-between items-center gap-4 pt-8 text-xs text-slate-500">
                    <div>
                        &copy; {{ date('Y') }} iCard Maker. All rights reserved.
                    </div>
                    <div class="flex items-center space-x-1">
                        <span>Design & Developed by</span>
                        <a href="https://leenaitsolutions.in" target="_blank" class="text-slate-400 hover:text-amber-400 font-semibold transition duration-200">leenaitsolutions.in</a>
                    </div>
                </div>
            </div>
        </footer>
    </body>
</html>
