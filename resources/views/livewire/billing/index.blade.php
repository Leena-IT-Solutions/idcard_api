<?php

use App\Models\CreditOrder;
use App\Models\CreditPlan;
use App\Models\CreditTransaction;
use App\Models\School;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public int $requestedCards = 1000;
    public string $paymentMethod = 'razorpay'; // razorpay, bank_transfer, offline_request
    public string $orderNotes = '';
    public bool $showSuccessModal = false;
    public ?int $lastSubmittedOrderId = null;

    // Toast
    public string $toastMessage = '';
    public string $toastType = 'success';

    public function mount(): void
    {
        $this->ensureDefaultPlansExist();
    }

    private function ensureDefaultPlansExist(): void
    {
        if (CreditPlan::count() === 0) {
            $defaultPlans = [
                ['name' => 'Starter Pack', 'min_quantity' => 1, 'max_quantity' => 499, 'price_per_credit' => 12.00, 'bonus_percentage' => 0, 'is_active' => true, 'sort_order' => 1, 'badge_text' => null, 'badge_color' => null],
                ['name' => 'Growth Pack', 'min_quantity' => 500, 'max_quantity' => 999, 'price_per_credit' => 10.00, 'bonus_percentage' => 10, 'is_active' => true, 'sort_order' => 2, 'badge_text' => 'Popular', 'badge_color' => 'bg-blue-600 text-white'],
                ['name' => 'Institution Pack', 'min_quantity' => 1000, 'max_quantity' => 2499, 'price_per_credit' => 8.00, 'bonus_percentage' => 15, 'is_active' => true, 'sort_order' => 3, 'badge_text' => 'Recommended', 'badge_color' => 'bg-indigo-600 text-white'],
                ['name' => 'Vendor Mega Pack', 'min_quantity' => 2500, 'max_quantity' => 4999, 'price_per_credit' => 6.00, 'bonus_percentage' => 20, 'is_active' => true, 'sort_order' => 4, 'badge_text' => 'Best Value', 'badge_color' => 'bg-emerald-600 text-white'],
                ['name' => 'Commercial Press', 'min_quantity' => 5000, 'max_quantity' => null, 'price_per_credit' => 4.50, 'bonus_percentage' => 30, 'is_active' => true, 'sort_order' => 5, 'badge_text' => 'Mega Volume', 'badge_color' => 'bg-purple-600 text-white'],
            ];
            foreach ($defaultPlans as $p) {
                CreditPlan::create($p);
            }
        }
    }

    public function setPreset(int $amount): void
    {
        $this->requestedCards = $amount;
    }

    public function incrementCards(int $step = 100): void
    {
        $this->requestedCards = min(50000, $this->requestedCards + $step);
    }

    public function decrementCards(int $step = 100): void
    {
        $this->requestedCards = max(100, $this->requestedCards - $step);
    }

    public function getActiveSchoolProperty(): ?School
    {
        $schoolId = session('active_school_id');
        if (!$schoolId && auth()->user()->hasRole('saas_admin')) {
            return School::first();
        }
        return $schoolId ? School::find($schoolId) : null;
    }

    public function getCalculationProperty(): array
    {
        $this->ensureDefaultPlansExist();
        return CreditPlan::calculateForQuantity($this->requestedCards);
    }

    public function with(): array
    {
        $this->ensureDefaultPlansExist();
        $school = $this->activeSchool;
        $plans = CreditPlan::active()->get();

        $myOrders = $school
            ? CreditOrder::where('school_id', $school->id)->latest()->paginate(5, ['*'], 'ordersPage')
            : collect();

        $myTransactions = $school
            ? CreditTransaction::where('school_id', $school->id)->latest()->paginate(8, ['*'], 'trxPage')
            : collect();

        return [
            'school' => $school,
            'plans' => $plans,
            'calc' => $this->calculation,
            'orders' => $myOrders,
            'transactions' => $myTransactions,
        ];
    }

    public function submitRechargeRequest(): void
    {
        $school = $this->activeSchool;
        if (!$school) {
            $this->toast('Please select an active school first.', 'warning');
            return;
        }

        $this->validate([
            'requestedCards' => 'required|integer|min:1|max:50000',
            'orderNotes' => 'nullable|string|max:500',
        ]);

        $calc = $this->calculation;
        $user = auth()->user();

        $order = CreditOrder::create([
            'school_id' => $school->id,
            'user_id' => $user->id,
            'ordered_credits' => $calc['quantity'],
            'bonus_credits' => $calc['bonus_credits'],
            'total_credited' => $calc['total_credits'],
            'price_per_credit' => $calc['rate'],
            'subtotal' => $calc['subtotal'],
            'gst_amount' => $calc['gst'],
            'total_amount' => $calc['total_amount'],
            'payment_method' => $this->paymentMethod,
            'payment_reference' => 'REQ-' . strtoupper(uniqid()),
            'status' => 'pending',
            'notes' => $this->orderNotes,
        ]);

        $this->lastSubmittedOrderId = $order->id;
        $this->showSuccessModal = true;
        $this->orderNotes = '';
    }

    private function toast(string $msg, string $type = 'success'): void
    {
        $this->toastMessage = $msg;
        $this->toastType = $type;
    }
}; ?>

<div class="space-y-6">
    <!-- Toast Notification Banner -->
    @if($toastMessage)
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" 
             class="p-4 rounded-2xl flex items-center justify-between shadow-xl transition-all 
             {{ $toastType === 'success' ? 'bg-emerald-600 text-white' : ($toastType === 'warning' ? 'bg-amber-500 text-white' : 'bg-indigo-600 text-white') }}">
            <div class="flex items-center gap-3">
                <svg class="w-6 h-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="font-bold text-sm">{{ $toastMessage }}</span>
            </div>
            <button @click="show = false" class="text-white/80 hover:text-white text-sm font-bold">✕</button>
        </div>
    @endif

    @if(!$school)
        <div class="bg-amber-50 dark:bg-amber-950/60 p-8 rounded-3xl border border-amber-200 dark:border-amber-800 text-center space-y-3 shadow-xl">
            <div class="w-12 h-12 bg-amber-100 dark:bg-amber-900/60 text-amber-600 rounded-2xl flex items-center justify-center mx-auto text-xl font-bold">
                🏫
            </div>
            <h3 class="text-lg font-black text-amber-900 dark:text-amber-200">{{ __('No Active School Workspace Selected') }}</h3>
            <p class="text-xs text-amber-700 dark:text-amber-400 max-w-md mx-auto">
                {{ __('Please select a school from the School Profiles menu to manage its wallet balance and purchase credits.') }}
            </p>
            <div class="pt-2">
                <a href="{{ route('schools') }}" wire:navigate class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold uppercase tracking-wider inline-flex items-center gap-2 shadow">
                    <span>{{ __('Go to School Profiles') }} →</span>
                </a>
            </div>
        </div>
    @else
        <!-- Top Section: Balance Card + Value Props Banner -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Balance Card: Rock Solid Premium Dark Theme -->
            <div class="lg:col-span-1 bg-gray-900 dark:bg-gray-950 text-white rounded-3xl p-7 border border-gray-800 shadow-2xl shadow-gray-950/30 relative overflow-hidden flex flex-col justify-between">
                <div class="absolute -right-10 -bottom-10 w-44 h-44 bg-indigo-600/20 rounded-full blur-2xl pointer-events-none"></div>

                <div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-xl bg-indigo-500/20 text-indigo-400 flex items-center justify-center font-bold">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                </svg>
                            </div>
                            <span class="text-xs font-bold uppercase tracking-wider text-gray-400">{{ __('Wallet Balance') }}</span>
                        </div>

                        @if($school->credits_balance >= 500)
                            <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                                🟢 {{ __('Healthy') }}
                            </span>
                        @elseif($school->credits_balance >= 100)
                            <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-amber-500/20 text-amber-400 border border-amber-500/30">
                                🟡 {{ __('Low Balance') }}
                            </span>
                        @else
                            <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-rose-500/20 text-rose-400 border border-rose-500/30">
                                🔴 {{ __('Empty / Top-up') }}
                            </span>
                        @endif
                    </div>

                    <div class="mt-6 flex items-baseline gap-2">
                        <span class="text-5xl sm:text-6xl font-black text-white font-mono tracking-tight">
                            {{ number_format($school->credits_balance) }}
                        </span>
                        <span class="text-sm font-bold text-gray-400 uppercase tracking-wider">cards</span>
                    </div>

                    <p class="text-xs text-gray-400 mt-2 font-medium">
                        {{ __('1 Credit = 1 Generated / Exported Student ID Card with single or imposition sheet print.') }}
                    </p>
                </div>

                <div class="mt-6 pt-5 border-t border-gray-800 flex items-center justify-between text-xs text-gray-400">
                    <span class="flex items-center gap-1.5 font-bold text-emerald-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        <span>{{ __('Credits Never Expire') }}</span>
                    </span>
                    <span class="font-bold text-white truncate max-w-[140px] text-right" title="{{ $school->name }}">{{ $school->name }}</span>
                </div>
            </div>

            <!-- Value Props & Highlights -->
            <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-3xl p-7 border border-gray-100 dark:border-gray-700 shadow-xl shadow-gray-200/40 dark:shadow-none flex flex-col justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 rounded-full text-[10px] font-extrabold uppercase tracking-wider border border-emerald-200 dark:border-emerald-900/30">
                            🎁 {{ __('Volume Discounts & Free Bonus') }}
                        </span>
                    </div>
                    <h2 class="text-xl sm:text-2xl font-black text-gray-900 dark:text-gray-100 tracking-tight mt-2">
                        {{ __('Flexible ID Card Generation & Recharge') }}
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        {{ __('Type any number of student cards you require. Higher purchase quantities automatically unlock cheaper rates and up to +30% FREE bonus cards.') }}
                    </p>
                </div>

                <!-- 3 Feature Chips -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-6 pt-5 border-t border-gray-100 dark:border-gray-700">
                    <div class="p-3.5 rounded-2xl bg-gray-50 dark:bg-gray-900/60 border border-gray-100 dark:border-gray-700/60">
                        <div class="font-black text-xs text-gray-900 dark:text-gray-100">🖨️ {{ __('All Formats Included') }}</div>
                        <div class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">{{ __('Single PDF, Imposition 10-Up, & PNG Zip') }}</div>
                    </div>
                    <div class="p-3.5 rounded-2xl bg-gray-50 dark:bg-gray-900/60 border border-gray-100 dark:border-gray-700/60">
                        <div class="font-black text-xs text-gray-900 dark:text-gray-100">⚡ {{ __('Instant Deduction') }}</div>
                        <div class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">{{ __('Credits deduct only upon final export generation') }}</div>
                    </div>
                    <div class="p-3.5 rounded-2xl bg-gray-50 dark:bg-gray-900/60 border border-gray-100 dark:border-gray-700/60">
                        <div class="font-black text-xs text-gray-900 dark:text-gray-100">🔒 {{ __('Zero Hidden Fees') }}</div>
                        <div class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">{{ __('Full access to template designer & mobile app') }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- =================== RECHARGE CALCULATOR & TIER TILES =================== -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Left: Dynamic Live Calculator (7 cols) -->
            <div class="lg:col-span-7 bg-white dark:bg-gray-800 rounded-3xl p-6 sm:p-8 border border-gray-100 dark:border-gray-700 shadow-xl shadow-gray-200/40 dark:shadow-none space-y-6">
                <div class="border-b border-gray-100 dark:border-gray-700 pb-4">
                    <h3 class="text-lg font-black text-gray-900 dark:text-gray-100 tracking-tight">
                        {{ __('Calculate & Purchase Credits') }}
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ __('Select your required quantity below. Price, volume tier, and bonus credits update in real-time.') }}
                    </p>
                </div>

                <!-- Quantity Stepper & Range Slider -->
                <div class="space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <label class="font-black text-xs uppercase tracking-wider text-gray-700 dark:text-gray-300">{{ __('Student ID Cards Needed') }}</label>
                        
                        <div class="flex items-center gap-2">
                            <button type="button" wire:click="decrementCards(100)" class="w-9 h-9 rounded-xl bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 text-gray-700 dark:text-gray-200 font-black text-lg flex items-center justify-center transition active:scale-95 shadow-sm">
                                −
                            </button>
                            <input type="number" wire:model.live.debounce.100ms="requestedCards" min="100" max="50000" step="50"
                                   class="w-36 bg-gray-50 dark:bg-gray-900 border-2 border-indigo-500 rounded-xl px-3 py-2 text-center font-mono font-black text-xl text-indigo-600 dark:text-indigo-400 focus:ring-2 focus:ring-indigo-500 shadow-inner" />
                            <button type="button" wire:click="incrementCards(100)" class="w-9 h-9 rounded-xl bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 text-gray-700 dark:text-gray-200 font-black text-lg flex items-center justify-center transition active:scale-95 shadow-sm">
                                +
                            </button>
                            <span class="text-xs font-bold text-gray-400 uppercase">cards</span>
                        </div>
                    </div>

                    <!-- Range Slider -->
                    <div class="pt-2">
                        <input type="range" wire:model.live="requestedCards" min="100" max="10000" step="50" 
                               class="w-full h-3 bg-gray-200 dark:bg-gray-700 rounded-lg appearance-none cursor-pointer accent-indigo-600" />
                        <div class="flex justify-between text-[10px] font-mono text-gray-400 font-bold mt-1">
                            <span>100</span>
                            <span>2,500</span>
                            <span>5,000</span>
                            <span>7,500</span>
                            <span>10,000+</span>
                        </div>
                    </div>

                    <!-- Quick Preset Buttons -->
                    <div class="flex flex-wrap items-center gap-2 pt-2">
                        <span class="text-xs font-black uppercase text-gray-400 mr-1">{{ __('Presets:') }}</span>
                        @foreach([500, 1000, 2500, 5000, 10000] as $preset)
                            <button type="button" wire:click="setPreset({{ $preset }})" 
                                    class="px-3.5 py-1.5 rounded-xl text-xs font-black font-mono transition 
                                    {{ $requestedCards == $preset ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}">
                                {{ number_format($preset) }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <!-- Live Price Breakdown Panel -->
                <div class="bg-indigo-50/50 dark:bg-indigo-950/30 rounded-3xl p-6 border-2 border-indigo-100 dark:border-indigo-900/60 space-y-3">
                    <div class="flex items-center justify-between text-xs text-gray-600 dark:text-gray-300 font-medium">
                        <span>{{ __('Base Cards Ordered:') }}</span>
                        <span class="font-mono font-bold text-gray-900 dark:text-gray-100 text-sm">{{ number_format($calc['quantity']) }} cards</span>
                    </div>

                    <div class="flex items-center justify-between text-xs text-gray-600 dark:text-gray-300 font-medium">
                        <span>{{ __('Volume Tier Applied:') }}</span>
                        <span class="font-bold text-gray-900 dark:text-gray-100">
                            <span class="font-mono">₹{{ number_format($calc['rate'], 2) }}</span> / card
                            <span class="text-[11px] text-indigo-600 dark:text-indigo-400 font-bold ml-1">({{ $calc['plan_name'] }})</span>
                        </span>
                    </div>

                    @if($calc['bonus_credits'] > 0)
                        <div class="flex items-center justify-between text-xs bg-emerald-100/80 dark:bg-emerald-950/80 p-3 rounded-2xl border border-emerald-300 dark:border-emerald-800">
                            <span class="font-black text-emerald-900 dark:text-emerald-200 flex items-center gap-1.5">
                                🎁 {{ __('Volume Bonus (+:pct% FREE):', ['pct' => $calc['bonus_percentage']]) }}
                            </span>
                            <span class="font-mono font-black text-emerald-800 dark:text-emerald-300 text-sm">
                                +{{ number_format($calc['bonus_credits']) }} FREE CARDS
                            </span>
                        </div>
                    @endif

                    <div class="flex items-center justify-between text-sm pt-2 border-t border-indigo-100 dark:border-indigo-900/60">
                        <span class="font-black text-gray-900 dark:text-gray-100">{{ __('Total Cards You Will Receive:') }}</span>
                        <span class="font-mono font-black text-indigo-600 dark:text-indigo-400 text-lg">
                            {{ number_format($calc['total_credits']) }} Cards
                        </span>
                    </div>

                    <div class="flex items-center justify-between text-xs text-gray-500 pt-2 border-t border-indigo-100 dark:border-indigo-900/60">
                        <span>{{ __('Effective Rate per Card:') }}</span>
                        <span class="font-mono font-black text-emerald-600">₹{{ number_format($calc['effective_rate'], 2) }} / card</span>
                    </div>

                    <div class="flex items-center justify-between text-xs text-gray-500">
                        <span>{{ __('Subtotal (Excl. Tax):') }}</span>
                        <span class="font-mono font-bold">₹{{ number_format($calc['subtotal'], 2) }}</span>
                    </div>

                    <div class="flex items-center justify-between text-xs text-gray-500">
                        <span>{{ __('GST (18%):') }}</span>
                        <span class="font-mono font-bold">₹{{ number_format($calc['gst'], 2) }}</span>
                    </div>

                    <div class="border-t-2 border-dashed border-indigo-200 dark:border-indigo-900 pt-4 flex items-center justify-between">
                        <span class="font-black text-base text-gray-900 dark:text-gray-100">{{ __('Total Payable Amount:') }}</span>
                        <span class="font-mono font-black text-3xl text-gray-900 dark:text-gray-100">
                            ₹{{ number_format($calc['total_amount'], 2) }}
                        </span>
                    </div>
                </div>

                <!-- Next Tier Upsell Nudge -->
                @if($calc['upsell_nudge'])
                    <div class="bg-amber-50 dark:bg-amber-950/60 p-4 rounded-2xl border border-amber-200 dark:border-amber-800 text-xs text-amber-900 dark:text-amber-200 flex items-center gap-3 shadow-sm">
                        <span class="text-xl shrink-0">💡</span>
                        <div>
                            <span class="font-bold">{{ __('Pro Tip:') }}</span>
                            {{ __('Add just :needed more cards to unlock the :plan tier (:rate/card) with :bonus% FREE bonus!', [
                                'needed' => number_format($calc['upsell_nudge']['needed_more']),
                                'plan' => $calc['upsell_nudge']['next_plan_name'],
                                'rate' => '₹' . $calc['upsell_nudge']['next_rate'],
                                'bonus' => '+' . $calc['upsell_nudge']['next_bonus_pct']
                            ]) }}
                        </div>
                    </div>
                @endif

                <!-- Payment Method & Notes -->
                <div class="space-y-4 pt-2" x-data="{
                    isProcessingRazorpay: false,
                    payWithRazorpay() {
                        this.isProcessingRazorpay = true;
                        fetch('{{ route('razorpay.create-order') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                school_id: {{ $school->id }},
                                requested_cards: parseInt($wire.get('requestedCards') || $wire.requestedCards || 100),
                                notes: $wire.get('orderNotes') || $wire.orderNotes || ''
                            })
                        })
                        .then(r => r.json())
                        .then(data => {
                            this.isProcessingRazorpay = false;
                            if (!data.success) {
                                alert(data.message || 'Error creating Razorpay order');
                                return;
                            }

                            const options = {
                                key: data.key_id,
                                amount: data.amount,
                                currency: data.currency,
                                name: data.name,
                                description: data.description,
                                order_id: data.razorpay_order_id,
                                prefill: data.prefill,
                                notes: data.notes,
                                theme: {
                                    color: '#4f46e5'
                                },
                                handler: (response) => {
                                    this.isProcessingRazorpay = true;
                                    fetch('{{ route('razorpay.verify-payment') }}', {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                            'Accept': 'application/json'
                                        },
                                        body: JSON.stringify({
                                            credit_order_id: data.order_id,
                                            razorpay_order_id: response.razorpay_order_id,
                                            razorpay_payment_id: response.razorpay_payment_id,
                                            razorpay_signature: response.razorpay_signature
                                        })
                                    })
                                    .then(vr => vr.json())
                                    .then(vdata => {
                                        this.isProcessingRazorpay = false;
                                        if (vdata.success) {
                                            alert('🎉 ' + vdata.message);
                                            window.location.reload();
                                        } else {
                                            alert('⚠️ ' + vdata.message);
                                        }
                                    })
                                    .catch(err => {
                                        this.isProcessingRazorpay = false;
                                        alert('Error verifying payment: ' + err.message);
                                    });
                                },
                                modal: {
                                    ondismiss: () => {
                                        this.isProcessingRazorpay = false;
                                    }
                                }
                            };

                            const rzp = new Razorpay(options);
                            rzp.on('payment.failed', function (resp){
                                alert('Payment Failed: ' + (resp.error.description || 'Unknown error'));
                            });
                            rzp.open();
                        })
                        .catch(err => {
                            this.isProcessingRazorpay = false;
                            alert('Network error: ' + err.message);
                        });
                    }
                }">
                    <div>
                        <label class="block font-black text-xs text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1.5">{{ __('Payment Option') }}</label>
                        <select wire:model.live="paymentMethod" class="w-full bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl px-4 py-2.5 text-xs text-gray-900 dark:text-gray-100 font-bold focus:ring-2 focus:ring-indigo-500">
                            <option value="razorpay">⚡ {{ __('Instant Online Payment (Razorpay — UPI / Cards / NetBanking)') }}</option>
                            <option value="bank_transfer">{{ __('Direct Bank Transfer / NEFT / IMPS / Offline UPI') }}</option>
                            <option value="offline_request">{{ __('Submit Purchase Order / Invoice Request') }}</option>
                        </select>
                    </div>

                    @if($paymentMethod !== 'razorpay')
                        <div>
                            <label class="block font-black text-xs text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1.5">{{ __('UTR / Reference Number or Remarks (Optional)') }}</label>
                            <input type="text" wire:model="orderNotes" placeholder="{{ __('e.g. Paid via PhonePe UTR #12345678 or Invoice PO request') }}" 
                                   class="w-full bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl px-4 py-2.5 text-xs text-gray-900 dark:text-gray-100 font-medium focus:ring-2 focus:ring-indigo-500" />
                        </div>

                        <button wire:click="submitRechargeRequest" 
                                class="w-full py-4 bg-indigo-600 hover:bg-indigo-700 text-white font-black text-sm uppercase tracking-wider rounded-2xl shadow-xl shadow-indigo-600/30 transition transform active:scale-[0.99] flex items-center justify-center gap-2 cursor-pointer">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            <span>{{ __('Submit Offline Request (:credits Cards)', ['credits' => number_format($calc['total_credits'])]) }}</span>
                        </button>
                    @else
                        <button @click="payWithRazorpay()" :disabled="isProcessingRazorpay"
                                class="w-full py-4 bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-700 hover:to-violet-700 disabled:opacity-50 text-white font-black text-sm uppercase tracking-wider rounded-2xl shadow-xl shadow-indigo-600/30 transition transform active:scale-[0.99] flex items-center justify-center gap-2 cursor-pointer">
                            <span x-show="!isProcessingRazorpay" class="flex items-center gap-2">
                                <span>⚡</span>
                                <span>{{ __('Pay ₹:amount Online Now (:credits Cards)', ['amount' => number_format($calc['total_amount'], 2), 'credits' => number_format($calc['total_credits'])]) }}</span>
                            </span>
                            <span x-show="isProcessingRazorpay" class="flex items-center gap-2">
                                <svg class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                <span>{{ __('Opening Razorpay Checkout...') }}</span>
                            </span>
                        </button>
                    @endif
                </div>
            </div>

            <!-- Right: Tier Slabs Reference (5 cols) -->
            <div class="lg:col-span-5 space-y-5">
                <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 sm:p-7 border border-gray-100 dark:border-gray-700 shadow-xl shadow-gray-200/40 dark:shadow-none space-y-4">
                    <h3 class="text-sm font-black text-gray-900 dark:text-gray-100 uppercase tracking-wider">{{ __('Available Volume Slabs') }}</h3>
                    
                    <div class="space-y-3">
                        @foreach($plans as $plan)
                            <div class="p-4 rounded-2xl bg-gray-50 dark:bg-gray-900/60 border border-gray-200 dark:border-gray-700 flex items-center justify-between hover:border-indigo-500 transition">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-black text-xs text-gray-900 dark:text-gray-100">{{ $plan->name }}</span>
                                        @if($plan->badge_text)
                                            <span class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase {{ $plan->badge_color ?? 'bg-indigo-600 text-white' }} shadow-sm">
                                                {{ $plan->badge_text }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="text-[11px] text-gray-500 dark:text-gray-400 mt-1 font-mono font-bold">
                                        {{ number_format($plan->min_quantity) }} {{ $plan->max_quantity ? '– ' . number_format($plan->max_quantity) : '+' }} cards
                                    </div>
                                </div>

                                <div class="text-right">
                                    <div class="font-black text-base text-gray-900 dark:text-gray-100 font-mono">₹{{ number_format($plan->price_per_credit, 2) }}</div>
                                    <div class="text-[10px] font-black text-emerald-600">+{{ $plan->bonus_percentage }}% Bonus</div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Direct Settlement Card -->
                    <div class="p-5 rounded-2xl bg-indigo-50/60 dark:bg-indigo-950/40 border border-indigo-100 dark:border-indigo-900/60 text-xs text-gray-700 dark:text-gray-300 space-y-1.5">
                        <div class="font-black text-indigo-900 dark:text-indigo-200 flex items-center gap-2">
                            <span>🏦</span>
                            <span>{{ __('Direct Settlement & Billing Support') }}</span>
                        </div>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400 leading-relaxed">
                            {{ __('For custom procurement invoices, corporate bank transfer verification, or enterprise agreements, please reach out to our SaaS administration team.') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- =================== ORDERS & USAGE HISTORY =================== -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Order History -->
            <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 sm:p-7 border border-gray-100 dark:border-gray-700 shadow-xl shadow-gray-200/40 dark:shadow-none space-y-4">
                <h3 class="text-sm font-black text-gray-900 dark:text-gray-100 uppercase tracking-wider">{{ __('My Recharge Requests') }}</h3>

                <div class="overflow-x-auto rounded-2xl border border-gray-100 dark:border-gray-700">
                    <table class="w-full text-left text-xs text-gray-600 dark:text-gray-300">
                        <thead class="bg-gray-50 dark:bg-gray-900 text-[10px] font-black text-gray-500 uppercase tracking-wider border-b border-gray-100 dark:border-gray-700">
                            <tr>
                                <th class="py-3 px-3.5">{{ __('Order #') }}</th>
                                <th class="py-3 px-3.5">{{ __('Credits') }}</th>
                                <th class="py-3 px-3.5">{{ __('Amount') }}</th>
                                <th class="py-3 px-3.5">{{ __('Status') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60 font-medium">
                            @forelse($orders as $ord)
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/20">
                                    <td class="py-3 px-3.5 font-mono font-bold text-gray-900 dark:text-gray-100">
                                        #{{ $ord->id }}
                                        <div class="text-[10px] text-gray-400 font-sans font-normal">{{ $ord->created_at->format('d M Y') }}</div>
                                    </td>
                                    <td class="py-3 px-3.5">
                                        <span class="font-black text-indigo-600 dark:text-indigo-400">+{{ number_format($ord->total_credited) }}</span>
                                        @if($ord->bonus_credits > 0)
                                            <div class="text-[10px] text-emerald-600 font-bold">+{{ $ord->bonus_credits }} bonus</div>
                                        @endif
                                    </td>
                                    <td class="py-3 px-3.5 font-mono font-black text-gray-900 dark:text-gray-100">
                                        ₹{{ number_format($ord->total_amount, 2) }}
                                    </td>
                                    <td class="py-3 px-3.5">
                                        @if($ord->status === 'approved')
                                            <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">
                                                {{ __('Approved') }}
                                            </span>
                                        @elseif($ord->status === 'pending')
                                            <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300">
                                                {{ __('Pending') }}
                                            </span>
                                        @else
                                            <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300">
                                                {{ ucfirst($ord->status) }}
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-8 text-gray-400">
                                        {{ __('No recharge orders placed yet.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($orders instanceof \Illuminate\Pagination\LengthAwarePaginator)
                    <div>{{ $orders->links() }}</div>
                @endif
            </div>

            <!-- Usage Ledger -->
            <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 sm:p-7 border border-gray-100 dark:border-gray-700 shadow-xl shadow-gray-200/40 dark:shadow-none space-y-4">
                <h3 class="text-sm font-black text-gray-900 dark:text-gray-100 uppercase tracking-wider">{{ __('Recent Wallet Activity') }}</h3>

                <div class="overflow-x-auto rounded-2xl border border-gray-100 dark:border-gray-700">
                    <table class="w-full text-left text-xs text-gray-600 dark:text-gray-300">
                        <thead class="bg-gray-50 dark:bg-gray-900 text-[10px] font-black text-gray-500 uppercase tracking-wider border-b border-gray-100 dark:border-gray-700">
                            <tr>
                                <th class="py-3 px-3.5">{{ __('Date') }}</th>
                                <th class="py-3 px-3.5">{{ __('Description') }}</th>
                                <th class="py-3 px-3.5">{{ __('Credits') }}</th>
                                <th class="py-3 px-3.5">{{ __('Balance') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60 font-medium">
                            @forelse($transactions as $trx)
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/20">
                                    <td class="py-3 px-3.5 font-mono text-[10px] text-gray-400">
                                        {{ $trx->created_at->format('d M, h:i A') }}
                                    </td>
                                    <td class="py-3 px-3.5 text-gray-800 dark:text-gray-200 truncate max-w-xs font-medium" title="{{ $trx->description }}">
                                        {{ $trx->description }}
                                    </td>
                                    <td class="py-3 px-3.5 font-mono font-black {{ $trx->credits >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                                        {{ $trx->credits > 0 ? '+' : '' }}{{ number_format($trx->credits) }}
                                    </td>
                                    <td class="py-3 px-3.5 font-mono font-black text-gray-900 dark:text-gray-100">
                                        {{ number_format($trx->balance_after) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-8 text-gray-400">
                                        {{ __('No wallet transactions recorded yet.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($transactions instanceof \Illuminate\Pagination\LengthAwarePaginator)
                    <div>{{ $transactions->links() }}</div>
                @endif
            </div>
        </div>
    @endif

    <!-- =================== SUCCESS MODAL =================== -->
    @if($showSuccessModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/80 backdrop-blur-sm" x-transition.opacity>
            <div class="bg-white dark:bg-gray-800 rounded-3xl p-8 max-w-md w-full shadow-2xl border border-gray-100 dark:border-gray-700 text-center space-y-4">
                <div class="w-16 h-16 bg-emerald-100 dark:bg-emerald-950/80 text-emerald-600 dark:text-emerald-400 rounded-full flex items-center justify-center mx-auto text-2xl font-bold">
                    ✓
                </div>
                <h3 class="text-xl font-black text-gray-900 dark:text-gray-100">{{ __('Recharge Request Submitted!') }}</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 leading-relaxed">
                    {{ __('Your order #:id for :credits credits has been sent to the SaaS administration team for review and wallet crediting.', [
                        'id' => $lastSubmittedOrderId,
                        'credits' => number_format($calc['total_credits'])
                    ]) }}
                </p>
                <div class="pt-3">
                    <button wire:click="$set('showSuccessModal', false)" 
                            class="px-8 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-black uppercase tracking-wider shadow-lg">
                        {{ __('Got It, Thank You') }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
</div>
