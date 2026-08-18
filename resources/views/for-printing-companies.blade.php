<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>For Printing Companies - iCard Maker</title>
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
                        <button type="button" class="dropdown-trigger flex items-center justify-center lg:justify-start gap-1 mx-auto lg:mx-0 text-sm font-semibold text-amber-400 hover:text-amber-300 focus:outline-none transition duration-200 w-full lg:w-auto py-2 lg:py-0">
                            <span>Solutions</span>
                            <svg class="dropdown-arrow w-4 h-4 transition-transform duration-200 group-hover/solutions:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div class="dropdown-menu hidden group-hover/solutions:block lg:absolute lg:left-1/2 lg:-translate-x-1/2 lg:top-full lg:mt-2 w-full lg:w-48 bg-slate-950 lg:border lg:border-slate-900 rounded-xl py-2 lg:shadow-xl z-50">
                            <a href="/for-schools" class="block px-4 py-2 text-sm text-slate-300 hover:text-amber-400 hover:bg-slate-900 transition duration-150 text-center lg:text-left">For Schools</a>
                            <a href="/for-printing-companies" class="block px-4 py-2 text-sm text-amber-400 hover:text-amber-300 hover:bg-slate-900 transition duration-150 text-center lg:text-left">For Printing Companies</a>
                        </div>
                    </div>

                    <a href="/mobile-app" class="text-sm font-semibold text-slate-300 hover:text-amber-400 transition duration-200 w-full lg:w-auto text-center py-2 lg:py-0">Mobile App</a>
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
                                    <a href="{{ route('register') }}" class="inline-flex items-center justify-center w-full max-w-[200px] py-2 text-sm font-bold text-slate-955 bg-gradient-to-r from-amber-400 to-amber-500 rounded-lg">Register</a>
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
                    🖨️ For Professional Printing Partners
                </span>
                <h1 class="text-4xl sm:text-6xl font-black tracking-tight leading-tight text-white">
                    Turn School ID Card Printing <br class="hidden sm:inline" />Into a <span class="bg-gradient-to-r from-amber-400 via-amber-500 to-orange-400 bg-clip-text text-transparent">Digital Workflow</span>
                </h1>
                <p class="text-lg sm:text-xl text-slate-400 leading-relaxed max-w-2xl mx-auto">
                    Get organized student data, verified information and print-ready ID cards without endless corrections.
                </p>
                <div class="pt-4 flex flex-col sm:flex-row items-center justify-center gap-4">
                    <a href="/contact" class="inline-flex items-center justify-center px-6 py-3 text-base font-bold text-slate-950 bg-gradient-to-r from-amber-400 to-amber-500 hover:from-amber-300 hover:to-amber-400 rounded-xl transition duration-200 shadow-lg shadow-amber-500/20">
                        Partner With iCard
                    </a>
                    <a href="/features" class="inline-flex items-center justify-center px-6 py-3 text-base font-bold text-white bg-slate-900/60 border border-slate-800 hover:bg-slate-850 rounded-xl transition duration-200">
                        Explore Features
                    </a>
                </div>
            </div>

            <!-- Problem Section -->
            <section class="max-w-5xl mx-auto mb-24 border-t border-slate-900 pt-16 text-center">
                <div class="space-y-3 mb-12">
                    <span class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-red-950/40 border border-red-900/30 text-[10px] font-bold tracking-widest text-red-400 uppercase">
                        The Messy Traditional Way
                    </span>
                    <h2 class="text-2xl sm:text-4xl font-extrabold text-white leading-tight">
                        Problem
                    </h2>
                    <p class="text-sm text-slate-400 max-w-lg mx-auto">
                        Printing companies typically waste hours dealing with unorganized and fragmented media files.
                    </p>
                </div>
                
                <!-- Grid of Problem Attributes with Icons -->
                <div class="relative bg-slate-900/40 border border-slate-850 rounded-3xl p-8 max-w-4xl mx-auto overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-r from-red-500/5 to-transparent blur-3xl pointer-events-none"></div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 text-left">
                        <!-- Card 1 -->
                        <div class="p-5 rounded-2xl bg-slate-950/60 border border-slate-900 flex items-start space-x-3.5">
                            <span class="flex items-center justify-center w-6 h-6 rounded-lg bg-red-950/45 border border-red-900/30 text-red-400 text-xs font-bold font-sans">&times;</span>
                            <div>
                                <h4 class="text-xs font-bold text-white mb-1">WhatsApp Photos</h4>
                                <p class="text-[11px] text-slate-400 leading-relaxed">Compressed, low-resolution images received in random chat groups.</p>
                            </div>
                        </div>
                        <!-- Card 2 -->
                        <div class="p-5 rounded-2xl bg-slate-950/60 border border-slate-900 flex items-start space-x-3.5">
                            <span class="flex items-center justify-center w-6 h-6 rounded-lg bg-red-950/45 border border-red-900/30 text-red-400 text-xs font-bold font-sans">&times;</span>
                            <div>
                                <h4 class="text-xs font-bold text-white mb-1">Excel Files</h4>
                                <p class="text-[11px] text-slate-400 leading-relaxed">Messy rows, formatting conflicts, and outdated student names.</p>
                            </div>
                        </div>
                        <!-- Card 3 -->
                        <div class="p-5 rounded-2xl bg-slate-950/60 border border-slate-900 flex items-start space-x-3.5">
                            <span class="flex items-center justify-center w-6 h-6 rounded-lg bg-red-950/45 border border-red-900/30 text-red-400 text-xs font-bold font-sans">&times;</span>
                            <div>
                                <h4 class="text-xs font-bold text-white mb-1">Different Formats</h4>
                                <p class="text-[11px] text-slate-400 leading-relaxed">Different filenames, crop ratios, sizes, and file extensions.</p>
                            </div>
                        </div>
                        <!-- Card 4 -->
                        <div class="p-5 rounded-2xl bg-slate-950/60 border border-slate-900 flex items-start space-x-3.5">
                            <span class="flex items-center justify-center w-6 h-6 rounded-lg bg-red-950/45 border border-red-900/30 text-red-400 text-xs font-bold font-sans">&times;</span>
                            <div>
                                <h4 class="text-xs font-bold text-white mb-1">Missing Information</h4>
                                <p class="text-[11px] text-slate-400 leading-relaxed">Incomplete phone numbers, missing roll numbers, or placeholder emails.</p>
                            </div>
                        </div>
                        <!-- Card 5 -->
                        <div class="p-5 rounded-2xl bg-slate-950/60 border border-slate-900 flex items-start space-x-3.5">
                            <span class="flex items-center justify-center w-6 h-6 rounded-lg bg-red-950/45 border border-red-900/30 text-red-400 text-xs font-bold font-sans">&times;</span>
                            <div>
                                <h4 class="text-xs font-bold text-white mb-1">Wrong Names</h4>
                                <p class="text-[11px] text-slate-400 leading-relaxed">Spelling errors and typos that lead to direct printing losses.</p>
                            </div>
                        </div>
                        <!-- Card 6 -->
                        <div class="p-5 rounded-2xl bg-slate-950/60 border border-slate-900 flex items-start space-x-3.5">
                            <span class="flex items-center justify-center w-6 h-6 rounded-lg bg-red-950/45 border border-red-900/30 text-red-400 text-xs font-bold font-sans">&times;</span>
                            <div>
                                <h4 class="text-xs font-bold text-white mb-1">Repeated Corrections</h4>
                                <p class="text-[11px] text-slate-400 leading-relaxed">Back-and-forth phone calls and delayed deliveries for weeks.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- iCard Solution Section -->
            <section class="max-w-7xl mx-auto mb-16 border-t border-slate-900 pt-16 text-center">
                <div class="space-y-3 mb-12">
                    <span class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-emerald-950/40 border border-emerald-900/30 text-[10px] font-bold tracking-widest text-emerald-400 uppercase">
                        Our Solution
                    </span>
                    <h2 class="text-2xl sm:text-4xl font-extrabold text-white leading-tight">
                        iCard Solution
                    </h2>
                </div>
                
                <!-- Horizontal flow layout for desktop, vertical on mobile -->
                <div class="relative bg-slate-900/40 border border-slate-850 rounded-3xl p-6 md:p-8 overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-r from-emerald-500/5 to-transparent blur-3xl pointer-events-none"></div>
                    
                    <div class="relative flex flex-col xl:flex-row items-stretch justify-between gap-6 xl:gap-2">
                        <!-- Step 1 -->
                        <div class="flex-1 flex flex-col items-center p-4 bg-slate-950/60 rounded-2xl border border-slate-900 shadow-sm text-center">
                            <span class="flex items-center justify-center w-8 h-8 rounded-full bg-amber-500/10 border border-amber-500/20 text-xs font-black text-amber-400 mb-3">1</span>
                            <h4 class="text-xs font-bold text-white mb-1">Campaign</h4>
                            <p class="text-[11px] text-slate-400 leading-normal max-w-[150px] mx-auto">School creates campaign</p>
                        </div>
                        
                        <div class="hidden xl:flex items-center text-slate-700 font-bold self-center">&rarr;</div>
                        
                        <!-- Step 2 -->
                        <div class="flex-1 flex flex-col items-center p-4 bg-slate-950/60 rounded-2xl border border-slate-900 shadow-sm text-center">
                            <span class="flex items-center justify-center w-8 h-8 rounded-full bg-amber-500/10 border border-amber-500/20 text-xs font-black text-amber-400 mb-3">2</span>
                            <h4 class="text-xs font-bold text-white mb-1">Submission</h4>
                            <p class="text-[11px] text-slate-400 leading-normal max-w-[150px] mx-auto">Parents submit data</p>
                        </div>
                        
                        <div class="hidden xl:flex items-center text-slate-700 font-bold self-center">&rarr;</div>
                        
                        <!-- Step 3 -->
                        <div class="flex-1 flex flex-col items-center p-4 bg-slate-950/60 rounded-2xl border border-slate-900 shadow-sm text-center">
                            <span class="flex items-center justify-center w-8 h-8 rounded-full bg-amber-500/10 border border-amber-500/20 text-xs font-black text-amber-400 mb-3">3</span>
                            <h4 class="text-xs font-bold text-white mb-1">Verification</h4>
                            <p class="text-[11px] text-slate-400 leading-normal max-w-[150px] mx-auto">Teachers verify information</p>
                        </div>
                        
                        <div class="hidden xl:flex items-center text-slate-700 font-bold self-center">&rarr;</div>
                        
                        <!-- Step 4 -->
                        <div class="flex-1 flex flex-col items-center p-4 bg-slate-950/60 rounded-2xl border border-slate-900 shadow-sm text-center">
                            <span class="flex items-center justify-center w-8 h-8 rounded-full bg-amber-500/10 border border-amber-500/20 text-xs font-black text-amber-400 mb-3">4</span>
                            <h4 class="text-xs font-bold text-white mb-1">Approval</h4>
                            <p class="text-[11px] text-slate-400 leading-normal max-w-[150px] mx-auto">School approves list</p>
                        </div>
                        
                        <div class="hidden xl:flex items-center text-slate-700 font-bold self-center">&rarr;</div>
                        
                        <!-- Step 5 -->
                        <div class="flex-1 flex flex-col items-center p-4 bg-slate-950/60 rounded-2xl border border-slate-900 shadow-sm text-center">
                            <span class="flex items-center justify-center w-8 h-8 rounded-full bg-amber-500/10 border border-amber-500/20 text-xs font-black text-amber-400 mb-3">5</span>
                            <h4 class="text-xs font-bold text-white mb-1">Receiving</h4>
                            <p class="text-[11px] text-slate-400 leading-normal max-w-[155px] mx-auto">Printing company receives data</p>
                        </div>
                        
                        <div class="hidden xl:flex items-center text-slate-700 font-bold self-center">&rarr;</div>
                        
                        <!-- Step 6 -->
                        <div class="flex-1 flex flex-col items-center p-4 bg-slate-950/60 rounded-2xl border border-slate-900 shadow-sm text-center">
                            <span class="flex items-center justify-center w-8 h-8 rounded-full bg-amber-500/10 border border-amber-500/20 text-xs font-black text-amber-400 mb-3">6</span>
                            <h4 class="text-xs font-bold text-white mb-1">Generation</h4>
                            <p class="text-[11px] text-slate-400 leading-normal max-w-[150px] mx-auto">ID cards generated</p>
                        </div>
                        
                        <div class="hidden xl:flex items-center text-emerald-500 font-black self-center">&rarr;</div>
                        
                        <!-- Step 7 -->
                        <div class="flex-1 flex flex-col items-center p-4 bg-emerald-950/15 rounded-2xl border border-emerald-900/30 shadow-sm text-center">
                            <span class="flex items-center justify-center w-8 h-8 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-xs font-black text-emerald-400 mb-3">7</span>
                            <h4 class="text-xs font-bold text-emerald-400 mb-1">Print</h4>
                            <p class="text-[11px] text-emerald-300 leading-normal max-w-[150px] mx-auto">Sent to final print margins</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Benefits Section -->
            <section class="max-w-5xl mx-auto mb-16 border-t border-slate-900 pt-16 text-center">
                <div class="space-y-3 mb-16">
                    <span class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-slate-900 border border-slate-800 text-[10px] font-bold tracking-widest text-amber-400 uppercase">
                        Partnership Value
                    </span>
                    <h2 class="text-2xl sm:text-4xl font-extrabold text-white leading-tight">
                        Benefits
                    </h2>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 text-left">
                    <!-- Benefit 1: Reduce Corrections -->
                    <div class="p-6 rounded-2xl bg-slate-900/40 border border-slate-850 hover:border-slate-800 transition duration-300 shadow group">
                        <div class="w-9 h-9 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 mb-4 group-hover:scale-105 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                        <h3 class="text-sm font-bold text-white mb-1.5">Reduce Corrections</h3>
                        <p class="text-xs text-slate-400 leading-relaxed">Verified student information.</p>
                    </div>

                    <!-- Benefit 2: Faster Production -->
                    <div class="p-6 rounded-2xl bg-slate-900/40 border border-slate-850 hover:border-slate-800 transition duration-300 shadow group">
                        <div class="w-9 h-9 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 mb-4 group-hover:scale-105 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                        </div>
                        <h3 class="text-sm font-bold text-white mb-1.5">Faster Production</h3>
                        <p class="text-xs text-slate-400 leading-relaxed">Organized data.</p>
                    </div>

                    <!-- Benefit 3: Bulk Processing -->
                    <div class="p-6 rounded-2xl bg-slate-900/40 border border-slate-850 hover:border-slate-800 transition duration-300 shadow group">
                        <div class="w-9 h-9 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 mb-4 group-hover:scale-105 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                        </div>
                        <h3 class="text-sm font-bold text-white mb-1.5">Bulk Processing</h3>
                        <p class="text-xs text-slate-400 leading-relaxed">Handle hundreds or thousands of students.</p>
                    </div>

                    <!-- Benefit 4: Multiple Schools -->
                    <div class="p-6 rounded-2xl bg-slate-900/40 border border-slate-850 hover:border-slate-800 transition duration-300 shadow group">
                        <div class="w-9 h-9 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 mb-4 group-hover:scale-105 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                        </div>
                        <h3 class="text-sm font-bold text-white mb-1.5">Multiple Schools</h3>
                        <p class="text-xs text-slate-400 leading-relaxed">Manage multiple school projects.</p>
                    </div>

                    <!-- Benefit 5: Professional Output -->
                    <div class="p-6 rounded-2xl bg-slate-900/40 border border-slate-850 hover:border-slate-800 transition duration-300 shadow group lg:col-span-2">
                        <div class="w-9 h-9 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 mb-4 group-hover:scale-105 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                        </div>
                        <h3 class="text-sm font-bold text-white mb-1.5">Professional Output</h3>
                        <p class="text-xs text-slate-400 leading-relaxed">Standardized ID card templates.</p>
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
