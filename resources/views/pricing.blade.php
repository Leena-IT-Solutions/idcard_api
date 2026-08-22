<x-web-layout title="Pricing & Volume Credit Slabs - iCard Maker" description="Transparent, pay-as-you-go ID card credit pricing for schools, institutes, and commercial printing vendors. Unused credits never expire.">
<div class="max-w-7xl mx-auto w-full px-4 sm:px-6 py-12 sm:py-16" x-data="{
    plans: {{ Js::from($plans->isNotEmpty() ? $plans : [
        ['name' => 'Starter Pack', 'min_quantity' => 1, 'max_quantity' => 499, 'price_per_credit' => '12.00', 'bonus_percentage' => 0],
        ['name' => 'Growth Pack', 'min_quantity' => 500, 'max_quantity' => 999, 'price_per_credit' => '10.00', 'bonus_percentage' => 10, 'badge_text' => 'Popular'],
        ['name' => 'Institution Pack', 'min_quantity' => 1000, 'max_quantity' => 2499, 'price_per_credit' => '8.00', 'bonus_percentage' => 15, 'badge_text' => 'Recommended'],
        ['name' => 'Vendor Mega Pack', 'min_quantity' => 2500, 'max_quantity' => 4999, 'price_per_credit' => '6.00', 'bonus_percentage' => 20, 'badge_text' => 'Best Value'],
        ['name' => 'Commercial Press', 'min_quantity' => 5000, 'max_quantity' => null, 'price_per_credit' => '4.50', 'bonus_percentage' => 30, 'badge_text' => 'Mega Volume'],
    ]) }},
    quantity: 1000,
    quickQuantities: [250, 500, 1000, 2500, 5000],
    
    get currentPlan() {
        const q = parseInt(this.quantity) || 1;
        let match = this.plans.find(p => q >= parseInt(p.min_quantity) && (p.max_quantity === null || p.max_quantity === undefined || q <= parseInt(p.max_quantity)));
        if (!match && this.plans.length > 0) {
            match = this.plans[this.plans.length - 1];
        }
        return match || { name: 'Institution Pack', price_per_credit: '8.00', bonus_percentage: 15 };
    },

    get rate() {
        return parseFloat(this.currentPlan.price_per_credit) || 8.00;
    },

    get bonusPercentage() {
        return parseInt(this.currentPlan.bonus_percentage) || 0;
    },

    get bonusCredits() {
        const q = parseInt(this.quantity) || 0;
        return Math.round((q * this.bonusPercentage) / 100);
    },

    get totalCredits() {
        const q = parseInt(this.quantity) || 0;
        return q + this.bonusCredits;
    },

    get subtotal() {
        const q = parseInt(this.quantity) || 0;
        return (q * this.rate);
    },

    get gst() {
        return this.subtotal * 0.18;
    },

    get totalPayable() {
        return this.subtotal + this.gst;
    },

    get effectiveRate() {
        return this.totalCredits > 0 ? (this.subtotal / this.totalCredits).toFixed(2) : this.rate.toFixed(2);
    },

    get nextTier() {
        const q = parseInt(this.quantity) || 1;
        return this.plans.find(p => parseInt(p.min_quantity) > q) || null;
    }
}">

    <!-- =================== HERO HEADER =================== -->
    <div class="text-center max-w-3xl mx-auto space-y-4 mb-16">
        <span class="inline-flex items-center space-x-2 px-3.5 py-1.5 rounded-full bg-amber-500/10 border border-amber-500/20 text-xs font-black tracking-widest text-amber-400 uppercase">
            💎 Pay-As-You-Go Credit Slabs
        </span>
        <h1 class="text-3xl sm:text-5xl font-black tracking-tight leading-tight text-white">
            Transparent Pricing for <span class="bg-gradient-to-r from-amber-400 via-orange-400 to-amber-500 bg-clip-text text-transparent">Every Institution</span>
        </h1>
        <p class="text-base sm:text-lg text-slate-400 leading-relaxed max-w-2xl mx-auto">
            No monthly lock-ins or recurring fees. Buy credits as you need them. Unused credits have <strong class="text-slate-200">lifetime validity</strong>, and higher volumes unlock <strong class="text-amber-400">up to 30% FREE bonus cards!</strong>
        </p>
    </div>

    <!-- =================== INTERACTIVE LIVE CALCULATOR =================== -->
    <div class="max-w-4xl mx-auto mb-20 bg-gradient-to-b from-slate-900/90 to-slate-950/90 border border-slate-800 rounded-3xl p-6 sm:p-10 shadow-2xl backdrop-blur-xl relative overflow-hidden">
        <div class="absolute -top-24 -right-24 w-72 h-72 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-24 -left-24 w-72 h-72 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative z-10 space-y-8">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-800 pb-6">
                <div>
                    <h2 class="text-xl sm:text-2xl font-black text-white flex items-center gap-2">
                        <span>⚡</span> Interactive Cost & Bonus Calculator
                    </h2>
                    <p class="text-xs text-slate-400 mt-1">
                        Slide or enter required cards to see real-time volume discounts and free bonus cards.
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs font-bold text-slate-400">Active Tier:</span>
                    <span class="px-3 py-1 bg-amber-500/20 border border-amber-500/40 text-amber-300 rounded-full text-xs font-black" x-text="currentPlan.name"></span>
                </div>
            </div>

            <!-- Slider + Number Input -->
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <label class="text-xs font-black uppercase tracking-wider text-slate-300">How many ID cards do you need?</label>
                    <div class="flex items-center gap-2">
                        <input type="number" 
                               x-model.number="quantity" 
                               value="1000"
                               min="10" 
                               max="20000" 
                               step="50"
                               class="w-32 bg-slate-950 border border-slate-700 text-amber-400 font-mono font-black text-right text-base rounded-xl px-3 py-1.5 focus:border-amber-500 focus:ring-1 focus:ring-amber-500 focus:outline-none">
                        <span class="text-xs font-bold text-slate-400">Cards</span>
                    </div>
                </div>

                <!-- Range Slider -->
                <input type="range" 
                       x-model.number="quantity" 
                       value="1000"
                       min="50" 
                       max="10000" 
                       step="50"
                       class="w-full h-2.5 bg-slate-800 rounded-lg appearance-none cursor-pointer accent-amber-500">

                <!-- Quick Quantity Pills -->
                <div class="flex flex-wrap items-center gap-2 pt-1">
                    <span class="text-[11px] font-bold text-slate-500">Quick Select:</span>
                    <template x-for="q in quickQuantities" :key="q">
                        <button type="button" 
                                @click="quantity = q" 
                                class="px-3 py-1 text-xs font-bold rounded-lg transition"
                                :class="quantity === q ? 'bg-amber-500 text-slate-950 font-black' : 'bg-slate-800 hover:bg-slate-700 text-slate-300'">
                            <span x-text="Number(q).toLocaleString() + ' cards'"></span>
                        </button>
                    </template>
                </div>
            </div>

            <!-- Upsell Incentive Nudge -->
            <template x-if="nextTier">
                <div class="p-4 rounded-2xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-between gap-4 text-xs">
                    <div class="flex items-center gap-2 text-amber-200">
                        <span class="text-base">🚀</span>
                        <span>
                            Add <strong><span x-text="(nextTier.min_quantity - quantity).toLocaleString()"></span> more cards</strong> to unlock <strong class="text-amber-400" x-text="nextTier.name"></strong> at <strong>₹<span x-text="nextTier.price_per_credit"></span>/card</strong> + <strong class="text-emerald-400" x-text="nextTier.bonus_percentage + '% FREE Bonus'"></strong>!
                        </span>
                    </div>
                    <button type="button" 
                            @click="quantity = nextTier.min_quantity" 
                            class="px-3 py-1.5 bg-amber-500 hover:bg-amber-400 text-slate-950 font-black rounded-lg text-xs shrink-0 transition">
                        Upgrade Tier
                    </button>
                </div>
            </template>

            <!-- Results Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 pt-2">
                <div class="p-4 rounded-2xl bg-slate-950/60 border border-slate-800/80">
                    <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Base Rate / Card</span>
                    <div class="text-xl sm:text-2xl font-mono font-black text-white mt-1">
                        ₹<span x-text="rate.toFixed(2)"></span>
                    </div>
                    <span class="text-[10px] text-slate-500">Volume slab rate</span>
                </div>

                <div class="p-4 rounded-2xl bg-emerald-950/30 border border-emerald-500/30">
                    <span class="text-[10px] font-black uppercase tracking-wider text-emerald-400">FREE Bonus Cards</span>
                    <div class="text-xl sm:text-2xl font-mono font-black text-emerald-300 mt-1">
                        +<span x-text="bonusCredits.toLocaleString()"></span>
                    </div>
                    <span class="text-[10px] text-emerald-400/80 font-bold" x-text="bonusPercentage > 0 ? (bonusPercentage + '% Extra Free') : 'No bonus on starter'"></span>
                </div>

                <div class="p-4 rounded-2xl bg-amber-950/30 border border-amber-500/30">
                    <span class="text-[10px] font-black uppercase tracking-wider text-amber-400">Total Cards Received</span>
                    <div class="text-xl sm:text-2xl font-mono font-black text-amber-300 mt-1">
                        <span x-text="totalCredits.toLocaleString()"></span>
                    </div>
                    <span class="text-[10px] text-slate-400 font-mono">Eff: ₹<span x-text="effectiveRate"></span>/card</span>
                </div>

                <div class="p-4 rounded-2xl bg-indigo-950/40 border border-indigo-500/40">
                    <span class="text-[10px] font-black uppercase tracking-wider text-indigo-300">Total (incl. 18% GST)</span>
                    <div class="text-xl sm:text-2xl font-mono font-black text-indigo-200 mt-1">
                        ₹<span x-text="Math.round(totalPayable).toLocaleString()"></span>
                    </div>
                    <span class="text-[10px] text-slate-400 font-mono">Sub: ₹<span x-text="subtotal.toLocaleString()"></span></span>
                </div>
            </div>

            <!-- CTA Button -->
            <div class="pt-2 text-center sm:text-right">
                <a href="{{ route('register') }}" 
                   class="w-full sm:w-auto inline-flex items-center justify-center gap-3 px-8 py-4 bg-gradient-to-r from-amber-400 via-orange-500 to-amber-500 hover:from-amber-300 hover:to-amber-400 text-slate-950 font-black text-sm uppercase tracking-wider rounded-2xl shadow-xl shadow-amber-500/20 transition transform hover:-translate-y-0.5">
                    <span>Create Free Account & Recharge Wallet</span>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                </a>
            </div>
        </div>
    </div>

    <!-- =================== VOLUME PRICING SLABS GRID =================== -->
    <div class="text-center mb-12">
        <h2 class="text-2xl sm:text-3xl font-extrabold text-white">All Volume Pricing Slabs</h2>
        <p class="text-xs text-slate-500 uppercase tracking-widest font-semibold mt-1">Automated tiered pricing with free bonus cards</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-6 items-stretch mb-24">
        @forelse($plans as $plan)
            @php
                $isPopular = str_contains(strtolower($plan->badge_text ?? ''), 'popular') || str_contains(strtolower($plan->name), 'growth');
                $isRecommended = str_contains(strtolower($plan->badge_text ?? ''), 'recommended') || str_contains(strtolower($plan->name), 'institution');
                $isBest = str_contains(strtolower($plan->badge_text ?? ''), 'best') || str_contains(strtolower($plan->badge_text ?? ''), 'volume');
                $isHighlight = $isPopular || $isRecommended || $isBest;
            @endphp
            <div class="flex flex-col rounded-3xl p-6 relative justify-between transition-all duration-300 hover:-translate-y-1.5
                        {{ $isHighlight ? 'bg-slate-900 border-2 ' . ($isPopular ? 'border-amber-500/80 shadow-2xl shadow-amber-500/10' : ($isRecommended ? 'border-indigo-500/80 shadow-2xl shadow-indigo-500/10' : 'border-emerald-500/80 shadow-2xl shadow-emerald-500/10')) : 'bg-slate-900/50 border border-slate-800 shadow-xl' }}">
                
                @if($plan->badge_text)
                    <div class="absolute -top-3 left-1/2 -translate-x-1/2 px-3 py-0.5 text-[10px] font-black tracking-widest uppercase rounded-full shadow-md
                                {{ $isPopular ? 'bg-amber-500 text-slate-950' : ($isRecommended ? 'bg-indigo-500 text-white' : 'bg-emerald-500 text-slate-950') }}">
                        {{ $plan->badge_text }}
                    </div>
                @endif

                <div>
                    <h3 class="text-base font-black text-white">{{ $plan->name }}</h3>
                    <p class="text-xs text-slate-400 mt-0.5 font-medium">
                        @if($plan->max_quantity)
                            {{ number_format($plan->min_quantity) }} – {{ number_format($plan->max_quantity) }} cards
                        @else
                            {{ number_format($plan->min_quantity) }}+ cards
                        @endif
                    </p>

                    <!-- Price -->
                    <div class="mt-5 flex items-baseline">
                        <span class="text-3xl sm:text-4xl font-black tracking-tight text-white">₹{{ number_format($plan->price_per_credit, 2) }}</span>
                        <span class="text-xs font-bold text-slate-400 ml-1">/ card</span>
                    </div>

                    <!-- Bonus Badge -->
                    <div class="mt-3">
                        @if($plan->bonus_percentage > 0)
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-emerald-500/15 border border-emerald-500/30 text-[11px] font-black text-emerald-400">
                                <span>🎁</span> +{{ $plan->bonus_percentage }}% FREE Bonus
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-slate-800 text-[11px] font-bold text-slate-400">
                                Standard Rate
                            </span>
                        @endif
                    </div>

                    <!-- Features List -->
                    <ul class="mt-6 space-y-3 text-xs text-slate-300">
                        <li class="flex items-center gap-2.5">
                            <svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                            <span>Print-Ready High-Res Imposition</span>
                        </li>
                        <li class="flex items-center gap-2.5">
                            <svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                            <span>QR & Barcode Generation</span>
                        </li>
                        <li class="flex items-center gap-2.5">
                            <svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                            <span>Live Data Collection Links</span>
                        </li>
                        <li class="flex items-center gap-2.5">
                            <svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                            <span>Lifetime Credit Validity</span>
                        </li>
                    </ul>
                </div>

                <!-- CTA -->
                <div class="mt-8">
                    <a href="{{ route('register') }}" 
                       class="w-full inline-flex items-center justify-center px-4 py-2.5 text-xs font-black uppercase tracking-wider rounded-xl transition duration-200 shadow-md
                              {{ $isPopular ? 'bg-amber-500 hover:bg-amber-400 text-slate-950 shadow-amber-500/20' : ($isRecommended ? 'bg-indigo-600 hover:bg-indigo-500 text-white shadow-indigo-500/20' : 'bg-slate-800 hover:bg-slate-700 text-slate-200') }}">
                        Choose Plan
                    </a>
                </div>
            </div>
        @empty
            <div class="col-span-full text-center py-12 text-slate-400">
                {{ __('No credit plans configured.') }}
            </div>
        @endforelse
    </div>

    <!-- =================== PRINTING COMPANY & COMMERCIAL PARTNERS =================== -->
    <div class="max-w-5xl mx-auto border-t border-slate-900 pt-16 mb-20 text-center">
        <div class="space-y-3 mb-10">
            <h2 class="text-2xl sm:text-3xl font-extrabold text-white">Printing Company & Vendor Partnerships</h2>
            <p class="text-xs text-slate-500 uppercase tracking-widest font-semibold">High-volume workflows for commercial offset & digital printers</p>
        </div>
        
        <div class="relative bg-gradient-to-r from-slate-900 via-slate-900 to-slate-950 border border-slate-800 rounded-3xl p-8 max-w-4xl mx-auto overflow-hidden flex flex-col md:flex-row items-center justify-between gap-6 shadow-2xl">
            <div class="absolute inset-0 bg-gradient-to-r from-amber-500/5 via-transparent to-indigo-500/5 blur-2xl pointer-events-none"></div>
            <div class="text-left max-w-lg relative z-10 space-y-2">
                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-500/10 border border-amber-500/20 text-[10px] font-black text-amber-400 uppercase tracking-wider">
                    <span>🖨️</span> Commercial Press Edition
                </div>
                <h4 class="text-xl font-bold text-white">High-Speed A4 Imposition & Sheet Layouts</h4>
                <p class="text-xs text-slate-400 leading-relaxed">
                    Custom paper dimensions, 8-up / 10-up card grid impositions, crop & cut marks, bleed margins, and automated webhook triggers for direct press integration.
                </p>
            </div>
            <div class="relative z-10 shrink-0">
                <a href="/contact" class="inline-flex items-center justify-center px-6 py-3.5 text-xs font-black uppercase tracking-wider text-slate-950 bg-gradient-to-r from-amber-400 to-amber-500 hover:from-amber-300 hover:to-amber-400 rounded-xl transition duration-200 shadow-xl shadow-amber-500/20 whitespace-nowrap">
                    Contact Partner Sales
                </a>
            </div>
        </div>
    </div>

    <!-- =================== FREQUENTLY ASKED QUESTIONS =================== -->
    <div class="max-w-4xl mx-auto border-t border-slate-900 pt-16">
        <div class="text-center mb-12">
            <h2 class="text-2xl sm:text-3xl font-extrabold text-white">Frequently Asked Questions</h2>
            <p class="text-xs text-slate-500 uppercase tracking-widest font-semibold mt-1">Everything you need to know about our credit system</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-slate-900/40 border border-slate-800/80 rounded-2xl p-6 space-y-2">
                <h4 class="text-sm font-bold text-white flex items-center gap-2">
                    <span class="text-amber-400">❓</span> Do purchased credits ever expire?
                </h4>
                <p class="text-xs text-slate-400 leading-relaxed">
                    No! All credits have <strong>lifetime validity</strong>. You can purchase credits in bulk today to take advantage of volume discounts and use them across multiple academic sessions or schools.
                </p>
            </div>

            <div class="bg-slate-900/40 border border-slate-800/80 rounded-2xl p-6 space-y-2">
                <h4 class="text-sm font-bold text-white flex items-center gap-2">
                    <span class="text-amber-400">❓</span> How are credits deducted?
                </h4>
                <p class="text-xs text-slate-400 leading-relaxed">
                    Credits are deducted strictly when you <strong>export or generate finalized student ID cards</strong> for printing (1 credit = 1 student ID card). Template designing, student photo uploads, and data collection are 100% free.
                </p>
            </div>

            <div class="bg-slate-900/40 border border-slate-800/80 rounded-2xl p-6 space-y-2">
                <h4 class="text-sm font-bold text-white flex items-center gap-2">
                    <span class="text-amber-400">❓</span> How do bonus credits work?
                </h4>
                <p class="text-xs text-slate-400 leading-relaxed">
                    When you purchase in higher slabs (e.g. 1,000 cards), our system automatically calculates and adds <strong>free bonus credits</strong> (e.g. +15% = 150 free cards) directly to your wallet at zero additional charge.
                </p>
            </div>

            <div class="bg-slate-900/40 border border-slate-800/80 rounded-2xl p-6 space-y-2">
                <h4 class="text-sm font-bold text-white flex items-center gap-2">
                    <span class="text-amber-400">❓</span> Which payment methods are accepted?
                </h4>
                <p class="text-xs text-slate-400 leading-relaxed">
                    We support instant recharge via <strong>UPI (GPay, PhonePe, Paytm), Debit/Credit Cards, NetBanking</strong> via Razorpay, as well as Direct Bank NEFT/RTGS transfers for institutional purchase orders.
                </p>
            </div>
        </div>
    </div>

</div>
</x-web-layout>
