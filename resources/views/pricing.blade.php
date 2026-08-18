<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Pricing - iCard Maker</title>
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
                        <div class="dropdown-menu hidden group-hover/solutions:block lg:absolute lg:left-1/2 lg:-translate-x-1/2 lg:top-full lg:pt-2 w-full lg:w-48 z-50">
                            <div class="bg-slate-950 lg:border lg:border-slate-900 rounded-xl py-2 lg:shadow-xl">
                                <a href="/for-schools" class="block px-4 py-2 text-sm text-slate-300 hover:text-amber-400 hover:bg-slate-900 transition duration-150 text-center lg:text-left">For Schools</a>
                                <a href="/for-printing-companies" class="block px-4 py-2 text-sm text-slate-300 hover:text-amber-400 hover:bg-slate-900 transition duration-150 text-center lg:text-left">For Printing Companies</a>
                            </div>
                        </div>
                    </div>

                    <a href="/mobile-app" class="text-sm font-semibold text-slate-300 hover:text-amber-400 transition duration-200 w-full lg:w-auto text-center py-2 lg:py-0">Mobile App</a>
                    <a href="/pricing" class="text-sm font-semibold text-amber-400 hover:text-amber-300 transition duration-200 w-full lg:w-auto text-center py-2 lg:py-0">Pricing</a>
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

        <!-- Main Pricing Content -->
        <main class="relative z-10 max-w-7xl mx-auto w-full px-6 py-16 flex-grow">
            <!-- Hero Header -->
            <div class="text-center max-w-3xl mx-auto space-y-4 mb-20">
                <span class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-slate-900 border border-slate-800 text-xs font-semibold tracking-wide text-amber-400">
                    💎 Transparent Plans
                </span>
                <h1 class="text-4xl sm:text-5xl font-black tracking-tight leading-none text-white">
                    Simple pricing for <span class="bg-gradient-to-r from-amber-400 to-amber-500 bg-clip-text text-transparent">everyone</span>
                </h1>
                <p class="text-lg text-slate-400">
                    Choose a plan tailored to your organization, whether you manage a single school or a professional print shop.
                </p>
            </div>

            <!-- School Plans Heading -->
            <div class="text-center mb-12">
                <h2 class="text-2xl sm:text-3xl font-extrabold text-white">School Plans</h2>
                <p class="text-xs text-slate-500 uppercase tracking-widest font-semibold mt-1">Flexible options for campuses</p>
            </div>

            <!-- School Pricing Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 items-stretch max-w-5xl mx-auto mb-24">
                <!-- Plan 1: Starter -->
                <div class="flex flex-col bg-slate-900/40 border border-slate-850 rounded-3xl p-8 shadow-xl relative justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-slate-300">Starter</h3>
                        <p class="text-xs text-slate-500 mt-1">For small schools</p>
                        <div class="mt-6 flex items-baseline">
                            <span class="text-4xl font-extrabold tracking-tight text-white">₹0</span>
                            <span class="text-sm font-semibold text-slate-500 ml-1">/ forever</span>
                        </div>
                        <ul class="mt-8 space-y-4 text-sm text-slate-400">
                            <li class="flex items-center space-x-3">
                                <svg class="w-4 h-4 text-amber-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                                <span>1 School / Campus</span>
                            </li>
                            <li class="flex items-center space-x-3">
                                <svg class="w-4 h-4 text-amber-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                                <span>Up to 100 Students</span>
                            </li>
                            <li class="flex items-center space-x-3">
                                <svg class="w-4 h-4 text-amber-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                                <span>Standard Layout Templates</span>
                            </li>
                        </ul>
                    </div>
                    <div class="mt-8">
                        <a href="{{ route('register') }}" class="w-full inline-flex items-center justify-center px-4 py-2.5 text-sm font-bold text-slate-300 hover:text-white bg-slate-900 border border-slate-800 rounded-xl transition duration-200">
                            Get Started Free
                        </a>
                    </div>
                </div>

                <!-- Plan 2: Professional -->
                <div class="flex flex-col bg-slate-900 border-2 border-amber-500/80 rounded-3xl p-8 shadow-2xl relative justify-between">
                    <div class="absolute -top-3.5 left-1/2 -translate-x-1/2 px-4 py-1 bg-amber-500 text-slate-950 text-xs font-black tracking-widest uppercase rounded-full shadow-md">
                        Most Popular
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-white">Professional</h3>
                        <p class="text-xs text-slate-400 mt-1">For growing schools</p>
                        <div class="mt-6 flex items-baseline">
                            <span class="text-4xl font-extrabold tracking-tight text-white">₹2,499</span>
                            <span class="text-sm font-semibold text-slate-400 ml-1">/ month</span>
                        </div>
                        <ul class="mt-8 space-y-4 text-sm text-slate-300">
                            <li class="flex items-center space-x-3">
                                <svg class="w-4 h-4 text-amber-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                                <span>Up to 5 Schools / Branches</span>
                            </li>
                            <li class="flex items-center space-x-3">
                                <svg class="w-4 h-4 text-amber-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                                <span>Unlimited Students Roster</span>
                            </li>
                            <li class="flex items-center space-x-3">
                                <svg class="w-4 h-4 text-amber-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                                <span>Custom Logo & Header Uploads</span>
                            </li>
                            <li class="flex items-center space-x-3">
                                <svg class="w-4 h-4 text-amber-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                                <span>Full Data Collection Campaigns</span>
                            </li>
                        </ul>
                    </div>
                    <div class="mt-8">
                        <a href="{{ route('register') }}" class="w-full inline-flex items-center justify-center px-4 py-2.5 text-sm font-bold text-slate-950 bg-gradient-to-r from-amber-400 to-amber-500 hover:from-amber-300 hover:to-amber-400 rounded-xl transition duration-200 shadow-lg shadow-amber-500/20">
                            Upgrade to Professional
                        </a>
                    </div>
                </div>

                <!-- Plan 3: Enterprise -->
                <div class="flex flex-col bg-slate-900/40 border border-slate-850 rounded-3xl p-8 shadow-xl relative justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-slate-300">Enterprise</h3>
                        <p class="text-xs text-slate-500 mt-1">For school groups</p>
                        <div class="mt-6 flex items-baseline">
                            <span class="text-4xl font-extrabold tracking-tight text-white">₹7,499</span>
                            <span class="text-sm font-semibold text-slate-500 ml-1">/ month</span>
                        </div>
                        <ul class="mt-8 space-y-4 text-sm text-slate-400">
                            <li class="flex items-center space-x-3">
                                <svg class="w-4 h-4 text-amber-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                                <span>Unlimited Schools & Campuses</span>
                            </li>
                            <li class="flex items-center space-x-3">
                                <svg class="w-4 h-4 text-amber-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                                <span>Unlimited Student Rosters</span>
                            </li>
                            <li class="flex items-center space-x-3">
                                <svg class="w-4 h-4 text-amber-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                                <span>Custom Subdomain & White Label</span>
                            </li>
                        </ul>
                    </div>
                    <div class="mt-8">
                        <a href="{{ route('register') }}" class="w-full inline-flex items-center justify-center px-4 py-2.5 text-sm font-bold text-slate-300 hover:text-white bg-slate-900 border border-slate-800 rounded-xl transition duration-200">
                            Contact Sales
                        </a>
                    </div>
                </div>
            </div>

            <!-- Printing Company Plans Heading & Section -->
            <div class="max-w-5xl mx-auto border-t border-slate-900 pt-16 text-center">
                <div class="space-y-3 mb-10">
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-white">Printing Company Plans</h2>
                    <p class="text-xs text-slate-500 uppercase tracking-widest font-semibold">Volume pricing for commercial partners</p>
                </div>
                
                <div class="relative bg-slate-900/40 border border-slate-850 rounded-3xl p-8 max-w-4xl mx-auto overflow-hidden flex flex-col md:flex-row items-center justify-between gap-6">
                    <div class="absolute inset-0 bg-gradient-to-r from-amber-500/5 to-transparent blur-3xl pointer-events-none"></div>
                    <div class="text-left max-w-lg relative z-10">
                        <h4 class="text-lg font-bold text-white mb-2">High Volume Imposition Exports</h4>
                        <p class="text-xs text-slate-400 leading-relaxed">
                            Need specific layout integrations, automated webhook notifications, or custom A4 layout templates? Get a partner account with customized print margins.
                        </p>
                    </div>
                    <div class="relative z-10 shrink-0">
                        <a href="/contact" class="inline-flex items-center justify-center px-6 py-3 text-sm font-bold text-slate-950 bg-gradient-to-r from-amber-400 to-amber-500 hover:from-amber-300 hover:to-amber-400 rounded-xl transition duration-200 shadow-lg shadow-amber-500/20 whitespace-nowrap">
                            Get Custom Pricing
                        </a>
                </div>
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
