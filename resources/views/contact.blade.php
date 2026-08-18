<x-web-layout title="Contact - iCard Maker" description="Get in touch with iCard Maker support or book a live product demo.">
<div class="max-w-7xl mx-auto w-full px-6 py-16">
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
</div>
</x-web-layout>
