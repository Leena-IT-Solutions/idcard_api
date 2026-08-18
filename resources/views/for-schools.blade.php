<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>For Schools - iCard Maker</title>
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
                        <div class="dropdown-menu hidden group-hover/solutions:block lg:absolute lg:left-1/2 lg:-translate-x-1/2 lg:top-full lg:pt-2 w-full lg:w-48 z-50">
                            <div class="bg-slate-950 lg:border lg:border-slate-900 rounded-xl py-2 lg:shadow-xl">
                                <a href="/for-schools" class="block px-4 py-2 text-sm text-slate-300 hover:text-amber-400 hover:bg-slate-900 transition duration-150 text-center lg:text-left">For Schools</a>
                                <a href="/for-printing-companies" class="block px-4 py-2 text-sm text-slate-300 hover:text-amber-400 hover:bg-slate-900 transition duration-150 text-center lg:text-left">For Printing Companies</a>
                            </div>
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
                    🏫 For Campuses & Educational Institutions
                </span>
                <h1 class="text-4xl sm:text-6xl font-black tracking-tight leading-tight text-white">
                    The Smarter Way to <span class="bg-gradient-to-r from-amber-400 via-amber-500 to-orange-400 bg-clip-text text-transparent">Manage Student ID Cards</span>
                </h1>
                <p class="text-lg sm:text-xl text-slate-400 leading-relaxed max-w-2xl mx-auto">
                    From collecting student information to printing final ID cards, manage everything from one platform.
                </p>
                <div class="pt-4 flex flex-col sm:flex-row items-center justify-center gap-4">
                    <a href="/contact" class="inline-flex items-center justify-center px-6 py-3 text-base font-bold text-slate-950 bg-gradient-to-r from-amber-400 to-amber-500 hover:from-amber-300 hover:to-amber-400 rounded-xl transition duration-200 shadow-lg shadow-amber-500/20">
                        Book School Demo
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
                        The Problem
                    </span>
                    <h2 class="text-2xl sm:text-4xl font-extrabold text-white leading-tight">
                        Your School's ID Card Process Shouldn't Look Like This
                    </h2>
                </div>
                
                <!-- Horizontal flow layout for the problem -->
                <div class="relative bg-slate-900/40 border border-slate-850 rounded-3xl p-8 max-w-4xl mx-auto overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-r from-red-500/5 to-transparent blur-3xl pointer-events-none"></div>
                    <div class="relative flex flex-col md:flex-row items-center justify-center gap-4 md:gap-2">
                        <div class="px-4 py-3 rounded-2xl bg-slate-950 border border-slate-900/80 text-sm font-semibold text-slate-400 w-full md:w-auto min-w-[120px] shadow">
                            WhatsApp
                        </div>
                        <span class="text-slate-600 rotate-90 md:rotate-0 font-bold">&rarr;</span>
                        <div class="px-4 py-3 rounded-2xl bg-slate-950 border border-slate-900/80 text-sm font-semibold text-slate-400 w-full md:w-auto min-w-[120px] shadow">
                            Excel
                        </div>
                        <span class="text-slate-600 rotate-90 md:rotate-0 font-bold">&rarr;</span>
                        <div class="px-4 py-3 rounded-2xl bg-slate-950 border border-slate-900/80 text-sm font-semibold text-slate-400 w-full md:w-auto min-w-[120px] shadow">
                            Phone Calls
                        </div>
                        <span class="text-slate-600 rotate-90 md:rotate-0 font-bold">&rarr;</span>
                        <div class="px-4 py-3 rounded-2xl bg-slate-950 border border-slate-900/80 text-sm font-semibold text-slate-400 w-full md:w-auto min-w-[120px] shadow">
                            Corrections
                        </div>
                        <span class="text-slate-600 rotate-90 md:rotate-0 font-bold">&rarr;</span>
                        <div class="px-4 py-3 rounded-2xl bg-slate-950 border border-slate-900/80 text-sm font-semibold text-slate-400 w-full md:w-auto min-w-[120px] shadow">
                            Printing
                        </div>
                        <span class="text-red-500 rotate-90 md:rotate-0 font-black">&rarr;</span>
                        <div class="px-5 py-3.5 rounded-2xl bg-red-950/20 border border-red-950/40 text-sm font-extrabold text-red-400 w-full md:w-auto min-w-[130px] shadow-lg animate-pulse">
                            Reprinting
                        </div>
                    </div>
                </div>
            </section>

            <!-- Solution Section -->
            <section class="max-w-5xl mx-auto mb-16 border-t border-slate-900 pt-16 text-center">
                <div class="space-y-3 mb-12">
                    <span class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-emerald-950/40 border border-emerald-900/30 text-[10px] font-bold tracking-widest text-emerald-400 uppercase">
                        The Solution
                    </span>
                    <h2 class="text-2xl sm:text-4xl font-extrabold text-white leading-tight">
                        One Digital Workflow
                    </h2>
                </div>
                
                <!-- Horizontal flow layout for the solution -->
                <div class="relative bg-slate-900/40 border border-slate-850 rounded-3xl p-8 max-w-4xl mx-auto overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-r from-emerald-500/5 to-transparent blur-3xl pointer-events-none"></div>
                    <div class="relative flex flex-col md:flex-row items-center justify-center gap-4 md:gap-2">
                        <div class="px-4 py-3 rounded-2xl bg-slate-950 border border-slate-900/80 text-sm font-bold text-slate-200 w-full md:w-auto min-w-[125px] shadow group hover:border-amber-400 transition">
                            School Admin
                        </div>
                        <span class="text-amber-500/60 rotate-90 md:rotate-0 font-bold">&rarr;</span>
                        <div class="px-4 py-3 rounded-2xl bg-slate-950 border border-slate-900/80 text-sm font-bold text-slate-200 w-full md:w-auto min-w-[125px] shadow group hover:border-amber-400 transition">
                            Teacher
                        </div>
                        <span class="text-amber-500/60 rotate-90 md:rotate-0 font-bold">&rarr;</span>
                        <div class="px-4 py-3 rounded-2xl bg-slate-950 border border-slate-900/80 text-sm font-bold text-slate-200 w-full md:w-auto min-w-[125px] shadow group hover:border-amber-400 transition">
                            Parent
                        </div>
                        <span class="text-amber-500/60 rotate-90 md:rotate-0 font-bold">&rarr;</span>
                        <div class="px-4 py-3 rounded-2xl bg-slate-950 border border-slate-900/80 text-sm font-bold text-slate-200 w-full md:w-auto min-w-[135px] shadow group hover:border-amber-400 transition">
                            Student Database
                        </div>
                        <span class="text-amber-500/60 rotate-90 md:rotate-0 font-bold">&rarr;</span>
                        <div class="px-4 py-3 rounded-2xl bg-slate-950 border border-slate-900/80 text-sm font-bold text-slate-200 w-full md:w-auto min-w-[125px] shadow group hover:border-amber-400 transition">
                            ID Card
                        </div>
                        <span class="text-emerald-400 rotate-90 md:rotate-0 font-black">&rarr;</span>
                        <div class="px-5 py-3.5 rounded-2xl bg-emerald-950/20 border border-emerald-950/40 text-sm font-black text-emerald-400 w-full md:w-auto min-w-[125px] shadow-lg">
                            Printer
                        </div>
                    </div>
                </div>
            </section>

            <!-- School Features Section -->
            <section class="max-w-5xl mx-auto mb-16 border-t border-slate-900 pt-16 text-center">
                <div class="space-y-3 mb-16">
                    <span class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-slate-900 border border-slate-800 text-[10px] font-bold tracking-widest text-amber-400 uppercase">
                        Core Capabilities
                    </span>
                    <h2 class="text-2xl sm:text-4xl font-extrabold text-white leading-tight">
                        School Features
                    </h2>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 text-left">
                    <!-- Feature 1: Multi-school management -->
                    <div class="p-6 rounded-2xl bg-slate-900/40 border border-slate-850 hover:border-slate-800 transition duration-300 shadow group">
                        <div class="w-9 h-9 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 mb-4 group-hover:scale-105 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                        </div>
                        <h3 class="text-sm font-bold text-white mb-1.5">Multi-School Management</h3>
                        <p class="text-xs text-slate-400 leading-relaxed">Administer multiple school campuses, brand lines, and configurations in a single unified cockpit.</p>
                    </div>

                    <!-- Feature 2: Teacher management -->
                    <div class="p-6 rounded-2xl bg-slate-900/40 border border-slate-850 hover:border-slate-800 transition duration-300 shadow group">
                        <div class="w-9 h-9 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 mb-4 group-hover:scale-105 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                        </div>
                        <h3 class="text-sm font-bold text-white mb-1.5">Teacher Management</h3>
                        <p class="text-xs text-slate-400 leading-relaxed">Invite class teachers, assign classroom divisions, and delegate verification controls seamlessly.</p>
                    </div>

                    <!-- Feature 3: Parent access -->
                    <div class="p-6 rounded-2xl bg-slate-900/40 border border-slate-850 hover:border-slate-800 transition duration-300 shadow group">
                        <div class="w-9 h-9 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 mb-4 group-hover:scale-105 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" /></svg>
                        </div>
                        <h3 class="text-sm font-bold text-white mb-1.5">Parent Access</h3>
                        <p class="text-xs text-slate-400 leading-relaxed">Send secure web and Android mobile link credentials so parents can upload student headshots directly.</p>
                    </div>

                    <!-- Feature 4: Student management -->
                    <div class="p-6 rounded-2xl bg-slate-900/40 border border-slate-850 hover:border-slate-800 transition duration-300 shadow group">
                        <div class="w-9 h-9 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 mb-4 group-hover:scale-105 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                        </div>
                        <h3 class="text-sm font-bold text-white mb-1.5">Student Management</h3>
                        <p class="text-xs text-slate-400 leading-relaxed">Central directory to search, filter, edit student demographics, or assign templates in bulk.</p>
                    </div>

                    <!-- Feature 5: Campaigns -->
                    <div class="p-6 rounded-2xl bg-slate-900/40 border border-slate-850 hover:border-slate-800 transition duration-300 shadow group">
                        <div class="w-9 h-9 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 mb-4 group-hover:scale-105 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                        </div>
                        <h3 class="text-sm font-bold text-white mb-1.5">Campaigns</h3>
                        <p class="text-xs text-slate-400 leading-relaxed">Launch registration campaigns to gather candidate profile details for the academic year.</p>
                    </div>

                    <!-- Feature 6: Data verification -->
                    <div class="p-6 rounded-2xl bg-slate-900/40 border border-slate-850 hover:border-slate-800 transition duration-300 shadow group">
                        <div class="w-9 h-9 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 mb-4 group-hover:scale-105 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                        </div>
                        <h3 class="text-sm font-bold text-white mb-1.5">Data Verification</h3>
                        <p class="text-xs text-slate-400 leading-relaxed">Enforce auto-revoke security to reset approvals if credentials change, preventing mistakes.</p>
                    </div>

                    <!-- Feature 7: Templates -->
                    <div class="p-6 rounded-2xl bg-slate-900/40 border border-slate-850 hover:border-slate-800 transition duration-300 shadow group">
                        <div class="w-9 h-9 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 mb-4 group-hover:scale-105 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" /></svg>
                        </div>
                        <h3 class="text-sm font-bold text-white mb-1.5">Templates</h3>
                        <p class="text-xs text-slate-400 leading-relaxed">Design beautiful landscape/portrait designs with custom attributes and positioning systems.</p>
                    </div>

                    <!-- Feature 8: PDF generation -->
                    <div class="p-6 rounded-2xl bg-slate-900/40 border border-slate-850 hover:border-slate-800 transition duration-300 shadow group">
                        <div class="w-9 h-9 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 mb-4 group-hover:scale-105 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                        </div>
                        <h3 class="text-sm font-bold text-white mb-1.5">PDF Generation</h3>
                        <p class="text-xs text-slate-400 leading-relaxed">Generate print-ready, high-resolution imposition PDF sheets with dynamic fields and cut margins.</p>
                    </div>
                </div>
            </section>

            <!-- Benefits Section -->
            <section class="max-w-5xl mx-auto mb-16 border-t border-slate-900 pt-16 text-center">
                <div class="space-y-3 mb-16">
                    <span class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-slate-900 border border-slate-800 text-[10px] font-bold tracking-widest text-amber-400 uppercase">
                        Key Value
                    </span>
                    <h2 class="text-2xl sm:text-4xl font-extrabold text-white leading-tight">
                        Platform Benefits
                    </h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 text-left">
                    <!-- School Administrators -->
                    <div class="p-8 rounded-3xl bg-slate-900/40 border border-slate-850 hover:border-amber-500/20 transition duration-300 shadow group flex flex-col justify-between">
                        <div>
                            <div class="flex items-center space-x-3 mb-6">
                                <div class="w-8 h-8 rounded-lg bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                                </div>
                                <h3 class="text-sm font-bold text-white uppercase tracking-wider">For School Administrators</h3>
                            </div>
                            <ul class="space-y-3.5 text-xs text-slate-300">
                                <li class="flex items-start space-x-3">
                                    <span class="text-amber-400 mt-0.5 font-bold">&check;</span>
                                    <span>Save administrative time</span>
                                </li>
                                <li class="flex items-start space-x-3">
                                    <span class="text-amber-400 mt-0.5 font-bold">&check;</span>
                                    <span>Reduce data errors</span>
                                </li>
                                <li class="flex items-start space-x-3">
                                    <span class="text-amber-400 mt-0.5 font-bold">&check;</span>
                                    <span>Centralize student information</span>
                                </li>
                                <li class="flex items-start space-x-3">
                                    <span class="text-amber-400 mt-0.5 font-bold">&check;</span>
                                    <span>Track collection progress</span>
                                </li>
                                <li class="flex items-start space-x-3">
                                    <span class="text-amber-400 mt-0.5 font-bold">&check;</span>
                                    <span>Control user access</span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Teachers -->
                    <div class="p-8 rounded-3xl bg-slate-900/40 border border-slate-850 hover:border-amber-500/20 transition duration-300 shadow group flex flex-col justify-between">
                        <div>
                            <div class="flex items-center space-x-3 mb-6">
                                <div class="w-8 h-8 rounded-lg bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                                </div>
                                <h3 class="text-sm font-bold text-white uppercase tracking-wider">For Teachers</h3>
                            </div>
                            <ul class="space-y-3.5 text-xs text-slate-300">
                                <li class="flex items-start space-x-3">
                                    <span class="text-amber-400 mt-0.5 font-bold">&check;</span>
                                    <span>Manage assigned classes</span>
                                </li>
                                <li class="flex items-start space-x-3">
                                    <span class="text-amber-400 mt-0.5 font-bold">&check;</span>
                                    <span>Verify information</span>
                                </li>
                                <li class="flex items-start space-x-3">
                                    <span class="text-amber-400 mt-0.5 font-bold">&check;</span>
                                    <span>Reduce paperwork</span>
                                </li>
                                <li class="flex items-start space-x-3">
                                    <span class="text-amber-400 mt-0.5 font-bold">&check;</span>
                                    <span>Update student records</span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Parents -->
                    <div class="p-8 rounded-3xl bg-slate-900/40 border border-slate-850 hover:border-amber-500/20 transition duration-300 shadow group flex flex-col justify-between">
                        <div>
                            <div class="flex items-center space-x-3 mb-6">
                                <div class="w-8 h-8 rounded-lg bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                </div>
                                <h3 class="text-sm font-bold text-white uppercase tracking-wider">For Parents</h3>
                            </div>
                            <ul class="space-y-3.5 text-xs text-slate-300">
                                <li class="flex items-start space-x-3">
                                    <span class="text-amber-400 mt-0.5 font-bold">&check;</span>
                                    <span>Submit data from mobile</span>
                                </li>
                                <li class="flex items-start space-x-3">
                                    <span class="text-amber-400 mt-0.5 font-bold">&check;</span>
                                    <span>Upload photographs</span>
                                </li>
                                <li class="flex items-start space-x-3">
                                    <span class="text-amber-400 mt-0.5 font-bold">&check;</span>
                                    <span>No repeated school visits</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Interactive CTA Section -->
            <div class="mt-20 p-8 rounded-3xl bg-gradient-to-r from-slate-900 via-slate-850 to-indigo-950 border border-slate-800 flex flex-col md:flex-row items-center justify-between gap-6">
                <div class="text-left">
                    <h3 class="text-2xl font-bold text-white">Book a Demo for Your School</h3>
                    <p class="text-slate-400 text-sm mt-1">Get an interactive walkthrough of the platform and custom template designs.</p>
                </div>
                <a href="/contact" class="inline-flex items-center justify-center px-6 py-3 text-base font-bold text-slate-950 bg-gradient-to-r from-amber-400 to-amber-500 hover:from-amber-300 hover:to-amber-400 rounded-xl transition duration-200 shadow-lg shadow-amber-500/20 whitespace-nowrap">
                    Book a Demo &rarr;
                </a>
            </div>
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
