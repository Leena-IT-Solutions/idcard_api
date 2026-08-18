<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Contact - iCard Maker</title>
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
                    <a href="/pricing" class="text-sm font-semibold text-slate-300 hover:text-amber-400 transition duration-200 w-full lg:w-auto text-center py-2 lg:py-0">Pricing</a>
                    <a href="/contact" class="text-sm font-semibold text-amber-400 hover:text-amber-300 transition duration-200 w-full lg:w-auto text-center py-2 lg:py-0">Contact</a>
                    
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

        <!-- Main Contact Content -->
        <main class="relative z-10 max-w-7xl mx-auto w-full px-6 py-16 flex-grow">
            <!-- Hero Header -->
            <div class="text-center max-w-3xl mx-auto space-y-4 mb-16">
                <span class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-slate-900 border border-slate-800 text-xs font-semibold tracking-wide text-amber-400">
                    ✉️ Book a Demo
                </span>
                <h1 class="text-4xl sm:text-5xl font-black tracking-tight leading-none text-white">
                    See <span class="bg-gradient-to-r from-amber-400 to-amber-500 bg-clip-text text-transparent">iCard in Action</span>
                </h1>
                <p class="text-lg text-slate-400 leading-relaxed">
                    Schedule a personalized demo and see how iCard can simplify your school's ID card workflow.
                </p>
            </div>

            <!-- Content Split Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 max-w-5xl mx-auto items-start">
                
                <!-- Left Details & FAQs -->
                <div class="space-y-8">
                    <div class="space-y-6">
                        <h2 class="text-2xl font-bold text-white">Contact Information</h2>
                        <p class="text-slate-400 text-sm">
                            Get in touch directly or explore our FAQs. We aim to respond to all inquiries within 24 business hours.
                        </p>
                        <div class="space-y-4">
                            <div class="flex items-center space-x-4 p-4 rounded-2xl bg-slate-900/50 border border-slate-850">
                                <div class="w-10 h-10 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                </div>
                                <div>
                                    <h4 class="text-xs text-slate-500 font-bold uppercase">General Support</h4>
                                    <a href="mailto:leenaitsolutions@gmail.com" class="text-sm font-semibold text-white hover:text-amber-400 transition">leenaitsolutions@gmail.com</a>
                                </div>
                            </div>

                            <div class="flex items-center space-x-4 p-4 rounded-2xl bg-slate-900/50 border border-slate-850">
                                <div class="w-10 h-10 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                </div>
                                <div>
                                    <h4 class="text-xs text-slate-500 font-bold uppercase">Business Hours</h4>
                                    <p class="text-sm font-semibold text-white">Monday - Friday: 9:00 AM - 6:00 PM</p>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Right Form Box -->
                <div class="bg-slate-900 border border-slate-850 rounded-3xl p-8 shadow-2xl relative">
                    <h3 class="text-xl font-bold text-white mb-6">Schedule Demo</h3>
                    
                    <form id="contactForm" onsubmit="event.preventDefault(); document.getElementById('successMsg').classList.remove('hidden'); document.getElementById('contactForm').reset();" class="space-y-5">
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Name</label>
                            <input type="text" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white placeholder-slate-600 focus:outline-none focus:border-amber-500/50 transition" placeholder="John Doe">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase mb-2">School / Company Name</label>
                            <input type="text" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white placeholder-slate-600 focus:outline-none focus:border-amber-500/50 transition" placeholder="Academy High School">
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Mobile Number</label>
                                <input type="tel" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white placeholder-slate-600 focus:outline-none focus:border-amber-500/50 transition" placeholder="+1 (555) 000-0000">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Email Address</label>
                                <input type="email" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white placeholder-slate-600 focus:outline-none focus:border-amber-500/50 transition" placeholder="john@example.com">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Number of Students</label>
                                <input type="number" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white placeholder-slate-600 focus:outline-none focus:border-amber-500/50 transition" placeholder="500">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-400 uppercase mb-2">I am a</label>
                                <select required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-amber-500/50 transition">
                                    <option value="" disabled selected>Select Role</option>
                                    <option value="school">School</option>
                                    <option value="printing_company">Printing Company</option>
                                    <option value="college">College</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Message</label>
                            <textarea required rows="3" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white placeholder-slate-600 focus:outline-none focus:border-amber-500/50 transition" placeholder="Tell us how we can help you..."></textarea>
                        </div>

                        <button type="submit" class="w-full inline-flex items-center justify-center px-6 py-3.5 text-sm font-bold text-slate-950 bg-gradient-to-r from-amber-400 to-amber-500 hover:from-amber-300 hover:to-amber-400 rounded-xl transition duration-200 shadow-lg shadow-amber-500/20">
                            Book My Free Demo
                        </button>
                    </form>

                    <!-- Success State Notice -->
                    <div id="successMsg" class="hidden absolute inset-0 bg-slate-900/95 backdrop-blur-sm rounded-3xl p-8 flex flex-col items-center justify-center text-center space-y-4">
                        <div class="w-16 h-16 rounded-full bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                        </div>
                        <div>
                            <h4 class="text-xl font-bold text-white">Demo Booked Successfully!</h4>
                            <p class="text-sm text-slate-400 mt-2 max-w-xs mx-auto">Thank you for booking. A representative will contact you shortly to confirm your scheduled slot.</p>
                        </div>
                        <button onclick="document.getElementById('successMsg').classList.add('hidden')" class="px-5 py-2.5 bg-slate-950 hover:bg-slate-850 border border-slate-800 rounded-xl text-xs font-bold transition">
                            Back
                        </button>
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
