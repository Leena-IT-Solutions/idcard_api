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
        <div class="absolute top-0 left-1/4 w-96 h-96 bg-indigo-900/20 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute top-20 right-1/4 w-96 h-96 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <!-- Header -->
        <header class="relative z-10 max-w-7xl mx-auto w-full px-6 py-6 flex justify-between items-center">
            <div class="flex items-center space-x-3">
                <img src="{{ asset('images/logo.png') }}" class="h-10 w-auto" alt="iCard Maker Logo">
                <span class="text-2xl font-black tracking-tight bg-gradient-to-r from-white via-slate-200 to-amber-400 bg-clip-text text-transparent">iCard Maker</span>
            </div>
            
            <nav class="flex items-center space-x-4">
                @if (Route::has('login'))
                    @auth
                        <a href="{{ url('/dashboard') }}" class="text-sm font-semibold text-amber-400 hover:text-amber-300 transition duration-200">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="text-sm font-semibold text-slate-300 hover:text-white transition duration-200">Log in</a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="inline-flex items-center justify-center px-4 py-2 text-sm font-semibold text-slate-950 bg-gradient-to-r from-amber-400 to-amber-500 hover:from-amber-300 hover:to-amber-400 rounded-xl transition duration-200 shadow-md shadow-amber-500/10">Register</a>
                        @endif
                    @endauth
                @endif
            </nav>
        </header>

        <!-- Main Hero Section -->
        <main class="relative z-10 max-w-7xl mx-auto w-full px-6 py-12 flex-grow flex flex-col lg:flex-row items-center justify-between gap-12">
            <!-- Left Content Column -->
            <div class="flex-1 text-center lg:text-left space-y-6 max-w-2xl">
                <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-slate-900 border border-slate-800 text-xs font-semibold tracking-wide text-amber-400">
                    <span>✨ Modern Digital ID Card System</span>
                </div>
                <h1 class="text-4xl sm:text-6xl font-extrabold tracking-tight leading-none text-white">
                    Generate Smart <span class="bg-gradient-to-r from-amber-400 to-amber-500 bg-clip-text text-transparent">Student ID Cards</span> in Minutes
                </h1>
                <p class="text-lg text-slate-400 leading-relaxed">
                    iCard Maker simplifies student profile collection, class assignment, and digital badge issuing. Built for schools, academies, and institutes seeking a premium, secure ID infrastructure.
                </p>
                <div class="pt-4 flex flex-col sm:flex-row gap-4 justify-center lg:justify-start">
                    @auth
                        <a href="{{ url('/dashboard') }}" class="inline-flex items-center justify-center px-6 py-3.5 text-base font-bold text-slate-950 bg-gradient-to-r from-amber-400 to-amber-500 hover:from-amber-300 hover:to-amber-400 rounded-2xl transition duration-200 shadow-lg shadow-amber-500/20">
                            Go to Dashboard &rarr;
                        </a>
                    @else
                        <a href="{{ route('register') }}" class="inline-flex items-center justify-center px-6 py-3.5 text-base font-bold text-slate-950 bg-gradient-to-r from-amber-400 to-amber-500 hover:from-amber-300 hover:to-amber-400 rounded-2xl transition duration-200 shadow-lg shadow-amber-500/20">
                            Create Free Account
                        </a>
                        <a href="{{ route('login') }}" class="inline-flex items-center justify-center px-6 py-3.5 text-base font-bold text-slate-300 hover:text-white bg-slate-900 hover:bg-slate-800 border border-slate-800 rounded-2xl transition duration-200">
                            School Administrator Sign In
                        </a>
                    @endauth
                </div>
            </div>

            <!-- Right Interactive Graphic Column -->
            <div class="flex-1 w-full max-w-md bg-slate-900 border border-slate-800 rounded-3xl p-8 relative shadow-2xl shadow-indigo-500/5">
                <!-- Floating Mini Card -->
                <div class="absolute -top-6 -left-6 bg-slate-950/80 backdrop-blur border border-slate-800 rounded-2xl p-4 shadow-xl flex items-center space-x-3 pointer-events-none">
                    <div class="w-10 h-10 rounded-full bg-amber-400 flex items-center justify-center text-slate-950">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400">Security Verification</p>
                        <p class="text-sm font-bold text-white">Active Identity Verified</p>
                    </div>
                </div>

                <!-- Card Structure Preview -->
                <div class="space-y-6">
                    <div class="flex justify-between items-center pb-4 border-b border-slate-800">
                        <span class="text-sm font-bold text-slate-400 uppercase tracking-widest">iCard Prototype</span>
                        <div class="h-2 w-2 rounded-full bg-green-400 animate-pulse"></div>
                    </div>
                    
                    <div class="aspect-[1.586/1] bg-gradient-to-br from-indigo-950 to-slate-900 rounded-2xl p-6 border border-indigo-900/30 flex flex-col justify-between shadow-inner">
                        <div class="flex justify-between items-start">
                            <div>
                                <h4 class="text-sm font-black tracking-tight text-white">EXCELSIOR ACADEMY</h4>
                                <p class="text-[9px] text-slate-400">Digital Student Card</p>
                            </div>
                            <img src="{{ asset('images/logo.png') }}" class="h-6 w-auto" alt="Logo">
                        </div>
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 rounded-xl bg-slate-800 border border-slate-700 flex items-center justify-center text-slate-600 font-bold text-sm">PHOTO</div>
                            <div>
                                <p class="text-xs font-bold text-white">Aarav S. Rathod</p>
                                <p class="text-[9px] text-slate-400">Class: 5-A | Roll No: 24</p>
                                <p class="text-[9px] text-slate-400">Blood Group: B+</p>
                            </div>
                        </div>
                        <div class="flex justify-between items-center text-[8px] text-slate-500 border-t border-slate-800/60 pt-2">
                            <span>ISSUE: 2026</span>
                            <span>VALID UNTIL: 2027</span>
                        </div>
                    </div>

                    <!-- Description of Core Modules -->
                    <div class="grid grid-cols-2 gap-4 pt-4">
                        <div class="p-3 bg-slate-950 border border-slate-850 rounded-xl">
                            <h3 class="text-sm font-bold text-white">Admins & Staff</h3>
                            <p class="text-[10px] text-slate-500">Configure classes, invite teachers, and export badges.</p>
                        </div>
                        <div class="p-3 bg-slate-950 border border-slate-850 rounded-xl">
                            <h3 class="text-sm font-bold text-white">Teachers & Parents</h3>
                            <p class="text-[10px] text-slate-500">Upload profile photos and manage student rosters.</p>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="relative z-10 w-full border-t border-slate-900 bg-slate-950/80 backdrop-blur py-8">
            <div class="max-w-7xl mx-auto px-6 flex flex-col md:flex-row justify-between items-center gap-4 text-sm text-slate-500">
                <div class="flex items-center space-x-2">
                    <img src="{{ asset('images/logo.png') }}" class="h-5 w-auto" alt="Logo">
                    <span>&copy; {{ date('Y') }} iCard Maker. All rights reserved.</span>
                </div>
                <div class="flex items-center space-x-6">
                    <a href="{{ route('privacy') }}" class="hover:text-amber-400 transition duration-200">Privacy Policy</a>
                    <span>Contact: <a href="mailto:leenaitsolutions@gmail.com" class="hover:text-amber-400 transition duration-200">leenaitsolutions@gmail.com</a></span>
                </div>
            </div>
        </footer>
    </body>
</html>
