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
                    <a href="/for-schools" class="text-xs sm:text-sm font-semibold text-amber-400 hover:text-amber-350 transition duration-200">For Schools</a>
                    <a href="/for-teachers" class="text-xs sm:text-sm font-semibold text-slate-300 hover:text-amber-400 transition duration-200">For Teachers</a>
                    <a href="/for-parents" class="text-xs sm:text-sm font-semibold text-slate-300 hover:text-amber-400 transition duration-200">For Parents</a>
                    <a href="/for-printing-companies" class="text-xs sm:text-sm font-semibold text-slate-300 hover:text-amber-400 transition duration-200">For Printing Companies</a>
                    <a href="/mobile-app" class="text-xs sm:text-sm font-semibold text-slate-300 hover:text-amber-400 transition duration-200">Mobile App</a>
                    <a href="/pricing" class="text-xs sm:text-sm font-semibold text-slate-300 hover:text-amber-400 transition duration-200">Pricing</a>
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

        <!-- Main Blank Content -->
        <main class="relative z-10 max-w-7xl mx-auto w-full px-6 py-32 flex-grow flex flex-col items-center justify-center text-center space-y-4">
            <h1 class="text-3xl sm:text-5xl font-black text-white">For Schools</h1>
            <p class="text-sm text-slate-500 uppercase tracking-widest font-semibold">Coming Soon</p>
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
