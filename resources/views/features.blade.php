<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Features - iCard Maker</title>
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
                    <a href="/features" class="text-sm font-semibold text-amber-400 hover:text-amber-305 transition duration-200 w-full lg:w-auto text-center py-2 lg:py-0">Features</a>
                    <a href="/how-it-works" class="text-sm font-semibold text-slate-300 hover:text-amber-400 transition duration-200 w-full lg:w-auto text-center py-2 lg:py-0">How It Works</a>
                    
                    <!-- Solutions Dropdown -->
                    <div class="relative group/solutions w-full lg:w-auto text-center lg:text-left">
                        <button type="button" class="dropdown-trigger flex items-center justify-center lg:justify-start gap-1 mx-auto lg:mx-0 text-sm font-semibold text-slate-300 hover:text-amber-400 focus:outline-none transition duration-200 w-full lg:w-auto py-2 lg:py-0">
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
                                <a href="{{ route('register') }}" class="inline-flex items-center justify-center px-4 py-2 text-sm font-bold text-slate-955 bg-gradient-to-r from-amber-400 to-amber-500 hover:from-amber-300 hover:to-amber-400 rounded-xl transition duration-200 shadow-md shadow-amber-500/10">Register</a>
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

        <!-- Main Features Content -->
        <main class="relative z-10 max-w-7xl mx-auto w-full px-6 py-16 flex-grow">
            <!-- Hero Header -->
            <div class="text-center max-w-3xl mx-auto space-y-4 mb-16">
                <span class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-slate-900 border border-slate-800 text-xs font-semibold tracking-wide text-amber-400">
                    ✨ Features
                </span>
                <h1 class="text-4xl sm:text-5xl font-black tracking-tight leading-tight text-white">
                    Everything You Need to <span class="bg-gradient-to-r from-amber-400 via-amber-500 to-orange-400 bg-clip-text text-transparent">Manage Student ID Cards</span>
                </h1>
                <p class="text-lg text-slate-400 leading-relaxed max-w-2xl mx-auto">
                    Powerful tools for school administration, data collection, student management, ID card design and printing.
                </p>
            </div>            <!-- Detailed Feature Categories (9 Sections) -->
            <div class="space-y-32 mt-24">
                
                <!-- 1. School Management -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                    <div class="lg:col-span-5 space-y-6 text-left">
                        <span class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-slate-900 border border-slate-800 text-[10px] font-bold tracking-widest text-amber-400 uppercase">
                            01 &bull; School Administration
                        </span>
                        <h2 class="text-3xl sm:text-4xl font-black text-white leading-tight">School Profiles & Branding</h2>
                        <p class="text-sm text-slate-400 leading-relaxed">
                            Configure multiple school campuses, brand assets, logos, and essential details dynamically. Switch dashboard context instantly to manage different schools under a single master account.
                        </p>
                        <div class="flex flex-wrap gap-1.5">
                            <span class="px-2.5 py-0.5 rounded-full bg-slate-900 border border-slate-800 text-[10px] text-slate-400">School Profiles</span>
                            <span class="px-2.5 py-0.5 rounded-full bg-slate-900 border border-slate-800 text-[10px] text-slate-400">Multiple Schools</span>
                            <span class="px-2.5 py-0.5 rounded-full bg-slate-900 border border-slate-800 text-[10px] text-slate-400">Branding Presets</span>
                        </div>
                        <ul class="space-y-2.5 text-xs text-slate-300">
                            <li class="flex items-center space-x-2.5">
                                <span class="w-4 h-4 rounded bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 font-bold text-[9px]">&check;</span>
                                <span>Logo & custom color integrations</span>
                            </li>
                            <li class="flex items-center space-x-2.5">
                                <span class="w-4 h-4 rounded bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 font-bold text-[9px]">&check;</span>
                                <span>Global school context switching controls</span>
                            </li>
                        </ul>
                    </div>
                    <div class="lg:col-span-7 relative group">
                        <div class="absolute -inset-2 rounded-3xl bg-gradient-to-tr from-amber-500/10 to-indigo-500/5 blur-2xl opacity-60 group-hover:opacity-80 transition duration-500"></div>
                        <div class="relative rounded-2xl overflow-hidden border border-slate-850 bg-slate-950 p-2.5 shadow-2xl">
                            <img src="{{ asset('images/screenshots/s1.png') }}" alt="School Profiles screenshot" class="w-full h-auto rounded-xl object-cover shadow-inner" />
                        </div>
                    </div>
                </div>

                <!-- 2. User & Role Management -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                    <div class="lg:col-span-7 lg:order-2 space-y-6 text-left">
                        <span class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-slate-900 border border-slate-800 text-[10px] font-bold tracking-widest text-amber-400 uppercase">
                            02 &bull; Security & Permissions
                        </span>
                        <h2 class="text-3xl sm:text-4xl font-black text-white leading-tight">User & Role Management</h2>
                        <p class="text-sm text-slate-400 leading-relaxed">
                            Maintain complete organizational oversight with fine-grained role allocations. Secure access controls isolate capabilities for system operators, school administrators, and class teachers with invite setups.
                        </p>
                        <div class="flex flex-wrap gap-1.5">
                            <span class="px-2.5 py-0.5 rounded-full bg-slate-900 border border-slate-800 text-[10px] text-slate-400">School Admin</span>
                            <span class="px-2.5 py-0.5 rounded-full bg-slate-900 border border-slate-800 text-[10px] text-slate-400">Teachers</span>
                            <span class="px-2.5 py-0.5 rounded-full bg-slate-900 border border-slate-800 text-[10px] text-slate-400">RBAC Security</span>
                        </div>
                        <ul class="space-y-2.5 text-xs text-slate-300">
                            <li class="flex items-center space-x-2.5">
                                <span class="w-4 h-4 rounded bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 font-bold text-[9px]">&check;</span>
                                <span>Class and Grade assignments for staff members</span>
                            </li>
                            <li class="flex items-center space-x-2.5">
                                <span class="w-4 h-4 rounded bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 font-bold text-[9px]">&check;</span>
                                <span>Teacher invitation flow & global school access limit configurations</span>
                            </li>
                        </ul>
                    </div>
                    <div class="lg:col-span-5 lg:order-1 relative group">
                        <div class="absolute -inset-2 rounded-3xl bg-gradient-to-tr from-indigo-500/10 to-amber-500/5 blur-2xl opacity-60 group-hover:opacity-80 transition duration-500"></div>
                        <div class="relative rounded-2xl overflow-hidden border border-slate-850 bg-slate-950 p-2.5 shadow-2xl">
                            <img src="{{ asset('images/screenshots/s2.png') }}" alt="User Roles screenshot" class="w-full h-auto rounded-xl object-cover shadow-inner" />
                        </div>
                    </div>
                </div>

                <!-- 3. Grade & Division Management -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                    <div class="lg:col-span-5 space-y-6 text-left">
                        <span class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-slate-900 border border-slate-800 text-[10px] font-bold tracking-widest text-amber-400 uppercase">
                            03 &bull; Class Structure
                        </span>
                        <h2 class="text-3xl sm:text-4xl font-black text-white leading-tight">Grade & Division Setup</h2>
                        <p class="text-sm text-slate-400 leading-relaxed">
                            Organize campus rosters with hierarchical grouping structures. Define grades, sections, and map specific divisions to teachers.
                        </p>
                        <div class="flex flex-wrap gap-1.5">
                            <span class="px-2.5 py-0.5 rounded-full bg-slate-900 border border-slate-800 text-[10px] text-slate-400">Grades</span>
                            <span class="px-2.5 py-0.5 rounded-full bg-slate-900 border border-slate-800 text-[10px] text-slate-400">Divisions</span>
                            <span class="px-2.5 py-0.5 rounded-full bg-slate-900 border border-slate-800 text-[10px] text-slate-400">Teacher Mapping</span>
                        </div>
                        <ul class="space-y-2.5 text-xs text-slate-300">
                            <li class="flex items-center space-x-2.5">
                                <span class="w-4 h-4 rounded bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 font-bold text-[9px]">&check;</span>
                                <span>Assign grades and divisions in seconds</span>
                            </li>
                        </ul>
                    </div>
                    <div class="lg:col-span-7 relative group">
                        <div class="absolute -inset-2 rounded-3xl bg-gradient-to-tr from-amber-500/10 to-indigo-500/5 blur-2xl opacity-60 group-hover:opacity-80 transition duration-500"></div>
                        <div class="relative rounded-2xl overflow-hidden border border-slate-850 bg-slate-950 p-2.5 shadow-2xl">
                            <img src="{{ asset('images/screenshots/s3.png') }}" alt="Grade and Division screenshot" class="w-full h-auto rounded-xl object-cover shadow-inner" />
                        </div>
                    </div>
                </div>

                <!-- 4. Campaign Management -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                    <div class="lg:col-span-7 lg:order-2 space-y-6 text-left">
                        <span class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-slate-900 border border-slate-800 text-[10px] font-bold tracking-widest text-amber-400 uppercase">
                            04 &bull; Data Collection Campaigns
                        </span>
                        <h2 class="text-3xl sm:text-4xl font-black text-white leading-tight">Campaign Management</h2>
                        <p class="text-sm text-slate-400 leading-relaxed">
                            Create time-bound campaigns for collecting student information during specific enrollment periods or academic cycles. Define clear deadlines and automate parent login allocations.
                        </p>
                        <div class="flex flex-wrap gap-1.5">
                            <span class="px-2.5 py-0.5 rounded-full bg-slate-900 border border-slate-800 text-[10px] text-slate-400">Time-Bound Campaigns</span>
                            <span class="px-2.5 py-0.5 rounded-full bg-slate-900 border border-slate-800 text-[10px] text-slate-400">Registration Cycles</span>
                        </div>
                    </div>
                    <div class="lg:col-span-5 lg:order-1 relative group">
                        <div class="absolute -inset-2 rounded-3xl bg-gradient-to-tr from-indigo-500/10 to-amber-500/5 blur-2xl opacity-60 group-hover:opacity-80 transition duration-500"></div>
                        <div class="relative rounded-2xl border border-slate-800 bg-slate-900/60 p-6 space-y-4 shadow-2xl text-left">
                            <div class="flex justify-between items-center pb-2 border-b border-slate-800">
                                <div>
                                    <h4 class="text-sm font-bold text-white uppercase tracking-wider">Active Campaign</h4>
                                    <p class="text-xs text-amber-400 font-black mt-0.5">iCard 2026–27</p>
                                </div>
                                <span class="px-2.5 py-1 text-[9px] font-bold text-emerald-400 bg-emerald-950/40 rounded-full border border-emerald-900/30">Registration Campaign</span>
                            </div>
                            <div class="grid grid-cols-2 gap-4 text-xs text-slate-400">
                                <div>
                                    <p class="uppercase font-semibold tracking-wider text-[10px]">Start Date</p>
                                    <p class="text-slate-200 font-bold mt-1">Aug 01, 2026</p>
                                </div>
                                <div>
                                    <p class="uppercase font-semibold tracking-wider text-[10px]">End Date</p>
                                    <p class="text-slate-200 font-bold mt-1">Sep 30, 2026</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 5. Parent Access Management -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                    <div class="lg:col-span-5 space-y-6 text-left">
                        <span class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-slate-900 border border-slate-800 text-[10px] font-bold tracking-widest text-amber-400 uppercase">
                            05 &bull; Guardian Security
                        </span>
                        <h2 class="text-3xl sm:text-4xl font-black text-white leading-tight">Parent Access Management</h2>
                        <p class="text-sm text-slate-400 leading-relaxed">
                            Control and authorize parent logins. Match parent mobile numbers during signup to automatically link student profiles, guaranteeing secure data capture.
                        </p>
                        <div class="flex flex-wrap gap-1.5">
                            <span class="px-2.5 py-0.5 rounded-full bg-slate-900 border border-slate-800 text-[10px] text-slate-400">Authorize Mobile Numbers</span>
                            <span class="px-2.5 py-0.5 rounded-full bg-slate-900 border border-slate-800 text-[10px] text-slate-400">Bulk Import</span>
                            <span class="px-2.5 py-0.5 rounded-full bg-slate-900 border border-slate-800 text-[10px] text-slate-400">Controlled Logins</span>
                        </div>
                    </div>
                    <div class="lg:col-span-7 relative group">
                        <div class="absolute -inset-2 rounded-3xl bg-gradient-to-tr from-amber-500/10 to-indigo-500/5 blur-2xl opacity-60 group-hover:opacity-80 transition duration-500"></div>
                        <div class="relative rounded-2xl overflow-hidden border border-slate-850 bg-slate-950 p-2.5 shadow-2xl">
                            <img src="{{ asset('images/screenshots/s5.png') }}" alt="Parent Access screenshot" class="w-full h-auto rounded-xl object-cover shadow-inner" />
                        </div>
                    </div>
                </div>

                <!-- 6. Student Management (BIG Section) -->
                <div class="p-8 sm:p-12 rounded-3xl bg-gradient-to-tr from-slate-900 via-indigo-950/20 to-slate-950 border border-slate-850 relative overflow-hidden shadow-2xl">
                    <div class="absolute -top-12 -right-12 w-96 h-96 bg-amber-500/5 rounded-full blur-3xl pointer-events-none"></div>
                    
                    <div class="max-w-4xl mx-auto space-y-8 text-center">
                        <span class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-slate-950 border border-slate-800 text-[10px] font-bold tracking-widest text-amber-400 uppercase">
                            06 &bull; Central Student Directory
                        </span>
                        <h2 class="text-3xl sm:text-5xl font-black text-white leading-none">Student Management Spotlight</h2>
                        <p class="text-sm sm:text-base text-slate-400 leading-relaxed max-w-2xl mx-auto">
                            The central core of the iCard Maker ecosystem. Filter, search, import, and download thousands of records with zero-latency controls.
                        </p>
                        
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-left">
                            <div class="p-4 bg-slate-950/80 rounded-2xl border border-slate-850">
                                <h4 class="text-xs font-bold text-white uppercase tracking-wider">Search & Filters</h4>
                                <p class="text-[11px] text-slate-400 mt-1">Locate records instantly by grade, section, or campaign attributes.</p>
                            </div>
                            <div class="p-4 bg-slate-950/80 rounded-2xl border border-slate-850">
                                <h4 class="text-xs font-bold text-white uppercase tracking-wider">Bulk Import / Export</h4>
                                <p class="text-[11px] text-slate-400 mt-1">Upload and match standard CSV/Excel roster databases in a single action.</p>
                            </div>
                            <div class="p-4 bg-slate-950/80 rounded-2xl border border-slate-850">
                                <h4 class="text-xs font-bold text-white uppercase tracking-wider">Template Linkage</h4>
                                <p class="text-[11px] text-slate-400 mt-1">Assign layout templates to individuals or entire grades dynamically.</p>
                            </div>
                            <div class="p-4 bg-slate-950/80 rounded-2xl border border-slate-850">
                                <h4 class="text-xs font-bold text-white uppercase tracking-wider">Full CRUD operations</h4>
                                <p class="text-[11px] text-slate-400 mt-1">Preview, crop images, modify fields, or delete candidate profiles.</p>
                            </div>
                        </div>

                        <!-- Large center mockup image -->
                        <div class="relative group mt-8">
                            <div class="absolute -inset-1 rounded-2xl bg-gradient-to-tr from-amber-500/10 to-indigo-500/10 blur-xl opacity-60"></div>
                            <div class="relative rounded-2xl overflow-hidden border border-slate-800 bg-slate-950 p-2 shadow-2xl">
                                <img src="{{ asset('images/screenshots/s6.png') }}" alt="Student Management Directory screenshot" class="w-full h-auto rounded-xl object-cover" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 7. Smart Student Registration -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                    <div class="lg:col-span-5 space-y-6 text-left">
                        <span class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-slate-900 border border-slate-800 text-[10px] font-bold tracking-widest text-amber-400 uppercase">
                            07 &bull; Frictionless Signup Portal
                        </span>
                        <h2 class="text-3xl sm:text-4xl font-black text-white leading-tight">Smart Student Registration</h2>
                        <p class="text-sm text-slate-400 leading-relaxed">
                            A fast, structured 4-step wizard interface designed specifically for parents. It minimizes input fatigue and enforces crop parameters directly in the client browser.
                        </p>
                        
                        <div class="p-4 rounded-2xl bg-amber-950/10 border border-amber-900/20">
                            <p class="text-xs font-bold text-amber-400 flex items-center gap-1.5">
                                <span>💡</span> Reusing Profile Functionality
                            </p>
                            <p class="text-[11px] text-slate-400 mt-1.5 leading-relaxed">
                                Parents can check mobile logs to automatically reuse existing profile details when enrolling siblings, preventing duplicate data entries.
                            </p>
                        </div>
                    </div>
                    <div class="lg:col-span-7 relative group">
                        <div class="absolute -inset-2 rounded-3xl bg-gradient-to-tr from-amber-500/10 to-indigo-500/5 blur-2xl opacity-60 group-hover:opacity-80 transition duration-500"></div>
                        <div class="relative rounded-2xl border border-slate-800 bg-slate-900/60 p-6 space-y-4 shadow-2xl text-left">
                            <h4 class="text-xs font-black text-slate-500 uppercase tracking-widest border-b border-slate-800 pb-2">Wizard Interface Workflow</h4>
                            <div class="grid grid-cols-4 gap-2 text-center text-[10px] font-bold">
                                <div class="p-2 bg-slate-950 rounded border border-slate-800 text-amber-400">
                                    <p class="text-slate-500 text-[8px] font-black uppercase">Step 01</p>
                                    <p class="mt-0.5">Mobile Check</p>
                                </div>
                                <div class="p-2 bg-slate-950 rounded border border-slate-800 text-slate-300">
                                    <p class="text-slate-500 text-[8px] font-black uppercase">Step 02</p>
                                    <p class="mt-0.5">Details</p>
                                </div>
                                <div class="p-2 bg-slate-950 rounded border border-slate-800 text-slate-300">
                                    <p class="text-slate-500 text-[8px] font-black uppercase">Step 03</p>
                                    <p class="mt-0.5">Campaign</p>
                                </div>
                                <div class="p-2 bg-slate-950 rounded border border-slate-850 text-slate-300">
                                    <p class="text-slate-500 text-[8px] font-black uppercase">Step 04</p>
                                    <p class="mt-0.5">Photo</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 8. ID Card Template Studio -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                    <div class="lg:col-span-5 lg:order-2 space-y-6 text-left">
                        <span class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-slate-900 border border-slate-800 text-[10px] font-bold tracking-widest text-amber-400 uppercase">
                            08 &bull; Drag & Drop Studio
                        </span>
                        <h2 class="text-3xl sm:text-4xl font-black text-white leading-tight">ID Card Template Studio</h2>
                        <p class="text-sm text-slate-400 leading-relaxed">
                            A powerful visual designer supporting portrait and landscape configurations. Setup master presets, position details dynamically, import/export template JSON structures, and configure backgrounds.
                        </p>
                        <div class="flex flex-wrap gap-1.5">
                            <span class="px-2.5 py-0.5 rounded-full bg-slate-900 border border-slate-800 text-[10px] text-slate-400">Portrait / Landscape</span>
                            <span class="px-2.5 py-0.5 rounded-full bg-slate-900 border border-slate-800 text-[10px] text-slate-400">JSON Import/Export</span>
                            <span class="px-2.5 py-0.5 rounded-full bg-slate-900 border border-slate-800 text-[10px] text-slate-400">Live Preview</span>
                        </div>
                    </div>
                    <div class="lg:col-span-7 lg:order-1 relative group">
                        <div class="absolute -inset-2 rounded-3xl bg-gradient-to-tr from-indigo-500/10 to-amber-500/5 blur-2xl opacity-60 group-hover:opacity-80 transition duration-500"></div>
                        <div class="relative rounded-2xl overflow-hidden border border-slate-850 bg-slate-950 p-2.5 shadow-2xl grid grid-cols-2 gap-3">
                            <img src="{{ asset('images/screenshots/s9.png') }}" alt="Templates view screenshot" class="w-full h-auto rounded-lg object-cover shadow" />
                            <img src="{{ asset('images/screenshots/s10.png') }}" alt="Template Studio Editor screenshot" class="w-full h-auto rounded-lg object-cover shadow" />
                        </div>
                    </div>
                </div>

                <!-- 9. ID Card Generation -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                    <div class="lg:col-span-5 space-y-6 text-left">
                        <span class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-slate-900 border border-slate-800 text-[10px] font-bold tracking-widest text-amber-400 uppercase">
                            09 &bull; Batch Print Engine
                        </span>
                        <h2 class="text-3xl sm:text-4xl font-black text-white leading-tight">ID Card Generation & Printing</h2>
                        <p class="text-sm text-slate-400 leading-relaxed">
                            Auto-maps registered database fields into custom designs in real-time. Export bulk printable PDF imposition sheets (8/10 grids with cut marks) or high-res candidate image packages.
                        </p>
                        <div class="flex flex-wrap gap-1.5">
                            <span class="px-2.5 py-0.5 rounded-full bg-slate-900 border border-slate-800 text-[10px] text-slate-400">PDF Sheet Imposition</span>
                            <span class="px-2.5 py-0.5 rounded-full bg-slate-900 border border-slate-800 text-[10px] text-slate-400">High-Res PNG ZIPs</span>
                        </div>
                    </div>
                    <div class="lg:col-span-7 relative group">
                        <div class="absolute -inset-2 rounded-3xl bg-gradient-to-tr from-amber-500/10 to-indigo-500/5 blur-2xl opacity-60 group-hover:opacity-80 transition duration-500"></div>
                        <div class="relative rounded-2xl border border-slate-800 bg-slate-900/60 p-6 shadow-2xl text-left">
                            <h4 class="text-xs font-black text-slate-500 uppercase tracking-widest border-b border-slate-800 pb-2">Execution Flow</h4>
                            <div class="flex flex-col md:flex-row items-center justify-between gap-2 text-[10px] text-slate-300 font-bold">
                                <div class="px-3 py-1.5 bg-slate-950 border border-slate-800 rounded text-center w-full md:w-auto">Student details</div>
                                <span class="text-amber-500/60 leading-none rotate-90 md:rotate-0">&rarr;</span>
                                <div class="px-3 py-1.5 bg-slate-950 border border-slate-800 rounded text-center w-full md:w-auto">Design Template</div>
                                <span class="text-amber-500/60 leading-none rotate-90 md:rotate-0">&rarr;</span>
                                <div class="px-3 py-1.5 bg-slate-950 border border-slate-800 rounded text-emerald-400 text-center w-full md:w-auto">Auto-Generated ID</div>
                                <span class="text-amber-500/60 leading-none rotate-90 md:rotate-0">&rarr;</span>
                                <div class="px-3 py-1.5 bg-emerald-950/40 text-emerald-400 border border-emerald-900/40 rounded text-center w-full md:w-auto">PDF Print Sheets</div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>         </div>

            <!-- School Administration & Verification Loop Section -->
            <section class="mt-24 border-t border-slate-900 pt-16">
                <div class="text-center max-w-2xl mx-auto space-y-3 mb-16">
                    <span class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-slate-900 border border-slate-800 text-[10px] font-bold tracking-widest text-amber-400 uppercase">
                        Admin Workflow
                    </span>
                    <h2 class="text-3xl font-bold text-white">The Verification & Printing Lifecycle</h2>
                    <p class="text-sm text-slate-400">Our automated data loop ensures card integrity by keeping information fully checked and synchronized.</p>
                </div>

                <div class="relative max-w-5xl mx-auto">
                    <!-- Line decoration behind steps on desktop -->
                    <div class="absolute left-[31px] md:left-1/2 top-8 bottom-8 w-[2px] bg-slate-850 -translate-x-1/2 hidden md:block"></div>

                    <div class="space-y-12">
                        <!-- Step 1 -->
                        <div class="flex flex-col md:flex-row items-center md:justify-between group">
                            <div class="w-full md:w-[45%] text-left md:text-right space-y-2 md:pr-8">
                                <span class="text-[10px] font-black text-amber-400 uppercase tracking-widest">Phase 01</span>
                                <h3 class="text-lg font-bold text-white group-hover:text-amber-400 transition duration-200">Import & Sync Rosters</h3>
                                <p class="text-xs text-slate-400 leading-relaxed">
                                    Admins import student demographics through Excel/CSV sheets. Newly registered parents are auto-linked to their children's files instantly using normalized mobile numbers.
                                </p>
                            </div>
                            <div class="w-16 h-16 rounded-full bg-slate-950 border-2 border-slate-800 flex items-center justify-center text-white font-bold relative z-10 my-4 md:my-0 group-hover:border-amber-400 transition duration-300">
                                01
                            </div>
                            <div class="w-full md:w-[45%] pl-8 hidden md:block"></div>
                        </div>

                        <!-- Step 2 -->
                        <div class="flex flex-col md:flex-row items-center md:justify-between group">
                            <div class="w-full md:w-[45%] pr-8 hidden md:block"></div>
                            <div class="w-16 h-16 rounded-full bg-slate-950 border-2 border-slate-800 flex items-center justify-center text-white font-bold relative z-10 my-4 md:my-0 group-hover:border-amber-400 transition duration-300">
                                02
                            </div>
                            <div class="w-full md:w-[45%] text-left space-y-2 md:pl-8">
                                <span class="text-[10px] font-black text-amber-400 uppercase tracking-widest">Phase 02</span>
                                <h3 class="text-lg font-bold text-white group-hover:text-amber-400 transition duration-200">Upload & Crop Headshots</h3>
                                <p class="text-xs text-slate-400 leading-relaxed">
                                    Parents and teachers use the Android App or mobile browser links to snap, crop, and upload student profile photos directly, eliminating manual flash-drive collections.
                                </p>
                            </div>
                        </div>

                        <!-- Step 3 -->
                        <div class="flex flex-col md:flex-row items-center md:justify-between group">
                            <div class="w-full md:w-[45%] text-left md:text-right space-y-2 md:pr-8">
                                <span class="text-[10px] font-black text-amber-400 uppercase tracking-widest">Phase 03</span>
                                <h3 class="text-lg font-bold text-white group-hover:text-amber-400 transition duration-200">Staff Verification Check</h3>
                                <p class="text-xs text-slate-400 leading-relaxed">
                                    Class teachers verify credentials and photo quality. Verified records are locked and approved for inclusion in the upcoming bulk printing export list.
                                </p>
                            </div>
                            <div class="w-16 h-16 rounded-full bg-slate-950 border-2 border-slate-800 flex items-center justify-center text-white font-bold relative z-10 my-4 md:my-0 group-hover:border-amber-400 transition duration-300">
                                03
                            </div>
                            <div class="w-full md:w-[45%] pl-8 hidden md:block"></div>
                        </div>

                        <!-- Step 4 -->
                        <div class="flex flex-col md:flex-row items-center md:justify-between group">
                            <div class="w-full md:w-[45%] pr-8 hidden md:block"></div>
                            <div class="w-16 h-16 rounded-full bg-slate-950 border-2 border-slate-800 flex items-center justify-center text-white font-bold relative z-10 my-4 md:my-0 group-hover:border-amber-400 transition duration-300">
                                04
                            </div>
                            <div class="w-full md:w-[45%] text-left space-y-2 md:pl-8">
                                <span class="text-[10px] font-black text-amber-400 uppercase tracking-widest">Phase 04</span>
                                <h3 class="text-lg font-bold text-white group-hover:text-amber-400 transition duration-200">Auto-Revoke Loop (Safety Trigger)</h3>
                                <p class="text-xs text-slate-400 leading-relaxed">
                                    If any detail (e.g. name, section, photo) is updated post-verification, database observers instantly revoke verification status to block incorrect prints.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Android Mobile App Showcase Section -->
            <section class="mt-24 p-8 sm:p-12 rounded-3xl bg-gradient-to-r from-slate-900 via-indigo-950/40 to-slate-950 border border-slate-850 relative overflow-hidden shadow-2xl">
                <!-- Glowing effect -->
                <div class="absolute -top-12 -right-12 w-64 h-64 bg-amber-500/5 rounded-full blur-3xl pointer-events-none"></div>
                <div class="absolute -bottom-12 -left-12 w-64 h-64 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                    <div class="space-y-6">
                        <span class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-slate-950 border border-slate-800 text-[10px] font-bold tracking-widest text-amber-400 uppercase">
                            Android Application
                        </span>
                        <h2 class="text-3xl sm:text-4xl font-extrabold text-white leading-tight">
                            The iCard Maker Mobile Client
                        </h2>
                        <p class="text-sm text-slate-400 leading-relaxed">
                            Bring your ID management setup to the field. Our official mobile client allows school admins, teachers, and parents to interact seamlessly.
                        </p>
                        
                        <div class="space-y-4">
                            <div class="flex items-start space-x-3">
                                <div class="w-5 h-5 rounded-md bg-amber-500/10 flex items-center justify-center text-amber-400 mt-0.5 font-bold text-xs">A</div>
                                <p class="text-xs text-slate-300"><strong class="text-white">For School Admins:</strong> Sync student rosters, invite staff members, and track verification queues from anywhere.</p>
                            </div>
                            <div class="flex items-start space-x-3">
                                <div class="w-5 h-5 rounded-md bg-amber-500/10 flex items-center justify-center text-amber-400 mt-0.5 font-bold text-xs">T</div>
                                <p class="text-xs text-slate-300"><strong class="text-white">For Teachers:</strong> Walk around classes and easily snap and upload profile pictures of student candidates.</p>
                            </div>
                            <div class="flex items-start space-x-3">
                                <div class="w-5 h-5 rounded-md bg-amber-500/10 flex items-center justify-center text-amber-400 mt-0.5 font-bold text-xs">P</div>
                                <p class="text-xs text-slate-300"><strong class="text-white">For Parents:</strong> Fill out detail forms, upload headshots of their children, and view active verified cards.</p>
                            </div>
                        </div>

                        <!-- Google Play Badge -->
                        <div class="pt-4">
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

                    <!-- Visual Representation: App Features List -->
                    <div class="bg-slate-950/60 border border-slate-850 rounded-2xl p-6 space-y-4">
                        <h4 class="text-xs font-black text-slate-500 uppercase tracking-widest border-b border-slate-900 pb-3">App Specifications</h4>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="p-4 bg-slate-900/40 rounded-xl border border-slate-900 text-center">
                                <p class="text-2xl font-black text-white">4.8+</p>
                                <p class="text-[10px] text-slate-500 uppercase font-semibold mt-1">User Rating</p>
                            </div>
                            <div class="p-4 bg-slate-900/40 rounded-xl border border-slate-900 text-center">
                                <p class="text-2xl font-black text-white">Lightweight</p>
                                <p class="text-[10px] text-slate-500 uppercase font-semibold mt-1">Optimized Size</p>
                            </div>
                            <div class="p-4 bg-slate-900/40 rounded-xl border border-slate-900 text-center">
                                <p class="text-xl font-bold text-white">Admins & Staff</p>
                                <p class="text-[10px] text-slate-500 uppercase font-semibold mt-1">Management Tool</p>
                            </div>
                            <div class="p-4 bg-slate-900/40 rounded-xl border border-slate-900 text-center">
                                <p class="text-xl font-bold text-white">Parents Portal</p>
                                <p class="text-[10px] text-slate-500 uppercase font-semibold mt-1">Direct Uploads</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Interactive callout section -->
            <div class="mt-20 p-8 rounded-3xl bg-gradient-to-r from-slate-900 via-slate-850 to-indigo-950 border border-slate-800 flex flex-col md:flex-row items-center justify-between gap-6">
                <div>
                    <h3 class="text-2xl font-bold text-white">Ready to streamline your ID issuing?</h3>
                    <p class="text-slate-400 text-sm mt-1">Get access to all premium design models for free today.</p>
                </div>
                <a href="{{ route('register') }}" class="inline-flex items-center justify-center px-6 py-3 text-base font-bold text-slate-950 bg-gradient-to-r from-amber-400 to-amber-500 hover:from-amber-300 hover:to-amber-400 rounded-xl transition duration-200 shadow-lg shadow-amber-500/20 whitespace-nowrap">
                    Start Designing &rarr;
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
