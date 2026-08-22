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
    public string $paymentMethod = 'bank_transfer'; // bank_transfer, razorpay, offline_request
    public string $orderNotes = '';
    public bool $showSuccessModal = false;
    public ?int $lastSubmittedOrderId = null;

    // Toast
    public string $toastMessage = '';
    public string $toastType = 'success';

    public function setPreset(int $amount): void
    {
        $this->requestedCards = $amount;
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
        return CreditPlan::calculateForQuantity($this->requestedCards);
    }

    public function with(): array
    {
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
             class="p-4 rounded-2xl flex items-center justify-between shadow-lg transition-all 
             {{ $toastType === 'success' ? 'bg-emerald-600 text-white' : ($toastType === 'warning' ? 'bg-amber-500 text-white' : 'bg-indigo-600 text-white') }}">
            <div class="flex items-center gap-3">
                <svg class="w-6 h-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="font-medium text-sm">{{ $toastMessage }}</span>
            </div>
            <button @click="show = false" class="text-white/80 hover:text-white text-sm font-bold">✕</button>
        </div>
    @endif

    @if(!$school)
        <div class="bg-amber-50 dark:bg-amber-950/60 p-6 rounded-3xl border border-amber-200 dark:border-amber-800 text-center space-y-2">
            <h3 class="text-lg font-bold text-amber-900 dark:text-amber-200">{{ __('No Active School Workspace Selected') }}</h3>
            <p class="text-xs text-amber-700 dark:text-amber-400">{{ __('Please select a school from your dashboard or profile to view wallet balances and purchase credits.') }}</p>
        </div>
    @else
        <!-- Header & Balance Hero Card -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
            <!-- Balance Card -->
            <div class="lg:col-span-1 bg-gradient-to-br from-indigo-900 via-indigo-800 to-indigo-950 text-white rounded-3xl p-6 shadow-xl relative overflow-hidden flex flex-col justify-between">
                <div class="absolute -right-6 -bottom-6 w-36 h-36 bg-white/10 rounded-full blur-xl pointer-events-none"></div>

                <div>
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-indigo-200">{{ __('Available Credit Balance') }}</span>
                        @if($school->credits_balance >= 500)
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                🟢 {{ __('Healthy') }}
                            </span>
                        @elseif($school->credits_balance >= 100)
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">
                                🟡 {{ __('Running Low') }}
                            </span>
                        @else
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/20 text-rose-300 border border-rose-500/30">
                                🔴 {{ __('Critical — Top-up') }}
                            </span>
                        @endif
                    </div>

                    <div class="mt-4">
                        <span class="text-4xl sm:text-5xl font-black tracking-tight font-mono">
                            {{ number_format($school->credits_balance) }}
                        </span>
                        <span class="text-sm font-medium text-indigo-200 ml-1">cards</span>
                    </div>

                    <p class="text-xs text-indigo-200 mt-2">
                        {{ __('1 Credit = 1 High-Resolution Student ID Card with single or imposition sheet print.') }}
                    </p>
                </div>

                <div class="mt-6 pt-4 border-t border-indigo-700/60 flex items-center justify-between text-[11px] text-indigo-200">
                    <span class="flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        <span>{{ __('Credits Never Expire') }}</span>
                    </span>
                    <span class="font-bold text-white">{{ $school->name }}</span>
                </div>
            </div>

            <!-- Value Props & Highlights -->
            <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-3xl p-6 border border-gray-100 dark:border-gray-700 shadow-sm flex flex-col justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="px-2.5 py-1 text-xs font-bold uppercase rounded-lg bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">
                            🎁 {{ __('Volume Discounts & Free Bonus') }}
                        </span>
                    </div>
                    <h2 class="text-xl font-black text-gray-900 dark:text-gray-100 tracking-tight mt-2">
                        {{ __('Flexible ID Card Generation Packs') }}
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        {{ __('Choose any custom number of cards or pick a preset. Higher quantities automatically unlock cheaper rates and up to +30% FREE bonus cards.') }}
                    </p>
                </div>

                <!-- 3 Feature Chips -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                    <div class="p-3 rounded-2xl bg-gray-50 dark:bg-gray-900/60">
                        <div class="font-bold text-xs text-gray-900 dark:text-gray-100">🖨️ {{ __('All Formats Included') }}</div>
                        <div class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">{{ __('Single PDF, Imposition 10-Up, & PNG Zip') }}</div>
                    </div>
                    <div class="p-3 rounded-2xl bg-gray-50 dark:bg-gray-900/60">
                        <div class="font-bold text-xs text-gray-900 dark:text-gray-100">⚡ {{ __('Instant Deduction') }}</div>
                        <div class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">{{ __('Deducts only on final export creation') }}</div>
                    </div>
                    <div class="p-3 rounded-2xl bg-gray-50 dark:bg-gray-900/60">
                        <div class="font-bold text-xs text-gray-900 dark:text-gray-100">🔒 {{ __('Zero Hidden Charges') }}</div>
                        <div class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">{{ __('Includes all template engine features') }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- =================== RECHARGE CALCULATOR & TIER TILES =================== -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Left: Dynamic Live Calculator (7 cols) -->
            <div class="lg:col-span-7 bg-white dark:bg-gray-800 rounded-3xl p-6 border border-gray-100 dark:border-gray-700 shadow-sm space-y-5">
                <div class="border-b border-gray-100 dark:border-gray-700 pb-3">
                    <h3 class="text-base font-black text-gray-900 dark:text-gray-100 tracking-tight">
                        {{ __('Calculate & Purchase Credits') }}
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ __('Type your required quantity or drag the slider. Price & free bonus update live.') }}
                    </p>
                </div>

                <!-- Quantity Slider & Input -->
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <label class="font-bold text-xs uppercase text-gray-700 dark:text-gray-300">{{ __('Number of Student ID Cards') }}</label>
                        <div class="flex items-center gap-2">
                            <input type="number" wire:model.live.debounce.150ms="requestedCards" min="100" max="50000" step="50"
                                   class="w-32 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl px-3 py-1.5 text-right font-mono font-black text-lg text-indigo-600 dark:text-indigo-400 focus:ring-2 focus:ring-indigo-500" />
                            <span class="text-xs font-bold text-gray-400">cards</span>
                        </div>
                    </div>

                    <!-- Range Slider -->
                    <input type="range" wire:model.live="requestedCards" min="100" max="10000" step="50" 
                           class="w-full h-2.5 bg-gray-200 dark:bg-gray-700 rounded-lg appearance-none cursor-pointer accent-indigo-600" />

                    <!-- Quick Preset Buttons -->
                    <div class="flex flex-wrap items-center gap-2 pt-1">
                        <span class="text-xs font-bold text-gray-400 mr-1">{{ __('Quick Select:') }}</span>
                        @foreach([500, 1000, 2500, 5000, 10000] as $preset)
                            <button type="button" wire:click="setPreset({{ $preset }})" 
                                    class="px-3 py-1 rounded-xl text-xs font-bold font-mono transition 
                                    {{ $requestedCards == $preset ? 'bg-indigo-600 text-white shadow-md' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200' }}">
                                {{ number_format($preset) }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <!-- Live Price Breakdown Panel -->
                <div class="bg-gray-50 dark:bg-gray-900/70 rounded-2xl p-5 border border-gray-100 dark:border-gray-700 space-y-3">
                    <div class="flex items-center justify-between text-xs text-gray-600 dark:text-gray-300">
                        <span>{{ __('Base Cards Ordered:') }}</span>
                        <span class="font-mono font-bold">{{ number_format($calc['quantity']) }}</span>
                    </div>

                    <div class="flex items-center justify-between text-xs text-gray-600 dark:text-gray-300">
                        <span>{{ __('Rate Applied:') }}</span>
                        <span class="font-mono font-bold text-gray-900 dark:text-gray-100">
                            ₹{{ number_format($calc['rate'], 2) }} / card
                            <span class="text-[10px] text-indigo-600 font-sans font-semibold ml-1">({{ $calc['plan_name'] }})</span>
                        </span>
                    </div>

                    @if($calc['bonus_credits'] > 0)
                        <div class="flex items-center justify-between text-xs bg-emerald-50 dark:bg-emerald-950/60 p-2.5 rounded-xl border border-emerald-200/60 dark:border-emerald-800/60">
                            <span class="font-bold text-emerald-800 dark:text-emerald-300 flex items-center gap-1.5">
                                🎁 {{ __('Volume Bonus (+:pct% Free):', ['pct' => $calc['bonus_percentage']]) }}
                            </span>
                            <span class="font-mono font-black text-emerald-700 dark:text-emerald-300 text-sm">
                                +{{ number_format($calc['bonus_credits']) }} FREE CARDS
                            </span>
                        </div>
                    @endif

                    <div class="flex items-center justify-between text-xs text-gray-600 dark:text-gray-300">
                        <span class="font-bold text-gray-900 dark:text-gray-100">{{ __('Total Cards You Will Receive:') }}</span>
                        <span class="font-mono font-black text-indigo-600 dark:text-indigo-400 text-base">
                            {{ number_format($calc['total_credits']) }} Cards
                        </span>
                    </div>

                    <div class="border-t border-gray-200 dark:border-gray-700 pt-3 flex items-center justify-between text-xs text-gray-500">
                        <span>{{ __('Effective Rate:') }}</span>
                        <span class="font-mono font-bold text-emerald-600">₹{{ number_format($calc['effective_rate'], 2) }} / card</span>
                    </div>

                    <div class="flex items-center justify-between text-xs text-gray-500">
                        <span>{{ __('Subtotal:') }}</span>
                        <span class="font-mono">₹{{ number_format($calc['subtotal'], 2) }}</span>
                    </div>

                    <div class="flex items-center justify-between text-xs text-gray-500">
                        <span>{{ __('GST (18%):') }}</span>
                        <span class="font-mono">₹{{ number_format($calc['gst'], 2) }}</span>
                    </div>

                    <div class="border-t-2 border-dashed border-gray-200 dark:border-gray-700 pt-3 flex items-center justify-between">
                        <span class="font-bold text-sm text-gray-900 dark:text-gray-100">{{ __('Total Payable Amount:') }}</span>
                        <span class="font-mono font-black text-2xl text-gray-900 dark:text-gray-100">
                            ₹{{ number_format($calc['total_amount'], 2) }}
                        </span>
                    </div>
                </div>

                <!-- Next Tier Upsell Nudge -->
                @if($calc['upsell_nudge'])
                    <div class="bg-indigo-50 dark:bg-indigo-950/60 p-3.5 rounded-2xl border border-indigo-200 dark:border-indigo-800 text-xs text-indigo-800 dark:text-indigo-300 flex items-center gap-2">
                        <span class="text-base shrink-0">💡</span>
                        <div>
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
                <div class="space-y-3 pt-2">
                    <div>
                        <label class="block font-bold text-xs text-gray-700 dark:text-gray-300 uppercase mb-1">{{ __('Payment Method') }}</label>
                        <select wire:model="paymentMethod" class="w-full bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl px-3 py-2 text-xs text-gray-900 dark:text-gray-100 font-medium">
                            <option value="bank_transfer">{{ __('Direct Bank Transfer / NEFT / IMPS / UPI') }}</option>
                            <option value="offline_request">{{ __('Submit Invoice Request (Pay Offline / PO)') }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-bold text-xs text-gray-700 dark:text-gray-300 uppercase mb-1">{{ __('UTR / Reference Number or Remarks (Optional)') }}</label>
                        <input type="text" wire:model="orderNotes" placeholder="{{ __('e.g. Paid via PhonePe UTR #12345678 or Invoice PO request') }}" 
                               class="w-full bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl px-3 py-2 text-xs text-gray-900 dark:text-gray-100" />
                    </div>

                    <button wire:click="submitRechargeRequest" 
                            class="w-full py-3 bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-700 hover:to-indigo-800 text-white font-black text-xs uppercase tracking-wider rounded-xl transition shadow-lg flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        <span>{{ __('Request Recharge (:credits Cards)', ['credits' => number_format($calc['total_credits'])]) }}</span>
                    </button>
                </div>
            </div>

            <!-- Right: Tier Slabs Reference (5 cols) -->
            <div class="lg:col-span-5 space-y-4">
                <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 border border-gray-100 dark:border-gray-700 shadow-sm space-y-4">
                    <h3 class="text-sm font-bold text-gray-800 dark:text-gray-200 uppercase tracking-wider">{{ __('Available Volume Slabs') }}</h3>
                    
                    <div class="space-y-3">
                        @foreach($plans as $plan)
                            <div class="p-3.5 rounded-2xl bg-gray-50 dark:bg-gray-900/60 border border-gray-100 dark:border-gray-700 flex items-center justify-between">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-xs text-gray-900 dark:text-gray-100">{{ $plan->name }}</span>
                                        @if($plan->badge_text)
                                            <span class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase {{ $plan->badge_color ?? 'bg-indigo-600 text-white' }}">
                                                {{ $plan->badge_text }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="text-[11px] text-gray-400 mt-0.5 font-mono">
                                        {{ number_format($plan->min_quantity) }} {{ $plan->max_quantity ? '– ' . number_format($plan->max_quantity) : '+' }} cards
                                    </div>
                                </div>

                                <div class="text-right">
                                    <div class="font-black text-sm text-gray-900 dark:text-gray-100">₹{{ number_format($plan->price_per_credit, 2) }}</div>
                                    <div class="text-[10px] font-bold text-emerald-600">+{{ $plan->bonus_percentage }}% Bonus</div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Bank Details Card -->
                    <div class="p-4 rounded-2xl bg-indigo-50/60 dark:bg-indigo-950/40 border border-indigo-100 dark:border-indigo-900/60 text-xs text-gray-700 dark:text-gray-300 space-y-1">
                        <div class="font-bold text-indigo-900 dark:text-indigo-200">🏦 {{ __('Direct Settlement Support') }}</div>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400">
                            {{ __('Need custom quotation, enterprise GST billing, or purchase order support? Contact platform administrator.') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- =================== ORDERS & USAGE HISTORY =================== -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Order History -->
            <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 border border-gray-100 dark:border-gray-700 shadow-sm space-y-4">
                <h3 class="text-sm font-bold text-gray-800 dark:text-gray-200 uppercase tracking-wider">{{ __('My Recharge Requests') }}</h3>

                <div class="overflow-x-auto rounded-2xl border border-gray-100 dark:border-gray-700">
                    <table class="w-full text-left text-xs text-gray-600 dark:text-gray-300">
                        <thead class="bg-gray-50 dark:bg-gray-900/50 text-[10px] font-bold text-gray-400 uppercase tracking-wider">
                            <tr>
                                <th class="py-2.5 px-3">{{ __('Order #') }}</th>
                                <th class="py-2.5 px-3">{{ __('Credits') }}</th>
                                <th class="py-2.5 px-3">{{ __('Amount') }}</th>
                                <th class="py-2.5 px-3">{{ __('Status') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                            @forelse($orders as $ord)
                                <tr>
                                    <td class="py-2.5 px-3 font-mono font-bold text-gray-900 dark:text-gray-100">
                                        #{{ $ord->id }}
                                        <div class="text-[10px] text-gray-400 font-sans font-normal">{{ $ord->created_at->format('d M Y') }}</div>
                                    </td>
                                    <td class="py-2.5 px-3">
                                        <span class="font-bold text-indigo-600">+{{ number_format($ord->total_credited) }}</span>
                                        @if($ord->bonus_credits > 0)
                                            <div class="text-[10px] text-emerald-600">+{{ $ord->bonus_credits }} bonus</div>
                                        @endif
                                    </td>
                                    <td class="py-2.5 px-3 font-mono font-bold">
                                        ₹{{ number_format($ord->total_amount, 2) }}
                                    </td>
                                    <td class="py-2.5 px-3">
                                        @if($ord->status === 'approved')
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700">
                                                {{ __('Approved') }}
                                            </span>
                                        @elseif($ord->status === 'pending')
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-700">
                                                {{ __('Pending') }}
                                            </span>
                                        @else
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-700">
                                                {{ ucfirst($ord->status) }}
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-6 text-gray-400">
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
            <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 border border-gray-100 dark:border-gray-700 shadow-sm space-y-4">
                <h3 class="text-sm font-bold text-gray-800 dark:text-gray-200 uppercase tracking-wider">{{ __('Recent Wallet Activity') }}</h3>

                <div class="overflow-x-auto rounded-2xl border border-gray-100 dark:border-gray-700">
                    <table class="w-full text-left text-xs text-gray-600 dark:text-gray-300">
                        <thead class="bg-gray-50 dark:bg-gray-900/50 text-[10px] font-bold text-gray-400 uppercase tracking-wider">
                            <tr>
                                <th class="py-2.5 px-3">{{ __('Date') }}</th>
                                <th class="py-2.5 px-3">{{ __('Description') }}</th>
                                <th class="py-2.5 px-3">{{ __('Credits') }}</th>
                                <th class="py-2.5 px-3">{{ __('Balance') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                            @forelse($transactions as $trx)
                                <tr>
                                    <td class="py-2.5 px-3 font-mono text-[10px] text-gray-400">
                                        {{ $trx->created_at->format('d M, h:i A') }}
                                    </td>
                                    <td class="py-2.5 px-3 text-gray-800 dark:text-gray-200 truncate max-w-xs" title="{{ $trx->description }}">
                                        {{ $trx->description }}
                                    </td>
                                    <td class="py-2.5 px-3 font-mono font-bold {{ $trx->credits >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                        {{ $trx->credits > 0 ? '+' : '' }}{{ number_format($trx->credits) }}
                                    </td>
                                    <td class="py-2.5 px-3 font-mono font-bold text-gray-900 dark:text-gray-100">
                                        {{ number_format($trx->balance_after) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-6 text-gray-400">
                                        {{ __('No transactions found.') }}
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
            <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 max-w-md w-full shadow-2xl border border-gray-100 dark:border-gray-700 text-center space-y-4">
                <div class="w-16 h-16 bg-emerald-100 dark:bg-emerald-950/80 text-emerald-600 dark:text-emerald-400 rounded-full flex items-center justify-center mx-auto text-2xl font-bold">
                    ✓
                </div>
                <h3 class="text-lg font-black text-gray-900 dark:text-gray-100">{{ __('Recharge Request Submitted!') }}</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    {{ __('Your order #:id for :credits credits has been sent to the SaaS administration team for review and wallet crediting.', [
                        'id' => $lastSubmittedOrderId,
                        'credits' => number_format($calc['total_credits'])
                    ]) }}
                </p>
                <div class="pt-2">
                    <button wire:click="$set('showSuccessModal', false)" 
                            class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold uppercase tracking-wider shadow">
                        {{ __('Got It, Thank You') }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
