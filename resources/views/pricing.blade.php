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
        <header class="sticky top-0 z-50 backdrop-blur-md bg-slate-950/80 border-b border-slate-900 w-full px-4 sm:px-6 py-4">
            <div class="max-w-7xl mx-auto flex flex-row justify-between items-center gap-4">
                <a href="/" class="flex items-center space-x-2 sm:space-x-3 hover:opacity-90 transition duration-200">
                    <img src="{{ asset('images/logo.png') }}" class="h-8 sm:h-9 w-auto" alt="iCard Maker Logo">
                    <span class="text-lg sm:text-xl font-bold tracking-tight bg-gradient-to-r from-white via-slate-200 to-amber-400 bg-clip-text text-transparent">iCard Maker</span>
                </a>

                <!-- Navigation Links -->
                <nav class="flex items-center space-x-3 sm:space-x-4 md:space-x-6 overflow-x-auto whitespace-nowrap scrollbar-none max-w-[55%] md:max-w-none py-1">
                    <a href="/features" class="text-xs sm:text-sm font-semibold text-slate-300 hover:text-amber-400 transition duration-200">Features</a>
                    <a href="/how-it-works" class="text-xs sm:text-sm font-semibold text-slate-300 hover:text-amber-400 transition duration-200">How It Works</a>
                    <a href="/for-schools" class="text-xs sm:text-sm font-semibold text-slate-300 hover:text-amber-400 transition duration-200">For Schools</a>
                    <a href="/for-teachers" class="text-xs sm:text-sm font-semibold text-slate-300 hover:text-amber-400 transition duration-200">For Teachers</a>
                    <a href="/for-parents" class="text-xs sm:text-sm font-semibold text-slate-300 hover:text-amber-400 transition duration-200">For Parents</a>
                    <a href="/for-printing-companies" class="text-xs sm:text-sm font-semibold text-slate-300 hover:text-amber-400 transition duration-200">For Printing Companies</a>
                    <a href="/mobile-app" class="text-xs sm:text-sm font-semibold text-slate-300 hover:text-amber-400 transition duration-200">Mobile App</a>
                    <a href="/pricing" class="text-xs sm:text-sm font-semibold text-amber-400 hover:text-amber-300 transition duration-200">Pricing</a>
                    <a href="/contact" class="text-xs sm:text-sm font-semibold text-slate-300 hover:text-amber-400 transition duration-200">Contact</a>
                </nav>

                <!-- Auth / Call to Action -->
                <div class="flex items-center space-x-2 sm:space-x-4">
                    @if (Route::has('login'))
                        @auth
                            <a href="{{ url('/dashboard') }}" class="text-xs sm:text-sm font-semibold text-amber-400 hover:text-amber-300 transition duration-200">Dashboard</a>
                        @else
                            <a href="{{ route('login') }}" class="text-xs sm:text-sm font-semibold text-slate-300 hover:text-white transition duration-200">Log in</a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="inline-flex items-center justify-center px-3 sm:px-4 py-1.5 sm:py-2 text-xs sm:text-sm font-semibold text-slate-950 bg-gradient-to-r from-amber-400 to-amber-500 hover:from-amber-300 hover:to-amber-400 rounded-xl transition duration-200 shadow-md shadow-amber-500/10">Register</a>
                            @endif
                        @endauth
                    @endif
                </div>
            </div>
        </header>

        <!-- Main Pricing Content -->
        <main class="relative z-10 max-w-7xl mx-auto w-full px-6 py-16 flex-grow">
            <!-- Hero Header -->
            <div class="text-center max-w-3xl mx-auto space-y-4 mb-16">
                <span class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-slate-900 border border-slate-800 text-xs font-semibold tracking-wide text-amber-400">
                    💎 Transparent Plans
                </span>
                <h1 class="text-4xl sm:text-5xl font-black tracking-tight leading-none text-white">
                    Simple pricing for <span class="bg-gradient-to-r from-amber-400 to-amber-500 bg-clip-text text-transparent">schools of all sizes</span>
                </h1>
                <p class="text-lg text-slate-400">
                    Start creating professional badges for free, then upgrade as your campus roster grows.
                </p>
            </div>

            <!-- Pricing Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 items-stretch max-w-5xl mx-auto">
                <!-- Plan 1: Starter -->
                <div class="flex flex-col bg-slate-900/40 border border-slate-850 rounded-3xl p-8 shadow-xl relative justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-slate-300">Starter</h3>
                        <p class="text-xs text-slate-500 mt-1">Perfect for small academies & tutoring camps</p>
                        <div class="mt-6 flex items-baseline">
                            <span class="text-4xl font-extrabold tracking-tight text-white">$0</span>
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
                            <li class="flex items-center space-x-3 text-slate-600">
                                <svg class="w-4 h-4 text-slate-700 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12" /></svg>
                                <span>Custom Logo Uploads</span>
                            </li>
                        </ul>
                    </div>
                    <div class="mt-8">
                        <a href="{{ route('register') }}" class="w-full inline-flex items-center justify-center px-4 py-2.5 text-sm font-bold text-slate-300 hover:text-white bg-slate-900 border border-slate-800 rounded-xl transition duration-200">
                            Get Started Free
                        </a>
                    </div>
                </div>

                <!-- Plan 2: Growth (Popular) -->
                <div class="flex flex-col bg-slate-900 border-2 border-amber-500/80 rounded-3xl p-8 shadow-2xl relative justify-between">
                    <div class="absolute -top-3.5 left-1/2 -translate-x-1/2 px-4 py-1 bg-amber-500 text-slate-950 text-xs font-black tracking-widest uppercase rounded-full shadow-md">
                        Most Popular
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-white">Growth</h3>
                        <p class="text-xs text-slate-400 mt-1">Built for growing public & private schools</p>
                        <div class="mt-6 flex items-baseline">
                            <span class="text-4xl font-extrabold tracking-tight text-white">$29</span>
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
                            Upgrade to Growth
                        </a>
                    </div>
                </div>

                <!-- Plan 3: Enterprise -->
                <div class="flex flex-col bg-slate-900/40 border border-slate-850 rounded-3xl p-8 shadow-xl relative justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-slate-300">Enterprise</h3>
                        <p class="text-xs text-slate-500 mt-1">For multi-institute chains & school boards</p>
                        <div class="mt-6 flex items-baseline">
                            <span class="text-4xl font-extrabold tracking-tight text-white">$89</span>
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
                                <span>Dedicated API Keys & Access</span>
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
