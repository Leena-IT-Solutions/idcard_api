<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'iCard Maker') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:300,400,500,600,700,800&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-slate-950 text-white min-h-screen flex flex-col justify-between selection:bg-amber-500 selection:text-slate-950">
        <div class="relative min-h-screen flex flex-col lg:flex-row">
            
            <!-- Glow background decorations -->
            <div class="absolute top-0 left-1/4 w-96 h-96 bg-indigo-900/20 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute bottom-10 right-1/4 w-96 h-96 bg-amber-500/5 rounded-full blur-3xl pointer-events-none"></div>

            <!-- Left Panel: Graphic & Branding (Hidden on mobile) -->
            <div class="hidden lg:flex lg:w-1/2 bg-slate-900/40 border-r border-slate-900 relative flex-col justify-between p-12 overflow-hidden">
                <!-- Floating Mini Card Graphic -->
                <div class="absolute -top-12 -left-12 w-64 h-64 bg-indigo-500/10 rounded-full blur-2xl pointer-events-none"></div>
                
                <!-- Logo & Brand Header -->
                <div class="relative z-10 flex items-center space-x-3">
                    <img src="{{ asset('images/logo.png') }}" class="h-10 w-auto" alt="iCard Maker Logo">
                    <span class="text-2xl font-black tracking-tight bg-gradient-to-r from-white via-slate-200 to-amber-400 bg-clip-text text-transparent">iCard Maker</span>
                </div>

                <!-- Central Illustration & Roster Pitch -->
                <div class="relative z-10 my-auto max-w-lg space-y-6">
                    <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-slate-950 border border-slate-800 text-xs font-semibold tracking-wide text-amber-400">
                        <span>✨ Make School Badges Hassle-Free</span>
                    </div>
                    <h2 class="text-4xl font-extrabold tracking-tight leading-tight">
                        Complete Identity Solution for Modern Schools
                    </h2>
                    <p class="text-slate-400 leading-relaxed text-sm">
                        Simplify data collection, profile photo uploads, and class divisions. iCard Maker streamlines the path from roster generation to instant printable badges.
                    </p>

                    <!-- Process Timeline Minimal Graphic -->
                    <div class="space-y-4 pt-4">
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 rounded-lg bg-indigo-900/30 border border-indigo-800/40 flex items-center justify-center text-xs font-bold text-indigo-400">01</div>
                            <p class="text-sm font-semibold text-slate-300">Quick Data Collection</p>
                        </div>
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 rounded-lg bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-xs font-bold text-amber-400">02</div>
                            <p class="text-sm font-semibold text-slate-300">Digital ID Generation</p>
                        </div>
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 rounded-lg bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-xs font-bold text-emerald-400">03</div>
                            <p class="text-sm font-semibold text-slate-300">Easy Printing Operations</p>
                        </div>
                    </div>
                </div>

                <!-- Footer References -->
                <div class="relative z-10 flex items-center justify-between text-xs text-slate-500 border-t border-slate-900 pt-6">
                    <span>&copy; {{ date('Y') }} iCard Maker. All rights reserved.</span>
                    <a href="{{ route('privacy') }}" class="hover:text-amber-400 transition">Privacy Policy</a>
                </div>
            </div>

            <!-- Right Panel: Form Slot -->
            <div class="flex-grow flex items-center justify-center p-6 sm:p-12 lg:w-1/2 relative z-10">
                <!-- Mobile Branding Header (Visible only on mobile/tablet) -->
                <div class="absolute top-6 left-6 flex items-center space-x-2 lg:hidden">
                    <img src="{{ asset('images/logo.png') }}" class="h-8 w-auto" alt="Logo">
                    <span class="text-lg font-bold text-white tracking-tight">iCard Maker</span>
                </div>

                <!-- Main Glassmorphic Wrapper -->
                <div class="w-full max-w-md bg-slate-900/50 backdrop-blur-md border border-slate-800 rounded-3xl p-8 sm:p-10 shadow-2xl shadow-slate-950/20 mt-12 lg:mt-0">
                    {{ $slot }}
                </div>
            </div>

        </div>
    </body>
</html>
