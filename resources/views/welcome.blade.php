<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>iCard Maker - Digital Student ID Card Portal</title>
        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:300,400,500,600,700,800&display=swap" rel="stylesheet" />
        <!-- Styles -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="antialiased font-sans bg-slate-950 text-white min-h-screen flex flex-col justify-between selection:bg-amber-500 selection:text-slate-950">
        <!-- Glow effects -->
        <div class="absolute top-0 left-1/4 w-[500px] h-[500px] bg-indigo-900/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute top-40 right-1/4 w-[500px] h-[500px] bg-amber-500/5 rounded-full blur-3xl pointer-events-none"></div>

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

        <!-- Main Hero Section -->
        <main class="relative z-10 max-w-7xl mx-auto w-full px-6 py-16 flex-grow flex flex-col lg:flex-row items-center justify-between gap-12">
            <!-- Left Content Column -->
            <div class="flex-1 text-center lg:text-left space-y-6 max-w-2xl">
                <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-slate-900 border border-slate-800 text-xs font-semibold tracking-wide text-amber-400 animate-pulse">
                    <span>✨ Modern Digital ID Card System</span>
                </div>
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black tracking-tight leading-tight text-white flex flex-col gap-2">
                    <span class="bg-gradient-to-r from-white via-slate-100 to-slate-300 bg-clip-text text-transparent">Student ID Card Management Platform</span>
                    <span class="text-2xl sm:text-3xl lg:text-4xl font-extrabold bg-gradient-to-r from-amber-400 via-amber-500 to-orange-400 bg-clip-text text-transparent leading-normal">
                        From Student Registration to Print-Ready ID Cards
                    </span>
                </h1>
                <p class="text-lg text-slate-400 leading-relaxed">
                    One cloud platform that connects Schools, Teachers, Parents and Printing Companies.
                </p>
                
                <div class="pt-4 flex flex-col sm:flex-row items-center gap-4 justify-center lg:justify-start">
                    @auth
                        <a href="{{ url('/dashboard') }}" class="inline-flex items-center justify-center px-6 py-4 text-base font-bold text-slate-950 bg-gradient-to-r from-amber-400 to-amber-500 hover:from-amber-300 hover:to-amber-400 rounded-2xl transition duration-200 shadow-lg shadow-amber-500/20">
                            Go to Dashboard &rarr;
                        </a>
                        <a href="/mobile-app" class="inline-flex items-center justify-center px-6 py-4 text-base font-bold text-slate-300 hover:text-white bg-slate-900 hover:bg-slate-800 border border-slate-800 rounded-2xl transition duration-200">
                            <svg class="w-5 h-5 mr-2 text-amber-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                            Download App
                        </a>
                    @else
                        <a href="{{ route('register') }}" class="inline-flex items-center justify-center px-6 py-4 text-base font-bold text-slate-950 bg-gradient-to-r from-amber-400 to-amber-500 hover:from-amber-300 hover:to-amber-400 rounded-2xl transition duration-200 shadow-lg shadow-amber-500/20">
                            Start Free Trial
                        </a>
                        <a href="{{ route('contact') }}" class="inline-flex items-center justify-center px-6 py-4 text-base font-bold text-slate-300 hover:text-white bg-slate-900 hover:bg-slate-800 border border-slate-800 rounded-2xl transition duration-200">
                            Book Demo
                        </a>
                        <a href="/mobile-app" class="inline-flex items-center justify-center px-6 py-4 text-base font-bold text-slate-300 hover:text-white bg-slate-900 hover:bg-slate-800 border border-slate-800 rounded-2xl transition duration-200">
                            <svg class="w-5 h-5 mr-2 text-amber-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                            Download App
                        </a>
                    @endauth
                </div>

                <!-- Minimal Trust Stats -->
                <div class="pt-8 border-t border-slate-900 grid grid-cols-3 gap-6 max-w-md mx-auto lg:mx-0">
                    <div>
                        <p class="text-2xl font-black text-white">100%</p>
                        <p class="text-xs text-slate-500 uppercase tracking-widest font-semibold mt-1">Digital Setup</p>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-white">Export</p>
                        <p class="text-xs text-slate-500 uppercase tracking-widest font-semibold mt-1">Print-Ready PDF</p>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-white">Zero</p>
                        <p class="text-xs text-slate-500 uppercase tracking-widest font-semibold mt-1">Hardware Required</p>
                    </div>
                </div>
            </div>

            <!-- Right Interactive Graphic Column -->
            <div class="flex-1 w-full max-w-md bg-slate-900/60 border border-slate-850 rounded-3xl p-8 relative shadow-2xl shadow-indigo-500/5 backdrop-blur-sm">
                <!-- Card Header -->
                <div class="flex justify-between items-center pb-4 border-b border-slate-850">
                    <div class="flex items-center space-x-2">
                        <div class="w-2.5 h-2.5 rounded-full bg-green-500 shadow-md shadow-green-500/30 animate-pulse"></div>
                        <span class="text-xs font-black text-slate-200 uppercase tracking-widest">Automated Delivery Pipeline</span>
                    </div>
                    <span class="text-[9px] text-slate-500 font-bold tracking-widest">ACTIVE</span>
                </div>

                <!-- Workflow Timeline Diagram -->
                <div class="relative border-l border-slate-800/80 ml-2.5 pl-6 space-y-5 py-4 mt-2">
                    <!-- Step 1: School -->
                    <div class="relative group flex items-start space-x-4">
                        <!-- Bullet on the line -->
                        <div class="absolute -left-[35px] top-1.5 w-5 h-5 rounded-full bg-slate-950 border border-slate-800 group-hover:border-amber-400 transition duration-200 flex items-center justify-center">
                            <div class="w-1.5 h-1.5 rounded-full bg-amber-400 group-hover:bg-amber-400"></div>
                        </div>
                        <div class="flex-shrink-0 w-8 h-8 rounded-lg bg-amber-500/5 border border-slate-800 group-hover:border-amber-500/20 flex items-center justify-center text-amber-400 transition duration-200">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                        </div>
                        <div class="transform group-hover:translate-x-1 transition duration-200">
                            <h4 class="text-xs font-bold text-white uppercase tracking-wider">School</h4>
                            <p class="text-[10px] text-slate-500">Initiates the ID card creation campaign</p>
                        </div>
                    </div>

                    <!-- Step 2: Teachers -->
                    <div class="relative group flex items-start space-x-4">
                        <!-- Bullet on the line -->
                        <div class="absolute -left-[35px] top-1.5 w-5 h-5 rounded-full bg-slate-950 border border-slate-800 group-hover:border-amber-400 transition duration-200 flex items-center justify-center">
                            <div class="w-1.5 h-1.5 rounded-full bg-amber-400 group-hover:bg-amber-400"></div>
                        </div>
                        <div class="flex-shrink-0 w-8 h-8 rounded-lg bg-amber-500/5 border border-slate-800 group-hover:border-amber-500/20 flex items-center justify-center text-amber-400 transition duration-200">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                        </div>
                        <div class="transform group-hover:translate-x-1 transition duration-200">
                            <h4 class="text-xs font-bold text-white uppercase tracking-wider">Teachers</h4>
                            <p class="text-[10px] text-slate-500">Manage classroom rosters & profiles</p>
                        </div>
                    </div>

                    <!-- Step 3: Parents -->
                    <div class="relative group flex items-start space-x-4">
                        <!-- Bullet on the line -->
                        <div class="absolute -left-[35px] top-1.5 w-5 h-5 rounded-full bg-slate-950 border border-slate-800 group-hover:border-amber-400 transition duration-200 flex items-center justify-center">
                            <div class="w-1.5 h-1.5 rounded-full bg-amber-400 group-hover:bg-amber-400"></div>
                        </div>
                        <div class="flex-shrink-0 w-8 h-8 rounded-lg bg-amber-500/5 border border-slate-800 group-hover:border-amber-500/20 flex items-center justify-center text-amber-400 transition duration-200">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <div class="transform group-hover:translate-x-1 transition duration-200">
                            <h4 class="text-xs font-bold text-white uppercase tracking-wider">Parents</h4>
                            <p class="text-[10px] text-slate-500">Upload profile photos & approve info</p>
                        </div>
                    </div>

                    <!-- Step 4: Student Database -->
                    <div class="relative group flex items-start space-x-4">
                        <!-- Bullet on the line -->
                        <div class="absolute -left-[35px] top-1.5 w-5 h-5 rounded-full bg-slate-950 border border-slate-800 group-hover:border-amber-400 transition duration-200 flex items-center justify-center">
                            <div class="w-1.5 h-1.5 rounded-full bg-amber-400 group-hover:bg-amber-400"></div>
                        </div>
                        <div class="flex-shrink-0 w-8 h-8 rounded-lg bg-amber-500/5 border border-slate-800 group-hover:border-amber-500/20 flex items-center justify-center text-amber-400 transition duration-200">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
                            </svg>
                        </div>
                        <div class="transform group-hover:translate-x-1 transition duration-200">
                            <h4 class="text-xs font-bold text-white uppercase tracking-wider">Student Database</h4>
                            <p class="text-[10px] text-slate-500">Centralized secure identity storage</p>
                        </div>
                    </div>

                    <!-- Step 5: Template Studio -->
                    <div class="relative group flex items-start space-x-4">
                        <!-- Bullet on the line -->
                        <div class="absolute -left-[35px] top-1.5 w-5 h-5 rounded-full bg-slate-950 border border-slate-800 group-hover:border-amber-400 transition duration-200 flex items-center justify-center">
                            <div class="w-1.5 h-1.5 rounded-full bg-amber-400 group-hover:bg-amber-400"></div>
                        </div>
                        <div class="flex-shrink-0 w-8 h-8 rounded-lg bg-amber-500/5 border border-slate-800 group-hover:border-amber-500/20 flex items-center justify-center text-amber-400 transition duration-200">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div class="transform group-hover:translate-x-1 transition duration-200">
                            <h4 class="text-xs font-bold text-white uppercase tracking-wider">Template Studio</h4>
                            <p class="text-[10px] text-slate-500">Design cards or map variables dynamically</p>
                        </div>
                    </div>

                    <!-- Step 6: Print Company -->
                    <div class="relative group flex items-start space-x-4">
                        <!-- Bullet on the line -->
                        <div class="absolute -left-[35px] top-1.5 w-5 h-5 rounded-full bg-slate-950 border border-slate-800 group-hover:border-amber-400 transition duration-200 flex items-center justify-center">
                            <div class="w-1.5 h-1.5 rounded-full bg-amber-400 group-hover:bg-amber-400"></div>
                        </div>
                        <div class="flex-shrink-0 w-8 h-8 rounded-lg bg-amber-500/5 border border-slate-800 group-hover:border-amber-500/20 flex items-center justify-center text-amber-400 transition duration-200">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                            </svg>
                        </div>
                        <div class="transform group-hover:translate-x-1 transition duration-200">
                            <h4 class="text-xs font-bold text-white uppercase tracking-wider">Print Company</h4>
                            <p class="text-[10px] text-slate-500">Access print-ready files securely</p>
                        </div>
                    </div>

                    <!-- Step 7: ID Cards Ready -->
                    <div class="relative group flex items-start space-x-4">
                        <!-- Bullet on the line -->
                        <div class="absolute -left-[35px] top-1.5 w-5 h-5 rounded-full bg-slate-950 border border-emerald-500/50 group-hover:border-emerald-400 transition duration-200 flex items-center justify-center">
                            <div class="w-1.5 h-1.5 rounded-full bg-emerald-400"></div>
                        </div>
                        <div class="flex-shrink-0 w-8 h-8 rounded-lg bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400 transition duration-200">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                        <div class="transform group-hover:translate-x-1 transition duration-200">
                            <h4 class="text-xs font-bold text-emerald-400 uppercase tracking-wider">ID Cards Ready</h4>
                            <p class="text-[10px] text-emerald-500/80">Batch printed, cut, and issued</p>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Trust Section -->
        <section class="relative z-10 max-w-7xl mx-auto w-full px-6 py-6">
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
                <!-- Stat 1 -->
                <div class="flex flex-col items-center justify-center text-center p-6 rounded-2xl bg-slate-900/40 border border-slate-850 hover:border-slate-800 transition duration-200 shadow-lg">
                    <span class="text-3xl sm:text-4xl lg:text-5xl font-black bg-gradient-to-r from-amber-400 via-amber-500 to-orange-400 bg-clip-text text-transparent">
                        10,000+
                    </span>
                    <span class="text-xs sm:text-sm font-bold tracking-wider text-slate-400 uppercase mt-2">
                        Students Managed
                    </span>
                </div>

                <!-- Stat 2 -->
                <div class="flex flex-col items-center justify-center text-center p-6 rounded-2xl bg-slate-900/40 border border-slate-850 hover:border-slate-800 transition duration-200 shadow-lg">
                    <span class="text-3xl sm:text-4xl lg:text-5xl font-black bg-gradient-to-r from-amber-400 via-amber-500 to-orange-400 bg-clip-text text-transparent">
                        100+
                    </span>
                    <span class="text-xs sm:text-sm font-bold tracking-wider text-slate-400 uppercase mt-2">
                        Schools
                    </span>
                </div>

                <!-- Stat 3 -->
                <div class="flex flex-col items-center justify-center text-center p-6 rounded-2xl bg-slate-900/40 border border-slate-850 hover:border-slate-800 transition duration-200 shadow-lg">
                    <span class="text-3xl sm:text-4xl lg:text-5xl font-black bg-gradient-to-r from-amber-400 via-amber-500 to-orange-400 bg-clip-text text-transparent">
                        50,000+
                    </span>
                    <span class="text-xs sm:text-sm font-bold tracking-wider text-slate-400 uppercase mt-2">
                        ID Cards Generated
                    </span>
                </div>

                <!-- Stat 4 -->
                <div class="flex flex-col items-center justify-center text-center p-6 rounded-2xl bg-slate-900/40 border border-slate-850 hover:border-slate-800 transition duration-200 shadow-lg">
                    <span class="text-3xl sm:text-4xl lg:text-5xl font-black text-emerald-400">
                        95%
                    </span>
                    <span class="text-xs sm:text-sm font-bold tracking-wider text-slate-400 uppercase mt-2">
                        Reduction in Manual Work
                    </span>
                </div>
            </div>
        </section>

        <!-- The Problem Section -->
        <section class="relative z-10 max-w-7xl mx-auto w-full px-6 py-16 border-t border-slate-900">
            <div class="text-center max-w-3xl mx-auto space-y-4 mb-12">
                <span class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-red-950/40 border border-red-900/30 text-xs font-semibold tracking-wider text-red-400 uppercase">
                    ⚠️ The Traditional Way
                </span>
                <h2 class="text-3xl sm:text-4xl font-black text-white tracking-tight">
                    Why Schools Still Waste Weeks Creating ID Cards
                </h2>
                <p class="text-sm text-slate-400 max-w-xl mx-auto">
                    A frustrating, manual back-and-forth chain reaction that wastes time and yields poor results.
                </p>
            </div>

            <!-- Problem Workflow Chain -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 max-w-6xl mx-auto">
                <!-- Step 1 -->
                <div class="p-6 rounded-2xl bg-slate-950 border border-slate-900 hover:border-red-900/40 transition duration-200 flex flex-col justify-between group relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-16 h-16 bg-red-500/5 rounded-bl-full pointer-events-none"></div>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-[10px] text-red-400 font-bold uppercase tracking-widest bg-red-950/30 px-2 py-0.5 rounded-full border border-red-900/20">Step 01</span>
                            <span class="text-red-500 text-lg select-none group-hover:scale-115 transition duration-200">💬</span>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-slate-200">WhatsApp Spam</h4>
                            <p class="text-xs text-slate-500 mt-2 leading-relaxed">Teacher asking parents on WhatsApp for photos and details.</p>
                        </div>
                    </div>
                </div>

                <!-- Step 2 -->
                <div class="p-6 rounded-2xl bg-slate-950 border border-slate-900 hover:border-red-900/40 transition duration-200 flex flex-col justify-between group relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-16 h-16 bg-red-500/5 rounded-bl-full pointer-events-none"></div>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-[10px] text-red-400 font-bold uppercase tracking-widest bg-red-950/30 px-2 py-0.5 rounded-full border border-red-900/20">Step 02</span>
                            <span class="text-red-500 text-lg select-none group-hover:scale-115 transition duration-200">📷</span>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-slate-200">Wrong Photos</h4>
                            <p class="text-xs text-slate-500 mt-2 leading-relaxed">Parents sending wrong, low-res, or blurry photos.</p>
                        </div>
                    </div>
                </div>

                <!-- Step 3 -->
                <div class="p-6 rounded-2xl bg-slate-950 border border-slate-900 hover:border-red-900/40 transition duration-200 flex flex-col justify-between group relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-16 h-16 bg-red-500/5 rounded-bl-full pointer-events-none"></div>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-[10px] text-red-400 font-bold uppercase tracking-widest bg-red-950/30 px-2 py-0.5 rounded-full border border-red-900/20">Step 03</span>
                            <span class="text-red-500 text-lg select-none group-hover:scale-115 transition duration-200">✍️</span>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-slate-200">Admin Corrections</h4>
                            <p class="text-xs text-slate-500 mt-2 leading-relaxed">Admin correcting spelling names, typos, and classes manually.</p>
                        </div>
                    </div>
                </div>

                <!-- Step 4 -->
                <div class="p-6 rounded-2xl bg-slate-950 border border-slate-900 hover:border-red-900/40 transition duration-200 flex flex-col justify-between group relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-16 h-16 bg-red-500/5 rounded-bl-full pointer-events-none"></div>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-[10px] text-red-400 font-bold uppercase tracking-widest bg-red-950/30 px-2 py-0.5 rounded-full border border-red-900/20">Step 04</span>
                            <span class="text-red-500 text-lg select-none group-hover:scale-115 transition duration-200">🔄</span>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-slate-200">Vendor Back & Forth</h4>
                            <p class="text-xs text-slate-500 mt-2 leading-relaxed">Printing company asking again due to size, format, or layout issues.</p>
                        </div>
                    </div>
                </div>

                <!-- Step 5 -->
                <div class="p-6 rounded-2xl bg-slate-950 border border-slate-900 hover:border-red-900/40 transition duration-200 flex flex-col justify-between group relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-16 h-16 bg-red-500/5 rounded-bl-full pointer-events-none"></div>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-[10px] text-red-400 font-bold uppercase tracking-widest bg-red-950/30 px-2 py-0.5 rounded-full border border-red-900/20">Step 05</span>
                            <span class="text-red-500 text-lg select-none group-hover:scale-115 transition duration-200">❌</span>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-slate-200">Wrong ID Cards</h4>
                            <p class="text-xs text-slate-500 mt-2 leading-relaxed">Errors get through, leading to incorrect details on final cards.</p>
                        </div>
                    </div>
                </div>

                <!-- Step 6 -->
                <div class="p-6 rounded-2xl bg-slate-950 border border-slate-900 hover:border-red-900/40 transition duration-200 flex flex-col justify-between group relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-16 h-16 bg-red-500/5 rounded-bl-full pointer-events-none"></div>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-[10px] text-red-400 font-bold uppercase tracking-widest bg-red-950/30 px-2 py-0.5 rounded-full border border-red-900/20">Step 06</span>
                            <span class="text-red-500 text-lg select-none group-hover:scale-115 transition duration-200">🖨️</span>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-slate-200">Reprint Cycle</h4>
                            <p class="text-xs text-slate-500 mt-2 leading-relaxed">Paying extra fees and waiting to run print sheets again.</p>
                        </div>
                    </div>
                </div>

                <!-- Step 7 -->
                <div class="p-6 rounded-2xl bg-slate-950 border border-slate-900 hover:border-red-900/40 transition duration-200 flex flex-col justify-between group relative overflow-hidden md:col-span-2 lg:col-span-2">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-red-500/5 rounded-bl-full pointer-events-none"></div>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-[10px] text-red-400 font-bold uppercase tracking-widest bg-red-950/30 px-2 py-0.5 rounded-full border border-red-900/20">Result</span>
                            <span class="text-red-500 text-lg select-none group-hover:scale-115 transition duration-200">⏳</span>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-slate-200">Weeks of Delay</h4>
                            <p class="text-xs text-slate-500 mt-2 leading-relaxed">Campuses left without secure physical or digital identification for weeks.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- The Solution Section -->
        <section class="relative z-10 max-w-7xl mx-auto w-full px-6 py-16 border-t border-slate-900 bg-slate-950/10">
            <div class="text-center max-w-3xl mx-auto space-y-4 mb-12">
                <span class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-emerald-950/40 border border-emerald-900/30 text-xs font-semibold tracking-wider text-emerald-400 uppercase">
                    ✨ The Modern Way
                </span>
                <h2 class="text-3xl sm:text-4xl font-extrabold text-white">
                    The Solution
                </h2>
                <p class="text-sm text-slate-400 max-w-2xl mx-auto">
                    Our cloud-based platform automates the complete ID card creation process. Every stakeholder works on the same platform.
                </p>
            </div>

            <!-- Role Roles Solution Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 max-w-6xl mx-auto mb-10">
                <!-- Sol 1 -->
                <div class="p-6 rounded-2xl bg-slate-900/40 border border-slate-850 hover:border-emerald-500/20 transition duration-200 flex items-start space-x-3">
                    <span class="text-emerald-400 text-lg select-none">✅</span>
                    <div>
                        <h4 class="text-sm font-bold text-white">Schools Control Everything</h4>
                        <p class="text-xs text-slate-500 mt-1 leading-relaxed">Admins manage templates, divisions, and print exports centrally.</p>
                    </div>
                </div>

                <!-- Sol 2 -->
                <div class="p-6 rounded-2xl bg-slate-900/40 border border-slate-850 hover:border-emerald-500/20 transition duration-200 flex items-start space-x-3">
                    <span class="text-emerald-400 text-lg select-none">✅</span>
                    <div>
                        <h4 class="text-sm font-bold text-white">Teachers Collect Data</h4>
                        <p class="text-xs text-slate-500 mt-1 leading-relaxed">Class teachers quickly update rosters and take profile headshots.</p>
                    </div>
                </div>

                <!-- Sol 3 -->
                <div class="p-6 rounded-2xl bg-slate-900/40 border border-slate-850 hover:border-emerald-500/20 transition duration-200 flex items-start space-x-3">
                    <span class="text-emerald-400 text-lg select-none">✅</span>
                    <div>
                        <h4 class="text-sm font-bold text-white">Parents Verify Details</h4>
                        <p class="text-xs text-slate-500 mt-1 leading-relaxed">Parents enter correct info and upload photos directly from their phone.</p>
                    </div>
                </div>

                <!-- Sol 4 -->
                <div class="p-6 rounded-2xl bg-slate-900/40 border border-slate-850 hover:border-emerald-500/20 transition duration-200 flex items-start space-x-3">
                    <span class="text-emerald-400 text-lg select-none">✅</span>
                    <div>
                        <h4 class="text-sm font-bold text-white">Printers Get Ready Data</h4>
                        <p class="text-xs text-slate-500 mt-1 leading-relaxed">Printing companies receive ready-to-print PDFs with correct dimensions.</p>
                    </div>
                </div>
            </div>

            <!-- Value Propositions list -->
            <div class="max-w-4xl mx-auto grid grid-cols-1 md:grid-cols-3 gap-6 pt-6 border-t border-slate-900/60">
                <div class="flex items-center space-x-3 justify-center md:justify-start">
                    <div class="w-8 h-8 rounded-full bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400 text-sm font-black">✓</div>
                    <span class="text-xs font-semibold text-slate-300">No Spreadsheets</span>
                </div>
                <div class="flex items-center space-x-3 justify-center md:justify-start">
                    <div class="w-8 h-8 rounded-full bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400 text-sm font-black">✓</div>
                    <span class="text-xs font-semibold text-slate-300">No WhatsApp Confusion</span>
                </div>
                <div class="flex items-center space-x-3 justify-center md:justify-start">
                    <div class="w-8 h-8 rounded-full bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400 text-sm font-black">✓</div>
                    <span class="text-xs font-semibold text-slate-300">No Repeated Data Entry</span>
                </div>
            </div>

            <!-- Horizontal Timeline -->
            <div class="max-w-6xl mx-auto mt-16 pt-12 border-t border-slate-900/60 relative">
                <h3 class="text-center text-xs font-black tracking-widest text-emerald-400 uppercase mb-10">Modern Automated Workflow</h3>
                
                <div class="relative flex flex-col md:flex-row justify-between items-start md:items-center gap-8 md:gap-4">
                    <!-- Continuous Line behind steps (Desktop only) -->
                    <div class="hidden md:block absolute top-[18px] left-4 right-4 h-0.5 bg-gradient-to-r from-emerald-500/10 via-emerald-500/40 to-emerald-500/10"></div>

                    <!-- Step 1: School Profile -->
                    <div class="relative flex md:flex-col items-center md:text-center gap-4 md:gap-3 flex-1 z-10 group w-full">
                        <div class="w-9 h-9 rounded-full bg-slate-950 border-2 border-emerald-500 flex items-center justify-center text-emerald-400 font-black text-xs shadow-lg shadow-emerald-500/10 group-hover:scale-110 group-hover:border-emerald-400 transition duration-200">
                            1
                        </div>
                        <div>
                            <p class="text-xs font-bold text-white uppercase tracking-wider group-hover:text-emerald-400 transition duration-200">School Profile</p>
                            <p class="text-[10px] text-slate-500 mt-0.5 max-w-[125px] md:mx-auto">Setup branding, name & details</p>
                        </div>
                    </div>

                    <!-- Step 2: Teacher Management -->
                    <div class="relative flex md:flex-col items-center md:text-center gap-4 md:gap-3 flex-1 z-10 group w-full">
                        <div class="w-9 h-9 rounded-full bg-slate-950 border-2 border-emerald-500/50 flex items-center justify-center text-emerald-400/80 font-black text-xs shadow-lg group-hover:scale-110 group-hover:border-emerald-400 transition duration-200">
                            2
                        </div>
                        <div>
                            <p class="text-xs font-bold text-white uppercase tracking-wider group-hover:text-emerald-400 transition duration-200">Teacher Management</p>
                            <p class="text-[10px] text-slate-500 mt-0.5 max-w-[125px] md:mx-auto">Assign class rosters to staff</p>
                        </div>
                    </div>

                    <!-- Step 3: Campaign -->
                    <div class="relative flex md:flex-col items-center md:text-center gap-4 md:gap-3 flex-1 z-10 group w-full">
                        <div class="w-9 h-9 rounded-full bg-slate-950 border-2 border-emerald-500/50 flex items-center justify-center text-emerald-400/80 font-black text-xs shadow-lg group-hover:scale-110 group-hover:border-emerald-400 transition duration-200">
                            3
                        </div>
                        <div>
                            <p class="text-xs font-bold text-white uppercase tracking-wider group-hover:text-emerald-400 transition duration-200">Campaign</p>
                            <p class="text-[10px] text-slate-500 mt-0.5 max-w-[125px] md:mx-auto">Launch photos collection link</p>
                        </div>
                    </div>

                    <!-- Step 4: Parent Mobile App -->
                    <div class="relative flex md:flex-col items-center md:text-center gap-4 md:gap-3 flex-1 z-10 group w-full">
                        <div class="w-9 h-9 rounded-full bg-slate-950 border-2 border-emerald-500/50 flex items-center justify-center text-emerald-400/80 font-black text-xs shadow-lg group-hover:scale-110 group-hover:border-emerald-400 transition duration-200">
                            4
                        </div>
                        <div>
                            <p class="text-xs font-bold text-white uppercase tracking-wider group-hover:text-emerald-400 transition duration-200">Parent Mobile App</p>
                            <p class="text-[10px] text-slate-500 mt-0.5 max-w-[125px] md:mx-auto">Parents crop & submit photos</p>
                        </div>
                    </div>

                    <!-- Step 5: Student Database -->
                    <div class="relative flex md:flex-col items-center md:text-center gap-4 md:gap-3 flex-1 z-10 group w-full">
                        <div class="w-9 h-9 rounded-full bg-slate-950 border-2 border-emerald-500/50 flex items-center justify-center text-emerald-400/80 font-black text-xs shadow-lg group-hover:scale-110 group-hover:border-emerald-400 transition duration-200">
                            5
                        </div>
                        <div>
                            <p class="text-xs font-bold text-white uppercase tracking-wider group-hover:text-emerald-400 transition duration-200">Student Database</p>
                            <p class="text-[10px] text-slate-500 mt-0.5 max-w-[125px] md:mx-auto">Central secure profile registry</p>
                        </div>
                    </div>

                    <!-- Step 6: Template Studio -->
                    <div class="relative flex md:flex-col items-center md:text-center gap-4 md:gap-3 flex-1 z-10 group w-full">
                        <div class="w-9 h-9 rounded-full bg-slate-950 border-2 border-emerald-500/50 flex items-center justify-center text-emerald-400/80 font-black text-xs shadow-lg group-hover:scale-110 group-hover:border-emerald-400 transition duration-200">
                            6
                        </div>
                        <div>
                            <p class="text-xs font-bold text-white uppercase tracking-wider group-hover:text-emerald-400 transition duration-200">Template Studio</p>
                            <p class="text-[10px] text-slate-500 mt-0.5 max-w-[125px] md:mx-auto">Design dynamic card variables</p>
                        </div>
                    </div>

                    <!-- Step 7: Printing -->
                    <div class="relative flex md:flex-col items-center md:text-center gap-4 md:gap-3 flex-1 z-10 group w-full">
                        <div class="w-9 h-9 rounded-full bg-slate-950 border-2 border-emerald-500 flex items-center justify-center text-emerald-400 font-black text-xs shadow-lg shadow-emerald-500/10 group-hover:scale-110 group-hover:border-emerald-400 transition duration-200">
                            7
                        </div>
                        <div>
                            <p class="text-xs font-bold text-white uppercase tracking-wider group-hover:text-emerald-400 transition duration-200">Printing</p>
                            <p class="text-[10px] text-slate-500 mt-0.5 max-w-[125px] md:mx-auto">One-click high-res print sheet</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Dynamic Information / Workflow Section -->
        <section class="relative z-10 max-w-7xl mx-auto w-full px-6 py-16 border-t border-slate-900">
            <div class="text-center max-w-2xl mx-auto space-y-3 mb-16">
                <h2 class="text-3xl font-bold text-white">How iCard Maker Works</h2>
                <p class="text-sm text-slate-400">Transform raw student records into printed plastic badges in four simple phases.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div class="space-y-3">
                    <div class="w-8 h-8 rounded-lg bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-xs font-bold text-indigo-400">01</div>
                    <h3 class="text-base font-bold text-white">Upload Roster</h3>
                    <p class="text-xs text-slate-400 leading-relaxed">Import student details dynamically via excel spreadsheets or our manual class configuration forms.</p>
                </div>
                <div class="space-y-3">
                    <div class="w-8 h-8 rounded-lg bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-xs font-bold text-amber-400">02</div>
                    <h3 class="text-base font-bold text-white">Design Layout</h3>
                    <p class="text-xs text-slate-400 leading-relaxed">Choose margins, upload backgrounds, and customize text parameters with our Drag & Drop Template builder.</p>
                </div>
                <div class="space-y-3">
                    <div class="w-8 h-8 rounded-lg bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-xs font-bold text-indigo-400">03</div>
                    <h3 class="text-base font-bold text-white">Photo Campaign</h3>
                    <p class="text-xs text-slate-400 leading-relaxed">Share collection campaign links with parents or teachers so they can snap and upload cropped candidate photos directly.</p>
                </div>
                <div class="space-y-3">
                    <div class="w-8 h-8 rounded-lg bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-xs font-bold text-amber-400">04</div>
                    <h3 class="text-base font-bold text-white">Print PDF Sheets</h3>
                    <p class="text-xs text-slate-400 leading-relaxed">Export high-resolution PDF print grids (A4 size with 8/10 cards per sheet) ready for commercial badge printers.</p>
                </div>
            </div>
        </section>

        <!-- How It Works Timeline Section -->
        <section class="relative z-10 max-w-7xl mx-auto w-full px-6 py-16 border-t border-slate-900 bg-slate-950/20">
            <div class="text-center max-w-2xl mx-auto space-y-3 mb-16">
                <span class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-slate-950 border border-slate-800 text-[10px] font-bold tracking-widest text-amber-400 uppercase">
                    Timeline Flow
                </span>
                <h2 class="text-3xl sm:text-4xl font-extrabold text-white">How It Works</h2>
                <p class="text-sm text-slate-400">Follow the simple steps from class setup to physical printed cards.</p>
            </div>

            <!-- Vertical Timeline Track -->
            <div class="relative max-w-4xl mx-auto">
                <!-- Line down the middle -->
                <div class="absolute left-8 md:left-1/2 top-4 bottom-4 w-[2px] bg-slate-800/80 -translate-x-1/2"></div>

                <div class="space-y-12">
                    <!-- Step 1 -->
                    <div class="relative flex flex-col md:flex-row items-start md:items-center justify-between group">
                        <div class="w-full md:w-[45%] text-left md:text-right pl-16 md:pl-0 md:pr-8">
                            <span class="text-[10px] font-black text-amber-400 uppercase tracking-widest">Step 01</span>
                            <h3 class="text-lg font-bold text-white group-hover:text-amber-400 transition">School creates academic year</h3>
                        </div>
                        <div class="absolute left-8 md:left-1/2 w-10 h-10 -translate-x-1/2 rounded-full bg-slate-950 border border-slate-800 flex items-center justify-center text-sm font-bold text-slate-300 group-hover:border-amber-400 transition z-10">
                            01
                        </div>
                        <div class="w-full md:w-[45%] pl-8 hidden md:block"></div>
                    </div>

                    <!-- Step 2 -->
                    <div class="relative flex flex-col md:flex-row items-start md:items-center justify-between group">
                        <div class="w-full md:w-[45%] pr-8 hidden md:block"></div>
                        <div class="absolute left-8 md:left-1/2 w-10 h-10 -translate-x-1/2 rounded-full bg-slate-950 border border-slate-800 flex items-center justify-center text-sm font-bold text-slate-300 group-hover:border-amber-400 transition z-10">
                            02
                        </div>
                        <div class="w-full md:w-[45%] text-left pl-16 md:pl-8">
                            <span class="text-[10px] font-black text-amber-400 uppercase tracking-widest">Step 02</span>
                            <h3 class="text-lg font-bold text-white group-hover:text-amber-400 transition">Teachers receive login</h3>
                        </div>
                    </div>

                    <!-- Step 3 -->
                    <div class="relative flex flex-col md:flex-row items-start md:items-center justify-between group">
                        <div class="w-full md:w-[45%] text-left md:text-right pl-16 md:pl-0 md:pr-8">
                            <span class="text-[10px] font-black text-amber-400 uppercase tracking-widest">Step 03</span>
                            <h3 class="text-lg font-bold text-white group-hover:text-amber-400 transition">Teachers add students</h3>
                        </div>
                        <div class="absolute left-8 md:left-1/2 w-10 h-10 -translate-x-1/2 rounded-full bg-slate-950 border border-slate-800 flex items-center justify-center text-sm font-bold text-slate-300 group-hover:border-amber-400 transition z-10">
                            03
                        </div>
                        <div class="w-full md:w-[45%] pl-8 hidden md:block"></div>
                    </div>

                    <!-- Step 4 -->
                    <div class="relative flex flex-col md:flex-row items-start md:items-center justify-between group">
                        <div class="w-full md:w-[45%] pr-8 hidden md:block"></div>
                        <div class="absolute left-8 md:left-1/2 w-10 h-10 -translate-x-1/2 rounded-full bg-slate-950 border border-slate-800 flex items-center justify-center text-sm font-bold text-slate-300 group-hover:border-amber-400 transition z-10">
                            04
                        </div>
                        <div class="w-full md:w-[45%] text-left pl-16 md:pl-8">
                            <span class="text-[10px] font-black text-amber-400 uppercase tracking-widest">Step 04</span>
                            <h3 class="text-lg font-bold text-white group-hover:text-amber-400 transition">Parents receive login</h3>
                        </div>
                    </div>

                    <!-- Step 5 -->
                    <div class="relative flex flex-col md:flex-row items-start md:items-center justify-between group">
                        <div class="w-full md:w-[45%] text-left md:text-right pl-16 md:pl-0 md:pr-8">
                            <span class="text-[10px] font-black text-amber-400 uppercase tracking-widest">Step 05</span>
                            <h3 class="text-lg font-bold text-white group-hover:text-amber-400 transition">Parents upload details</h3>
                            <div class="flex flex-wrap md:justify-end gap-1.5 mt-2">
                                <span class="px-2 py-0.5 rounded bg-slate-900 border border-slate-800 text-[10px] text-slate-300">Student Photo</span>
                                <span class="px-2 py-0.5 rounded bg-slate-900 border border-slate-800 text-[10px] text-slate-300">Parent Photo</span>
                                <span class="px-2 py-0.5 rounded bg-slate-900 border border-slate-800 text-[10px] text-slate-300">Signature</span>
                                <span class="px-2 py-0.5 rounded bg-slate-900 border border-slate-800 text-[10px] text-slate-300">Required Documents</span>
                            </div>
                        </div>
                        <div class="absolute left-8 md:left-1/2 w-10 h-10 -translate-x-1/2 rounded-full bg-slate-950 border-2 border-amber-400 flex items-center justify-center text-sm font-bold text-amber-400 z-10 shadow-lg shadow-amber-400/10 animate-pulse">
                            05
                        </div>
                        <div class="w-full md:w-[45%] pl-8 hidden md:block"></div>
                    </div>

                    <!-- Step 6 -->
                    <div class="relative flex flex-col md:flex-row items-start md:items-center justify-between group">
                        <div class="w-full md:w-[45%] pr-8 hidden md:block"></div>
                        <div class="absolute left-8 md:left-1/2 w-10 h-10 -translate-x-1/2 rounded-full bg-slate-950 border border-slate-800 flex items-center justify-center text-sm font-bold text-slate-300 group-hover:border-amber-400 transition z-10">
                            06
                        </div>
                        <div class="w-full md:w-[45%] text-left pl-16 md:pl-8">
                            <span class="text-[10px] font-black text-amber-400 uppercase tracking-widest">Step 06</span>
                            <h3 class="text-lg font-bold text-white group-hover:text-amber-400 transition">Teachers verify</h3>
                        </div>
                    </div>

                    <!-- Step 7 -->
                    <div class="relative flex flex-col md:flex-row items-start md:items-center justify-between group">
                        <div class="w-full md:w-[45%] text-left md:text-right pl-16 md:pl-0 md:pr-8">
                            <span class="text-[10px] font-black text-amber-400 uppercase tracking-widest">Step 07</span>
                            <h3 class="text-lg font-bold text-white group-hover:text-amber-400 transition">School Admin approves</h3>
                        </div>
                        <div class="absolute left-8 md:left-1/2 w-10 h-10 -translate-x-1/2 rounded-full bg-slate-950 border border-slate-800 flex items-center justify-center text-sm font-bold text-slate-300 group-hover:border-amber-400 transition z-10">
                            07
                        </div>
                        <div class="w-full md:w-[45%] pl-8 hidden md:block"></div>
                    </div>

                    <!-- Step 8 -->
                    <div class="relative flex flex-col md:flex-row items-start md:items-center justify-between group">
                        <div class="w-full md:w-[45%] pr-8 hidden md:block"></div>
                        <div class="absolute left-8 md:left-1/2 w-10 h-10 -translate-x-1/2 rounded-full bg-slate-950 border border-slate-800 flex items-center justify-center text-sm font-bold text-slate-300 group-hover:border-amber-400 transition z-10">
                            08
                        </div>
                        <div class="w-full md:w-[45%] text-left pl-16 md:pl-8">
                            <span class="text-[10px] font-black text-amber-400 uppercase tracking-widest">Step 08</span>
                            <h3 class="text-lg font-bold text-white group-hover:text-amber-400 transition">Printing company downloads print-ready data</h3>
                        </div>
                    </div>

                    <!-- Step 9 -->
                    <div class="relative flex flex-col md:flex-row items-start md:items-center justify-between group">
                        <div class="w-full md:w-[45%] text-left md:text-right pl-16 md:pl-0 md:pr-8">
                            <span class="text-[10px] font-black text-amber-400 uppercase tracking-widest">Step 09</span>
                            <h3 class="text-lg font-bold text-white group-hover:text-amber-400 transition">Professional ID cards printed</h3>
                        </div>
                        <div class="absolute left-8 md:left-1/2 w-10 h-10 -translate-x-1/2 rounded-full bg-slate-950 border border-slate-800 flex items-center justify-center text-sm font-bold text-slate-300 group-hover:border-amber-400 transition z-10">
                            09
                        </div>
                        <div class="w-full md:w-[45%] pl-8 hidden md:block"></div>
                    </div>
                </div>
            </div>
        </section>


        <!-- Features Benefit Grid Section -->
        <section class="relative z-10 max-w-7xl mx-auto w-full px-6 py-16 border-t border-slate-900">
            <div class="text-center max-w-2xl mx-auto space-y-3 mb-16">
                <span class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-slate-900 border border-slate-800 text-[10px] font-bold tracking-widest text-amber-400 uppercase">
                    Core Benefits
                </span>
                <h2 class="text-3xl sm:text-4xl font-bold text-white">Why Schools Trust iCard Maker</h2>
                <p class="text-sm text-slate-400">Our unified student badge system eliminates administrative friction and printing errors.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Card 1 -->
                <div class="p-8 rounded-3xl bg-slate-900/40 border border-slate-850 hover:border-slate-800 transition duration-300 group shadow-lg">
                    <div class="w-10 h-10 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 mb-6 group-hover:scale-110 transition duration-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Student Data Security</h3>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Complete compliance and localized SQLite database storage to protect sensitive student records and profile imagery.
                    </p>
                </div>

                <!-- Card 2 -->
                <div class="p-8 rounded-3xl bg-slate-900/40 border border-slate-850 hover:border-slate-800 transition duration-300 group shadow-lg">
                    <div class="w-10 h-10 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 mb-6 group-hover:scale-110 transition duration-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122" /></svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Real-time Layout Controls</h3>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Design pixel-perfect layout configurations. Instantly adjust field variables, alignments, colors, and margins.
                    </p>
                </div>

                <!-- Card 3 -->
                <div class="p-8 rounded-3xl bg-slate-900/40 border border-slate-850 hover:border-slate-800 transition duration-300 group shadow-lg">
                    <div class="w-10 h-10 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 mb-6 group-hover:scale-110 transition duration-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Instant CSV Integrations</h3>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Import thousands of students in seconds using our standard CSV/Excel roster import sheet templates.
                    </p>
                </div>
            </div>
        </section>

        <!-- FAQ Accordion Section -->
        <section class="relative z-10 max-w-4xl mx-auto w-full px-6 py-16 border-t border-slate-900">
            <div class="text-center space-y-3 mb-12">
                <h2 class="text-3xl font-bold text-white">Frequently Asked Questions</h2>
                <p class="text-sm text-slate-400 font-semibold">Got questions? We've got answers.</p>
            </div>

            <div class="space-y-4">
                <details class="group bg-slate-900/40 border border-slate-850 rounded-2xl p-6 [&_summary::-webkit-details-marker]:hidden">
                    <summary class="flex justify-between items-center cursor-pointer outline-none">
                        <h4 class="text-sm font-semibold text-white pr-4">Do we need specialized printing hardware?</h4>
                        <span class="transition duration-300 group-open:-rotate-180 text-amber-400">&darr;</span>
                    </summary>
                    <p class="text-xs text-slate-400 mt-4 leading-relaxed">
                        No specialized printing hardware is required from our app side. The system exports standard print-ready PDF sheets (typically A4 format with standard ID card margins) which can be sent to any normal office printer or professional commercial badge printer.
                    </p>
                </details>


                
                <details class="group bg-slate-900/40 border border-slate-850 rounded-2xl p-6 [&_summary::-webkit-details-marker]:hidden">
                    <summary class="flex justify-between items-center cursor-pointer outline-none">
                        <h4 class="text-sm font-semibold text-white pr-4">Can parents upload photos using their mobile phones?</h4>
                        <span class="transition duration-300 group-open:-rotate-180 text-amber-400">&darr;</span>
                    </summary>
                    <p class="text-xs text-slate-400 mt-4 leading-relaxed">
                        Yes! With photo collection campaigns, administrators send parents dynamic mobile upload links. Parents open the link, snap a picture of their child, and the browser crops and submits it automatically.
                    </p>
                </details>

                <details class="group bg-slate-900/40 border border-slate-850 rounded-2xl p-6 [&_summary::-webkit-details-marker]:hidden">
                    <summary class="flex justify-between items-center cursor-pointer outline-none">
                        <h4 class="text-sm font-semibold text-white pr-4">Is student data secure on your server?</h4>
                        <span class="transition duration-300 group-open:-rotate-180 text-amber-400">&darr;</span>
                    </summary>
                    <p class="text-xs text-slate-400 mt-4 leading-relaxed">
                        Data privacy is our top priority. We use strict database level protection, access control tokens, and encrypted storage configurations. Student details are kept strictly within school boundaries.
                    </p>
                </details>
            </div>
        </section>

        <!-- Android Mobile App Showcase Section -->
        <section class="relative z-10 max-w-7xl mx-auto w-full px-6 py-16 border-t border-slate-900">
            <div class="p-8 sm:p-12 rounded-3xl bg-gradient-to-r from-slate-900 via-indigo-950/20 to-slate-950 border border-slate-850 relative overflow-hidden shadow-2xl">
                <!-- Glowing effect -->
                <div class="absolute -top-12 -right-12 w-64 h-64 bg-amber-500/5 rounded-full blur-3xl pointer-events-none"></div>
                <div class="absolute -bottom-12 -left-12 w-64 h-64 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center text-left">
                    <div class="space-y-6">
                        <span class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-slate-950 border border-slate-800 text-[10px] font-bold tracking-widest text-amber-400 uppercase">
                            Android App Available
                        </span>
                        <h2 class="text-3xl sm:text-4xl font-extrabold text-white leading-tight">
                            Download iCard Maker for Android
                        </h2>
                        <p class="text-sm text-slate-400 leading-relaxed">
                            Our native Android application provides a convenient portal for school admins, teachers, and parents. Easily upload data, crop photos, and track verification queues directly from your phone.
                        </p>
                        
                        <div class="space-y-3 text-xs text-slate-300">
                            <div class="flex items-center space-x-2.5">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                                <span><strong class="text-white">Admins & Staff:</strong> Sync rosters, configure classes, and oversee campaigns.</span>
                            </div>
                            <div class="flex items-center space-x-2.5">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                                <span><strong class="text-white">Teachers:</strong> Capture and crop candidate photos instantly in the classroom.</span>
                            </div>
                            <div class="flex items-center space-x-2.5">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                                <span><strong class="text-white">Parents:</strong> Submit student info and photos without web logins.</span>
                            </div>
                        </div>

                        <!-- Google Play Badge -->
                        <div class="pt-2">
                            <a href="https://play.google.com/store/apps/details?id=com.infoleena.icard.maker" target="_blank" class="inline-flex items-center space-x-3 px-5 py-2.5 bg-black border border-slate-800 hover:border-slate-700 rounded-xl transition duration-200 shadow-md">
                                <svg class="w-6 h-6 text-amber-400" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M5 3.00003C5 2.50003 5.4 2.20003 5.9 2.40003L21.4 11.4C21.8 11.6 21.8 12.4 21.4 12.6L5.9 21.6C5.4 21.8 5 21.5 5 21V3.00003Z"/>
                                </svg>
                                <div class="text-left leading-none">
                                    <p class="text-[9px] text-slate-400 font-semibold tracking-wider uppercase">Get it on</p>
                                    <p class="text-sm font-bold text-white mt-1">Google Play</p>
                                </div>
                            </a>
                        </div>
                    </div>

                    <!-- App Stats / Features Grid -->
                    <div class="bg-slate-950/60 border border-slate-850 rounded-2xl p-6 space-y-4 text-left">
                        <h4 class="text-xs font-black text-slate-500 uppercase tracking-widest border-b border-slate-900 pb-3">Mobile Features</h4>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="p-4 bg-slate-900/40 rounded-xl border border-slate-900">
                                <p class="text-xl font-bold text-white">Camera Sync</p>
                                <p class="text-[10px] text-slate-500 mt-1">Direct crop and upload controls</p>
                            </div>
                            <div class="p-4 bg-slate-900/40 rounded-xl border border-slate-900">
                                <p class="text-xl font-bold text-white">Push Alert</p>
                                <p class="text-[10px] text-slate-500 mt-1">Instant updates on badge status</p>
                            </div>
                            <div class="p-4 bg-slate-900/40 rounded-xl border border-slate-900">
                                <p class="text-xl font-bold text-white">Secure Sync</p>
                                <p class="text-[10px] text-slate-500 mt-1">Linked directly with Web database</p>
                            </div>
                            <div class="p-4 bg-slate-900/40 rounded-xl border border-slate-900">
                                <p class="text-xl font-bold text-white">Fast Setup</p>
                                <p class="text-[10px] text-slate-500 mt-1">Login using registered number</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Final Call To Action Banner -->
        <section class="relative z-10 max-w-7xl mx-auto w-full px-6 py-16">
            <div class="p-8 sm:p-12 rounded-3xl bg-gradient-to-r from-slate-900 via-slate-850 to-indigo-950/80 border border-slate-800 text-center space-y-6 relative overflow-hidden shadow-2xl">
                <!-- Decorative Glow -->
                <div class="absolute -top-12 -left-12 w-32 h-32 bg-amber-500/10 rounded-full blur-2xl"></div>
                <div class="absolute -bottom-12 -right-12 w-32 h-32 bg-indigo-500/10 rounded-full blur-2xl"></div>

                <div class="max-w-2xl mx-auto space-y-4">
                    <h2 class="text-3xl sm:text-4xl font-extrabold text-white leading-tight">
                        Elevate your school's identity infrastructure today
                    </h2>
                    <p class="text-sm text-slate-400 leading-relaxed">
                        Create templates, invite colleagues, gather photos via campaigns, and print high-quality badges in less than 5 minutes.
                    </p>
                </div>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="{{ route('register') }}" class="inline-flex items-center justify-center px-6 py-3.5 text-sm font-bold text-slate-950 bg-gradient-to-r from-amber-400 to-amber-500 hover:from-amber-300 hover:to-amber-400 rounded-xl transition duration-200 shadow-lg shadow-amber-500/20">
                        Get Started Free
                    </a>
                    <a href="/features" class="inline-flex items-center justify-center px-6 py-3.5 text-sm font-bold text-slate-300 hover:text-white bg-slate-950 hover:bg-slate-900 border border-slate-800 rounded-xl transition duration-200">
                        Explore Features
                    </a>
                </div>
            </div>
        </section>
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
