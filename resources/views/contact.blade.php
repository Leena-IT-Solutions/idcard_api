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
                    <a href="/contact" class="text-xs sm:text-sm font-semibold text-amber-400 hover:text-amber-300 transition duration-200">Contact</a>
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

        <!-- Main Contact Content -->
        <main class="relative z-10 max-w-7xl mx-auto w-full px-6 py-16 flex-grow">
            <!-- Hero Header -->
            <div class="text-center max-w-3xl mx-auto space-y-4 mb-16">
                <span class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-slate-900 border border-slate-800 text-xs font-semibold tracking-wide text-amber-400">
                    ✉️ Connect With Us
                </span>
                <h1 class="text-4xl sm:text-5xl font-black tracking-tight leading-none text-white">
                    We'd love to <span class="bg-gradient-to-r from-amber-400 to-amber-500 bg-clip-text text-transparent">hear from you</span>
                </h1>
                <p class="text-lg text-slate-400">
                    Have questions about setup, pricing, or templates? Send our team a message.
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

                    <!-- FAQs Accordion -->
                    <div class="space-y-4 pt-4">
                        <h3 class="text-xl font-bold text-white">Frequently Asked Questions</h3>
                        
                        <div class="space-y-3">
                            <details class="group bg-slate-900/40 border border-slate-850 rounded-2xl p-5 [&_summary::-webkit-details-marker]:hidden">
                                <summary class="flex justify-between items-center cursor-pointer outline-none">
                                    <h4 class="text-sm font-semibold text-white pr-4">Can parents upload their own photos?</h4>
                                    <span class="transition duration-300 group-open:-rotate-180 text-amber-400">&darr;</span>
                                </summary>
                                <p class="text-xs text-slate-400 mt-3 leading-relaxed">
                                    Yes! Launch a photo campaign from your dashboard and share the generated links. Parents or teachers can open the links on their phones to upload verified student profile photos directly.
                                </p>
                            </details>

                            <details class="group bg-slate-900/40 border border-slate-850 rounded-2xl p-5 [&_summary::-webkit-details-marker]:hidden">
                                <summary class="flex justify-between items-center cursor-pointer outline-none">
                                    <h4 class="text-sm font-semibold text-white pr-4">What paper format is used for printing?</h4>
                                    <span class="transition duration-300 group-open:-rotate-180 text-amber-400">&darr;</span>
                                </summary>
                                <p class="text-xs text-slate-400 mt-3 leading-relaxed">
                                    The system generates high-res PDF sheets aligned to standard A4 or Letter sizes with grids of 8 or 10 ID cards (standard CR80 credit card dimensions).
                                </p>
                            </details>

                            <details class="group bg-slate-900/40 border border-slate-850 rounded-2xl p-5 [&_summary::-webkit-details-marker]:hidden">
                                <summary class="flex justify-between items-center cursor-pointer outline-none">
                                    <h4 class="text-sm font-semibold text-white pr-4">Can we build custom designs?</h4>
                                    <span class="transition duration-300 group-open:-rotate-180 text-amber-400">&darr;</span>
                                </summary>
                                <p class="text-xs text-slate-400 mt-3 leading-relaxed">
                                    Absolutely. Our interactive template editor lets you select custom backgrounds, reposition name and detail text boxes, and generate custom QR layouts.
                                </p>
                            </details>
                        </div>
                    </div>
                </div>

                <!-- Right Form Box -->
                <div class="bg-slate-900 border border-slate-850 rounded-3xl p-8 shadow-2xl relative">
                    <h3 class="text-xl font-bold text-white mb-6">Send Message</h3>
                    
                    <form id="contactForm" onsubmit="event.preventDefault(); document.getElementById('successMsg').classList.remove('hidden'); document.getElementById('contactForm').reset();" class="space-y-5">
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Full Name</label>
                            <input type="text" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white placeholder-slate-600 focus:outline-none focus:border-amber-500/50 transition" placeholder="John Doe">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Email Address</label>
                            <input type="email" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white placeholder-slate-600 focus:outline-none focus:border-amber-500/50 transition" placeholder="john@example.com">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Message</label>
                            <textarea required rows="4" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-sm text-white placeholder-slate-600 focus:outline-none focus:border-amber-500/50 transition" placeholder="Tell us how we can help you..."></textarea>
                        </div>

                        <button type="submit" class="w-full inline-flex items-center justify-center px-6 py-3.5 text-sm font-bold text-slate-950 bg-gradient-to-r from-amber-400 to-amber-500 hover:from-amber-300 hover:to-amber-400 rounded-xl transition duration-200 shadow-lg shadow-amber-500/20">
                            Send Message
                        </button>
                    </form>

                    <!-- Success State Notice -->
                    <div id="successMsg" class="hidden absolute inset-0 bg-slate-900/95 backdrop-blur-sm rounded-3xl p-8 flex flex-col items-center justify-center text-center space-y-4">
                        <div class="w-16 h-16 rounded-full bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                        </div>
                        <div>
                            <h4 class="text-xl font-bold text-white">Message Sent Successfully!</h4>
                            <p class="text-sm text-slate-400 mt-2 max-w-xs mx-auto">Thank you for reaching out. A representative will email you shortly.</p>
                        </div>
                        <button onclick="document.getElementById('successMsg').classList.add('hidden')" class="px-5 py-2.5 bg-slate-950 hover:bg-slate-850 border border-slate-800 rounded-xl text-xs font-bold transition">
                            Send Another Message
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
