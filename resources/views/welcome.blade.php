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

        <!-- Main Hero Section -->
        <main class="relative z-10 max-w-7xl mx-auto w-full px-6 py-16 flex-grow flex flex-col lg:flex-row items-center justify-between gap-12">
            <!-- Left Content Column -->
            <div class="flex-1 text-center lg:text-left space-y-6 max-w-2xl">
                <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-slate-900 border border-slate-800 text-xs font-semibold tracking-wide text-amber-400 animate-pulse">
                    <span>✨ Modern Digital ID Card System</span>
                </div>
                <h1 class="text-4xl sm:text-6xl font-black tracking-tight leading-none text-white">
                    Generate Smart <span class="bg-gradient-to-r from-amber-400 via-amber-500 to-orange-400 bg-clip-text text-transparent">Student ID Cards</span> in Minutes
                </h1>
                <p class="text-lg text-slate-400 leading-relaxed">
                    iCard Maker simplifies student profile collection, class assignment, and digital badge issuing. Built for schools, academies, and institutes seeking a premium, secure ID infrastructure.
                </p>
                
                <div class="pt-4 flex flex-col sm:flex-row items-center gap-4 justify-center lg:justify-start">
                    @auth
                        <a href="{{ url('/dashboard') }}" class="inline-flex items-center justify-center px-6 py-4 text-base font-bold text-slate-950 bg-gradient-to-r from-amber-400 to-amber-500 hover:from-amber-300 hover:to-amber-400 rounded-2xl transition duration-200 shadow-lg shadow-amber-500/20">
                            Go to Dashboard &rarr;
                        </a>
                    @else
                        <a href="{{ route('register') }}" class="inline-flex items-center justify-center px-6 py-4 text-base font-bold text-slate-950 bg-gradient-to-r from-amber-400 to-amber-500 hover:from-amber-300 hover:to-amber-400 rounded-2xl transition duration-200 shadow-lg shadow-amber-500/20">
                            Create Free Account
                        </a>
                        <a href="{{ route('login') }}" class="inline-flex items-center justify-center px-6 py-4 text-base font-bold text-slate-300 hover:text-white bg-slate-900 hover:bg-slate-800 border border-slate-800 rounded-2xl transition duration-200">
                            School Admin Sign In
                        </a>
                    @endauth

                    <a href="https://play.google.com/store/apps/details?id=com.infoleena.icard.maker" target="_blank" class="inline-flex items-center space-x-3 px-5 h-[58px] bg-black border border-slate-800 hover:border-slate-700 rounded-2xl transition duration-200 shadow-md">
                        <svg class="w-6 h-6 text-amber-400" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M5 3.00003C5 2.50003 5.4 2.20003 5.9 2.40003L21.4 11.4C21.8 11.6 21.8 12.4 21.4 12.6L5.9 21.6C5.4 21.8 5 21.5 5 21V3.00003Z"/>
                        </svg>
                        <div class="text-left leading-none">
                            <p class="text-[8px] text-slate-400 font-semibold tracking-wider uppercase">Get it on</p>
                            <p class="text-xs font-bold text-white mt-1">Google Play</p>
                        </div>
                    </a>
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
                <!-- Floating Mini Card -->
                <div class="absolute -top-6 -left-6 bg-slate-950/90 backdrop-blur border border-slate-800 rounded-2xl p-4 shadow-xl flex items-center space-x-3 pointer-events-none">
                    <div class="w-10 h-10 rounded-full bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-[10px] text-slate-500 uppercase tracking-wider font-semibold">Security System</p>
                        <p class="text-xs font-bold text-white">Active Identity Verified</p>
                    </div>
                </div>

                <!-- Card Structure Preview -->
                <div class="space-y-6">
                    <div class="flex justify-between items-center pb-4 border-b border-slate-850">
                        <span class="text-xs font-black text-slate-400 uppercase tracking-widest">iCard System Preview</span>
                        <div class="flex items-center space-x-2">
                            <span class="text-[10px] text-slate-500 font-bold">ONLINE</span>
                            <div class="h-2.5 w-2.5 rounded-full bg-green-500 shadow-md shadow-green-500/30 animate-pulse"></div>
                        </div>
                    </div>
                    
                    <!-- Glassmorphic ID Card -->
                    <div class="aspect-[1.586/1] bg-gradient-to-br from-indigo-950/80 via-slate-900 to-slate-950 rounded-2xl p-6 border border-indigo-900/30 flex flex-col justify-between shadow-xl relative overflow-hidden group">
                        <!-- Card Glow Top -->
                        <div class="absolute -top-12 -right-12 w-24 h-24 bg-amber-500/10 rounded-full blur-xl"></div>
                        
                        <div class="flex justify-between items-start z-10">
                            <div>
                                <h4 class="text-xs font-black tracking-widest text-slate-200">EXCELSIOR ACADEMY</h4>
                                <p class="text-[8px] text-amber-400 uppercase font-semibold">Digital Student ID</p>
                            </div>
                            <img src="{{ asset('images/logo.png') }}" class="h-6 w-auto" alt="Logo">
                        </div>
                        <div class="flex items-center space-x-4 z-10">
                            <!-- Placeholder Avatar -->
                            <div class="w-14 h-14 rounded-xl bg-slate-900 border border-slate-800 flex flex-col items-center justify-center overflow-hidden">
                                <svg class="w-8 h-8 text-slate-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" /></svg>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-white">Aarav S. Rathod</p>
                                <p class="text-[10px] text-slate-400 font-semibold">Class: 5-A | Roll No: 24</p>
                                <p class="text-[9px] text-slate-500">Blood Group: <span class="text-amber-400 font-bold">B+</span></p>
                            </div>
                        </div>
                        <div class="flex justify-between items-center text-[8px] text-slate-500 border-t border-slate-800/80 pt-2 z-10">
                            <span>ISSUE: 2026</span>
                            <div class="flex space-x-0.5">
                                <span class="bg-slate-800 w-[2px] h-2"></span>
                                <span class="bg-slate-800 w-[1px] h-2"></span>
                                <span class="bg-slate-800 w-[3px] h-2"></span>
                                <span class="bg-slate-800 w-[1px] h-2"></span>
                                <span class="bg-slate-800 w-[2px] h-2"></span>
                            </div>
                            <span>VALID UNTIL: 2027</span>
                        </div>
                    </div>

                    <!-- Description of Core Modules -->
                    <div class="grid grid-cols-2 gap-4 pt-2">
                        <div class="p-4 bg-slate-950 border border-slate-850 rounded-2xl hover:border-slate-800 transition duration-200">
                            <h3 class="text-xs font-bold text-white uppercase tracking-wider">Admins & Staff</h3>
                            <p class="text-[10px] text-slate-500 mt-1 leading-relaxed">Configure classes, invite teachers, and export badges.</p>
                        </div>
                        <div class="p-4 bg-slate-950 border border-slate-850 rounded-2xl hover:border-slate-800 transition duration-200">
                            <h3 class="text-xs font-bold text-white uppercase tracking-wider">Teachers & Parents</h3>
                            <p class="text-[10px] text-slate-500 mt-1 leading-relaxed">Upload profile photos and manage student rosters.</p>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- The Problem Section -->
        <section class="relative z-10 max-w-7xl mx-auto w-full px-6 py-16 border-t border-slate-900">
            <div class="text-center max-w-3xl mx-auto space-y-4 mb-12">
                <span class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-red-950/40 border border-red-900/30 text-xs font-semibold tracking-wider text-red-400 uppercase">
                    ⚠️ The Traditional Way
                </span>
                <h2 class="text-3xl sm:text-4xl font-extrabold text-white">
                    Schools still spend weeks making ID cards
                </h2>
                <p class="text-sm text-slate-400 max-w-xl mx-auto">
                    Traditional ID card compilation is filled with manual steps, communication delays, and errors.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 max-w-6xl mx-auto">
                <!-- Item 1 -->
                <div class="p-6 rounded-2xl bg-slate-950 border border-slate-900 hover:border-red-900/30 transition duration-200 flex items-start space-x-3">
                    <span class="text-red-500 text-lg select-none">❌</span>
                    <div>
                        <h4 class="text-sm font-bold text-slate-200">Manual Photos</h4>
                        <p class="text-xs text-slate-500 mt-1 leading-relaxed">Student photos collected manually via USBs or emails.</p>
                    </div>
                </div>

                <!-- Item 2 -->
                <div class="p-6 rounded-2xl bg-slate-950 border border-slate-900 hover:border-red-900/30 transition duration-200 flex items-start space-x-3">
                    <span class="text-red-500 text-lg select-none">❌</span>
                    <div>
                        <h4 class="text-sm font-bold text-slate-200">Paper Forms</h4>
                        <p class="text-xs text-slate-500 mt-1 leading-relaxed">Student rosters and details filled on paper sheets.</p>
                    </div>
                </div>

                <!-- Item 3 -->
                <div class="p-6 rounded-2xl bg-slate-950 border border-slate-900 hover:border-red-900/30 transition duration-200 flex items-start space-x-3">
                    <span class="text-red-500 text-lg select-none">❌</span>
                    <div>
                        <h4 class="text-sm font-bold text-slate-200">Wrong Spellings</h4>
                        <p class="text-xs text-slate-500 mt-1 leading-relaxed">Name spelling mistakes due to manual re-typing.</p>
                    </div>
                </div>

                <!-- Item 4 -->
                <div class="p-6 rounded-2xl bg-slate-950 border border-slate-900 hover:border-red-900/30 transition duration-200 flex items-start space-x-3">
                    <span class="text-red-500 text-lg select-none">❌</span>
                    <div>
                        <h4 class="text-sm font-bold text-slate-200">Missing Information</h4>
                        <p class="text-xs text-slate-500 mt-1 leading-relaxed">Key parameters like blood group or section missing.</p>
                    </div>
                </div>

                <!-- Item 5 -->
                <div class="p-6 rounded-2xl bg-slate-950 border border-slate-900 hover:border-red-900/30 transition duration-200 flex items-start space-x-3">
                    <span class="text-red-500 text-lg select-none">❌</span>
                    <div>
                        <h4 class="text-sm font-bold text-slate-200">School Visits</h4>
                        <p class="text-xs text-slate-500 mt-1 leading-relaxed">Parents visiting the school repeatedly to submit data.</p>
                    </div>
                </div>

                <!-- Item 6 -->
                <div class="p-6 rounded-2xl bg-slate-950 border border-slate-900 hover:border-red-900/30 transition duration-200 flex items-start space-x-3">
                    <span class="text-red-500 text-lg select-none">❌</span>
                    <div>
                        <h4 class="text-sm font-bold text-slate-200">WhatsApp Overload</h4>
                        <p class="text-xs text-slate-500 mt-1 leading-relaxed">Teachers sending random low-res photos on WhatsApp.</p>
                    </div>
                </div>

                <!-- Item 7 -->
                <div class="p-6 rounded-2xl bg-slate-950 border border-slate-900 hover:border-red-900/30 transition duration-200 flex items-start space-x-3">
                    <span class="text-red-500 text-lg select-none">❌</span>
                    <div>
                        <h4 class="text-sm font-bold text-slate-200">Printer Corrections</h4>
                        <p class="text-xs text-slate-500 mt-1 leading-relaxed">Printing companies constantly asking for file corrections.</p>
                    </div>
                </div>

                <!-- Item 8 -->
                <div class="p-6 rounded-2xl bg-slate-950 border border-slate-900 hover:border-red-900/30 transition duration-200 flex items-start space-x-3">
                    <span class="text-red-500 text-lg select-none">❌</span>
                    <div>
                        <h4 class="text-sm font-bold text-slate-200">Phone Calls</h4>
                        <p class="text-xs text-slate-500 mt-1 leading-relaxed">Hundreds of follow-up phone calls to gather correct data.</p>
                    </div>
                </div>
            </div>

            <!-- Warning Callout -->
            <div class="mt-10 max-w-3xl mx-auto p-4 rounded-xl bg-red-950/20 border border-red-900/20 text-center">
                <p class="text-sm font-bold text-red-400">
                    ⚠️ One mistake means printing everything again.
                </p>
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

        <!-- ID Verification Loop Section -->
        <section class="relative z-10 max-w-7xl mx-auto w-full px-6 py-16 border-t border-slate-900">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                <!-- Left: Simple English description of steps -->
                <div class="lg:col-span-5 space-y-6 text-left">
                    <span class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-slate-900 border border-slate-800 text-[10px] font-bold tracking-widest text-amber-400 uppercase">
                        Smart Approval Loop
                    </span>
                    <h2 class="text-3xl sm:text-4xl font-extrabold text-white leading-tight">
                        How We Prevent Printing Mistakes
                    </h2>
                    <p class="text-sm text-slate-400 leading-relaxed">
                        To save costs and avoid errors, iCard Maker has a built-in safety check. We make sure every card is double-checked and verified before it is sent to print.
                    </p>

                    <!-- Simple Steps List -->
                    <div class="space-y-4">
                        <div class="flex items-start space-x-3">
                            <span class="w-5 h-5 rounded-md bg-amber-500/10 flex items-center justify-center text-amber-400 font-bold text-xs mt-0.5">1</span>
                            <div>
                                <h4 class="text-xs font-bold text-white uppercase tracking-wider">School Uploads the List</h4>
                                <p class="text-xs text-slate-400 mt-0.5 leading-relaxed">The school admin uploads the list of students with their names and classes.</p>
                            </div>
                        </div>
                        <div class="flex items-start space-x-3">
                            <span class="w-5 h-5 rounded-md bg-amber-500/10 flex items-center justify-center text-amber-400 font-bold text-xs mt-0.5">2</span>
                            <div>
                                <h4 class="text-xs font-bold text-white uppercase tracking-wider">Parents Upload Photos</h4>
                                <p class="text-xs text-slate-400 mt-0.5 leading-relaxed">Parents use their mobile phones to snap and upload their child's card photo.</p>
                            </div>
                        </div>
                        <div class="flex items-start space-x-3">
                            <span class="w-5 h-5 rounded-md bg-amber-500/10 flex items-center justify-center text-amber-400 font-bold text-xs mt-0.5">3</span>
                            <div>
                                <h4 class="text-xs font-bold text-white uppercase tracking-wider">Teachers Approve the Cards</h4>
                                <p class="text-xs text-slate-400 mt-0.5 leading-relaxed">Class teachers review the photos and spelling. If everything looks good, they click approve.</p>
                            </div>
                        </div>
                        <div class="flex items-start space-x-3">
                            <span class="w-5 h-5 rounded-md bg-amber-500/10 flex items-center justify-center text-amber-400 font-bold text-xs mt-0.5">4</span>
                            <div>
                                <h4 class="text-xs font-bold text-white uppercase tracking-wider">Admin Prints approved Cards</h4>
                                <p class="text-xs text-slate-400 mt-0.5 leading-relaxed">Admins generate and print the cards that are marked as approved.</p>
                            </div>
                        </div>
                        <div class="flex items-start space-x-3 border-t border-slate-900 pt-3">
                            <span class="w-5 h-5 rounded-md bg-amber-500/10 flex items-center justify-center text-amber-400 font-bold text-xs mt-0.5">&larr;</span>
                            <div>
                                <h4 class="text-xs font-bold text-white uppercase tracking-wider">Safety Reset (If Info Changes)</h4>
                                <p class="text-xs text-slate-400 mt-0.5 leading-relaxed">If anyone edits student details later, the card is instantly unapproved. It must be approved again before printing.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: Diagram Image -->
                <div class="lg:col-span-7 flex justify-center">
                    <div class="p-4 bg-slate-950/60 border border-slate-850 rounded-3xl overflow-hidden shadow-2xl">
                        <img src="{{ asset('images/id_verification_workflow.png') }}" alt="School ID Card Verification Loop Diagram" class="w-full h-auto rounded-2xl max-w-lg object-contain shadow-inner" />
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
