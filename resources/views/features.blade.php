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
        <header class="sticky top-0 z-50 backdrop-blur-md bg-slate-950/80 border-b border-slate-900 w-full px-4 sm:px-6 py-4">
            <div class="max-w-7xl mx-auto flex flex-row justify-between items-center gap-4">
                <a href="/" class="flex items-center space-x-2 sm:space-x-3 hover:opacity-90 transition duration-200">
                    <img src="{{ asset('images/logo.png') }}" class="h-8 sm:h-9 w-auto" alt="iCard Maker Logo">
                    <span class="text-lg sm:text-xl font-bold tracking-tight bg-gradient-to-r from-white via-slate-200 to-amber-400 bg-clip-text text-transparent">iCard Maker</span>
                </a>

                <!-- Navigation Links -->
                <nav class="flex items-center space-x-3 sm:space-x-4 md:space-x-6 overflow-x-auto whitespace-nowrap scrollbar-none max-w-[55%] md:max-w-none py-1">
                    <a href="/features" class="text-xs sm:text-sm font-semibold text-amber-400 hover:text-amber-300 transition duration-200">Features</a>
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

        <!-- Main Features Content -->
        <main class="relative z-10 max-w-7xl mx-auto w-full px-6 py-16 flex-grow">
            <!-- Hero Header -->
            <div class="text-center max-w-3xl mx-auto space-y-4 mb-16">
                <span class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-slate-900 border border-slate-800 text-xs font-semibold tracking-wide text-amber-400">
                    🚀 Powerful Platform Features
                </span>
                <h1 class="text-4xl sm:text-5xl font-black tracking-tight leading-none text-white">
                    Everything you need to <span class="bg-gradient-to-r from-amber-400 to-amber-500 bg-clip-text text-transparent">Manage School IDs</span>
                </h1>
                <p class="text-lg text-slate-400">
                    Automate student data validation, design premium templates, and export bulk print jobs effortlessly.
                </p>
            </div>

            <!-- Features Grid (9 Items) -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Feature 1: Template Builder -->
                <div class="group relative bg-slate-900/60 border border-slate-850 hover:border-amber-500/30 rounded-3xl p-8 transition-all duration-300 hover:-translate-y-1 shadow-xl">
                    <div class="w-12 h-12 rounded-2xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 mb-6 group-hover:scale-110 transition duration-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-2">Visual Template Editor</h3>
                    <p class="text-sm text-slate-400 leading-relaxed">
                        Design professional horizontal or vertical identity layouts. Drag and drop text fields, assign custom backgrounds, and adjust barcode parameters in real-time.
                    </p>
                </div>

                <!-- Feature 2: Mobile Photo App -->
                <div class="group relative bg-slate-900/60 border border-slate-850 hover:border-amber-500/30 rounded-3xl p-8 transition-all duration-300 hover:-translate-y-1 shadow-xl">
                    <div class="w-12 h-12 rounded-2xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 mb-6 group-hover:scale-110 transition duration-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-2">Android Mobile App</h3>
                    <p class="text-sm text-slate-400 leading-relaxed">
                        Administrators, teachers, and parents can capture verified candidate photos, manage rosters, and upload details directly from our official Google Play application.
                    </p>
                </div>

                <!-- Feature 3: Smart Parent Linkage -->
                <div class="group relative bg-slate-900/60 border border-slate-850 hover:border-amber-500/30 rounded-3xl p-8 transition-all duration-300 hover:-translate-y-1 shadow-xl">
                    <div class="w-12 h-12 rounded-2xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 mb-6 group-hover:scale-110 transition duration-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-2">Smart Parent-Student Link</h3>
                    <p class="text-sm text-slate-400 leading-relaxed">
                        Zero setup linkage. Normalizes mobile inputs during register to instantly hook parent profiles with matching school records, granting immediate access to upload details.
                    </p>
                </div>

                <!-- Feature 4: Asynchronous Print Queues -->
                <div class="group relative bg-slate-900/60 border border-slate-850 hover:border-amber-500/30 rounded-3xl p-8 transition-all duration-300 hover:-translate-y-1 shadow-xl">
                    <div class="w-12 h-12 rounded-2xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 mb-6 group-hover:scale-110 transition duration-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-2">Asynchronous Print Queues</h3>
                    <p class="text-sm text-slate-400 leading-relaxed">
                        Never worry about timeouts during bulk prints. Queue operations in the background to handle thousands of records while tracking progress in real-time.
                    </p>
                </div>

                <!-- Feature 5: Multi-Format ZIP/PDF Export -->
                <div class="group relative bg-slate-900/60 border border-slate-850 hover:border-amber-500/30 rounded-3xl p-8 transition-all duration-300 hover:-translate-y-1 shadow-xl">
                    <div class="w-12 h-12 rounded-2xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 mb-6 group-hover:scale-110 transition duration-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 4H6a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-2m-4-1v8m0 0l3-3m-3 3L9 8m-5 5h2.586a1 1 0 01.707.293l2.414 2.414a1 1 0 00.707.293h3.172a1 1 0 00.707-.293l2.414-2.414a1 1 0 01.707-.293H20" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-2">Multi-Format Print Outputs</h3>
                    <p class="text-sm text-slate-400 leading-relaxed">
                        Export pre-arranged imposition PDF sheets (8/10 grids with cut marks), high-res PNG image zips for card machines, or references Excel data packages.
                    </p>
                </div>

                <!-- Feature 6: Auto-Revoke Verification -->
                <div class="group relative bg-slate-900/60 border border-slate-850 hover:border-amber-500/30 rounded-3xl p-8 transition-all duration-300 hover:-translate-y-1 shadow-xl">
                    <div class="w-12 h-12 rounded-2xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 mb-6 group-hover:scale-110 transition duration-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-2">Automated Verification Loop</h3>
                    <p class="text-sm text-slate-400 leading-relaxed">
                        Stop printing outdated card layouts. Booted database observers automatically reset teacher verification states if student info or photos are updated.
                    </p>
                </div>

                <!-- Feature 7: Roster Structure -->
                <div class="group relative bg-slate-900/60 border border-slate-850 hover:border-amber-500/30 rounded-3xl p-8 transition-all duration-300 hover:-translate-y-1 shadow-xl">
                    <div class="w-12 h-12 rounded-2xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 mb-6 group-hover:scale-110 transition duration-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-2">Grade & Division Structure</h3>
                    <p class="text-sm text-slate-400 leading-relaxed">
                        Organize your school with ease. Group student records by academic years, classes, and specific divisions. Import batches using excel or CSV file templates.
                    </p>
                </div>

                <!-- Feature 8: Barcodes & QR codes -->
                <div class="group relative bg-slate-900/60 border border-slate-850 hover:border-amber-500/30 rounded-3xl p-8 transition-all duration-300 hover:-translate-y-1 shadow-xl">
                    <div class="w-12 h-12 rounded-2xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 mb-6 group-hover:scale-110 transition duration-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-2">QR Code & Barcode Integration</h3>
                    <p class="text-sm text-slate-400 leading-relaxed">
                        Embed dynamically generated QR codes or custom barcode symbologies on card backs to enable digital attendance scanning and secure physical campus entry validation.
                    </p>
                </div>

                <!-- Feature 9: Role Management -->
                <div class="group relative bg-slate-900/60 border border-slate-850 hover:border-amber-500/30 rounded-3xl p-8 transition-all duration-300 hover:-translate-y-1 shadow-xl">
                    <div class="w-12 h-12 rounded-2xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 mb-6 group-hover:scale-110 transition duration-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-2">Granular Role Assignment</h3>
                    <p class="text-sm text-slate-400 leading-relaxed">
                        Keep operations secure with fine-grained access. Allocate distinct permissions for system operators, school admins, teachers, and student profiles.
                    </p>
                </div>
            </div>

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
