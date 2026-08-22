<?php

use App\Models\CreditOrder;
use App\Models\CreditPlan;
use App\Models\CreditTransaction;
use App\Models\School;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    // Filters
    public string $activeTab = 'orders'; // 'orders', 'plans', 'transactions', 'schools'
    public string $orderStatusFilter = '';
    public string $searchSchool = '';
    public string $transactionTypeFilter = '';

    // Manual Grant / Adjustment Modal
    public bool $showGrantModal = false;
    public ?int $selectedSchoolId = null;
    public int $grantCredits = 500;
    public string $grantType = 'admin_adjustment'; // admin_adjustment, demo_grant, payment_settlement
    public string $grantNote = '';
    public string $grantAction = 'add'; // 'add' or 'deduct'

    // Plan Edit/Create Modal
    public bool $showPlanModal = false;
    public ?int $editingPlanId = null;
    public string $planName = '';
    public int $planMinQuantity = 1;
    public ?int $planMaxQuantity = null;
    public float $planPricePerCredit = 10.00;
    public int $planBonusPercentage = 0;
    public bool $planIsActive = true;
    public int $planSortOrder = 0;
    public string $planBadgeText = '';

    // Toast message
    public string $toastMessage = '';
    public string $toastType = 'success';

    public function with(): array
    {
        // 1. KPI Aggregations
        $totalCreditsInCirculation = School::sum('credits_balance');
        $totalOrdersCount = CreditOrder::count();
        $totalPaidOrders = CreditOrder::where('status', 'approved')->get();
        $totalRevenue = $totalPaidOrders->sum('total_amount');
        $totalCreditsGranted = $totalPaidOrders->sum('total_credited');
        $totalExportsDeductions = abs(CreditTransaction::where('type', 'export_deduction')->sum('credits'));
        $pendingOrdersCount = CreditOrder::where('status', 'pending')->count();
        $schoolsCount = School::count();

        // 2. Query Orders
        $ordersQuery = CreditOrder::with(['school', 'user', 'approvedByUser'])
            ->when($this->orderStatusFilter, fn($q) => $q->where('status', $this->orderStatusFilter))
            ->when($this->searchSchool, function ($q) {
                $q->whereHas('school', fn($sq) => $sq->where('name', 'like', "%{$this->searchSchool}%"));
            })
            ->latest();

        // 3. Query Transactions Ledger
        $transactionsQuery = CreditTransaction::with(['school', 'performedByUser'])
            ->when($this->transactionTypeFilter, fn($q) => $q->where('type', $this->transactionTypeFilter))
            ->when($this->searchSchool, function ($q) {
                $q->whereHas('school', fn($sq) => $sq->where('name', 'like', "%{$this->searchSchool}%"));
            })
            ->latest();

        // 4. Query Plans
        $plans = CreditPlan::orderBy('sort_order', 'asc')->get();

        // 5. Query Schools with Balance
        $schoolsWithBalance = School::withCount('campaigns')
            ->when($this->searchSchool, fn($q) => $q->where('name', 'like', "%{$this->searchSchool}%"))
            ->orderBy('credits_balance', 'desc')
            ->paginate(10, ['*'], 'schoolsPage');

        $allSchools = School::orderBy('name', 'asc')->get(['id', 'name', 'credits_balance']);

        return [
            'totalCreditsInCirculation' => $totalCreditsInCirculation,
            'totalOrdersCount' => $totalOrdersCount,
            'totalRevenue' => $totalRevenue,
            'totalCreditsGranted' => $totalCreditsGranted,
            'totalExportsDeductions' => $totalExportsDeductions,
            'pendingOrdersCount' => $pendingOrdersCount,
            'schoolsCount' => $schoolsCount,
            'orders' => $ordersQuery->paginate(10, ['*'], 'ordersPage'),
            'transactions' => $transactionsQuery->paginate(15, ['*'], 'transactionsPage'),
            'plans' => $plans,
            'schoolsWithBalance' => $schoolsWithBalance,
            'allSchools' => $allSchools,
        ];
    }

    public function openGrantModal(?int $schoolId = null): void
    {
        $this->selectedSchoolId = $schoolId ?? School::first()?->id;
        $this->grantCredits = 500;
        $this->grantType = 'admin_adjustment';
        $this->grantNote = '';
        $this->grantAction = 'add';
        $this->showGrantModal = true;
    }

    public function submitManualGrant(): void
    {
        $this->validate([
            'selectedSchoolId' => 'required|exists:schools,id',
            'grantCredits' => 'required|integer|min:1|max:100000',
            'grantNote' => 'nullable|string|max:255',
        ]);

        $school = School::findOrFail($this->selectedSchoolId);
        $amount = (int) $this->grantCredits;
        $admin = auth()->user();

        if ($this->grantAction === 'add') {
            $note = $this->grantNote ?: 'Manual Credit Grant by Admin';
            $school->addCredits(
                $amount,
                $this->grantType,
                $note,
                null,
                $admin
            );

            // Also create an approved order record for bookkeeping
            CreditOrder::create([
                'school_id' => $school->id,
                'user_id' => $admin->id,
                'ordered_credits' => $amount,
                'bonus_credits' => 0,
                'total_credited' => $amount,
                'price_per_credit' => 0,
                'subtotal' => 0,
                'gst_amount' => 0,
                'total_amount' => 0,
                'payment_method' => 'admin_grant',
                'payment_reference' => 'ADMIN-GRANT-' . strtoupper(uniqid()),
                'status' => 'approved',
                'approved_by' => $admin->id,
                'approved_at' => now(),
                'notes' => $note,
            ]);

            $this->toast("Successfully granted {$amount} credits to {$school->name}!", 'success');
        } else {
            // Deduct
            $note = $this->grantNote ?: 'Manual Credit Adjustment by Admin';
            $school->deductCredits(
                $amount,
                $note,
                null,
                $admin
            );
            $this->toast("Deducted {$amount} credits from {$school->name}!", 'info');
        }

        $this->showGrantModal = false;
    }

    public function approveOrder(int $orderId): void
    {
        $order = CreditOrder::with('school')->findOrFail($orderId);
        if ($order->status === 'approved') {
            $this->toast('Order is already approved.', 'warning');
            return;
        }

        $admin = auth()->user();
        $order->update([
            'status' => 'approved',
            'approved_by' => $admin->id,
            'approved_at' => now(),
        ]);

        // Add credits to school wallet
        $order->school->addCredits(
            $order->total_credited,
            'recharge',
            "Recharge Pack: {$order->ordered_credits} cards + {$order->bonus_credits} bonus (Order #{$order->id})",
            $order,
            $admin
        );

        $this->toast("Order #{$order->id} approved! Added {$order->total_credited} credits to {$order->school->name}.", 'success');
    }

    public function rejectOrder(int $orderId): void
    {
        $order = CreditOrder::findOrFail($orderId);
        $order->update(['status' => 'rejected']);
        $this->toast("Order #{$orderId} has been marked as rejected.", 'info');
    }

    public function openNewPlanModal(): void
    {
        $this->editingPlanId = null;
        $this->planName = '';
        $this->planMinQuantity = 100;
        $this->planMaxQuantity = null;
        $this->planPricePerCredit = 10.00;
        $this->planBonusPercentage = 0;
        $this->planIsActive = true;
        $this->planSortOrder = (CreditPlan::max('sort_order') ?? 0) + 1;
        $this->planBadgeText = '';
        $this->showPlanModal = true;
    }

    public function editPlan(int $planId): void
    {
        $plan = CreditPlan::findOrFail($planId);
        $this->editingPlanId = $plan->id;
        $this->planName = $plan->name;
        $this->planMinQuantity = $plan->min_quantity;
        $this->planMaxQuantity = $plan->max_quantity;
        $this->planPricePerCredit = (float) $plan->price_per_credit;
        $this->planBonusPercentage = (int) $plan->bonus_percentage;
        $this->planIsActive = (bool) $plan->is_active;
        $this->planSortOrder = (int) $plan->sort_order;
        $this->planBadgeText = (string) $plan->badge_text;
        $this->showPlanModal = true;
    }

    public function savePlan(): void
    {
        $this->validate([
            'planName' => 'required|string|max:100',
            'planMinQuantity' => 'required|integer|min:1',
            'planMaxQuantity' => 'nullable|integer|gte:planMinQuantity',
            'planPricePerCredit' => 'required|numeric|min:0.5',
            'planBonusPercentage' => 'required|integer|min:0|max:100',
            'planSortOrder' => 'required|integer|min:0',
        ]);

        CreditPlan::updateOrCreate(
            ['id' => $this->editingPlanId],
            [
                'name' => $this->planName,
                'min_quantity' => $this->planMinQuantity,
                'max_quantity' => $this->planMaxQuantity,
                'price_per_credit' => $this->planPricePerCredit,
                'bonus_percentage' => $this->planBonusPercentage,
                'is_active' => $this->planIsActive,
                'sort_order' => $this->planSortOrder,
                'badge_text' => $this->planBadgeText ?: null,
            ]
        );

        $this->toast($this->editingPlanId ? 'Plan updated successfully.' : 'New plan created successfully.', 'success');
        $this->showPlanModal = false;
    }

    public function togglePlanStatus(int $planId): void
    {
        $plan = CreditPlan::findOrFail($planId);
        $plan->update(['is_active' => !$plan->is_active]);
        $this->toast("Plan '{$plan->name}' " . ($plan->is_active ? 'activated' : 'deactivated') . '.', 'info');
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

    <!-- Header Actions & Headline -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white dark:bg-gray-800 p-6 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-1 text-xs font-bold uppercase rounded-lg bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800">
                    {{ __('SaaS Billing Engine') }}
                </span>
                @if($pendingOrdersCount > 0)
                    <span class="px-2.5 py-1 text-xs font-bold rounded-lg bg-amber-100 dark:bg-amber-950/80 text-amber-700 dark:text-amber-400 animate-pulse">
                        ⚡ {{ $pendingOrdersCount }} {{ __('Pending Approval') }}
                    </span>
                @endif
            </div>
            <h1 class="text-2xl font-black text-gray-900 dark:text-gray-100 tracking-tight mt-1">
                {{ __('Credits & Revenue Command Center') }}
            </h1>
            <p class="text-xs text-gray-500 dark:text-gray-400">
                {{ __('Manage school wallet balances, configure volume bonus slabs, process recharge requests, and audit ledger.') }}
            </p>
        </div>

        <div class="flex items-center gap-3">
            <button wire:click="openGrantModal" 
                    class="px-4 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-bold text-xs uppercase tracking-wider rounded-xl transition shadow flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                <span>{{ __('Grant / Adjust Credits') }}</span>
            </button>
            <button wire:click="openNewPlanModal" 
                    class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs uppercase tracking-wider rounded-xl transition shadow flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l4-2 4 2 4-2 4 2z" />
                </svg>
                <span>{{ __('Add Pricing Slab') }}</span>
            </button>
        </div>
    </div>

    <!-- 4 Primary KPI Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <!-- Revenue Card -->
        <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 shadow-sm border border-gray-100 dark:border-gray-700 relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Total Revenue') }}</span>
                <div class="w-10 h-10 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-bold">
                    ₹
                </div>
            </div>
            <div class="mt-4">
                <span class="text-3xl font-black text-gray-900 dark:text-gray-100 tracking-tight">₹{{ number_format($totalRevenue, 2) }}</span>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('From paid & approved recharge orders') }}</p>
            </div>
        </div>

        <!-- Credits in Circulation -->
        <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 shadow-sm border border-gray-100 dark:border-gray-700 relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Active Wallet Balance') }}</span>
                <div class="w-10 h-10 rounded-2xl bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                    </svg>
                </div>
            </div>
            <div class="mt-4">
                <span class="text-3xl font-black text-indigo-600 dark:text-indigo-400 tracking-tight">{{ number_format($totalCreditsInCirculation) }}</span>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('Credits available across :count schools', ['count' => $schoolsCount]) }}</p>
            </div>
        </div>

        <!-- Total Cards Exported / Deducted -->
        <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 shadow-sm border border-gray-100 dark:border-gray-700 relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Cards Exported (Burned)') }}</span>
                <div class="w-10 h-10 rounded-2xl bg-purple-50 dark:bg-purple-950/40 text-purple-600 dark:text-purple-400 flex items-center justify-center font-bold">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                </div>
            </div>
            <div class="mt-4">
                <span class="text-3xl font-black text-gray-900 dark:text-gray-100 tracking-tight">{{ number_format($totalExportsDeductions) }}</span>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('PDF/Imposition cards exported') }}</p>
            </div>
        </div>

        <!-- Pending Orders -->
        <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 shadow-sm border border-gray-100 dark:border-gray-700 relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Pending Orders') }}</span>
                <div class="w-10 h-10 rounded-2xl bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 flex items-center justify-center font-bold">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <div class="mt-4 flex items-baseline gap-2">
                <span class="text-3xl font-black {{ $pendingOrdersCount > 0 ? 'text-amber-600' : 'text-gray-900 dark:text-gray-100' }} tracking-tight">{{ $pendingOrdersCount }}</span>
                <span class="text-xs text-gray-500 dark:text-gray-400">/ {{ $totalOrdersCount }} {{ __('total') }}</span>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('Awaiting admin approval & top-up') }}</p>
        </div>
    </div>

    <!-- Tab Navigation & Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 shadow-sm border border-gray-100 dark:border-gray-700 space-y-6">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-gray-100 dark:border-gray-700 pb-4">
            <!-- Tabs -->
            <div class="flex flex-wrap items-center gap-2">
                <button wire:click="$set('activeTab', 'orders')" 
                        class="px-4 py-2 rounded-xl text-xs font-bold uppercase tracking-wider transition flex items-center gap-2 
                        {{ $activeTab === 'orders' ? 'bg-indigo-600 text-white shadow-md' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    <span>{{ __('Recharge Orders') }}</span>
                    @if($pendingOrdersCount > 0)
                        <span class="px-1.5 py-0.5 rounded-full bg-amber-400 text-gray-900 text-[10px] font-black">{{ $pendingOrdersCount }}</span>
                    @endif
                </button>

                <button wire:click="$set('activeTab', 'plans')" 
                        class="px-4 py-2 rounded-xl text-xs font-bold uppercase tracking-wider transition flex items-center gap-2 
                        {{ $activeTab === 'plans' ? 'bg-indigo-600 text-white shadow-md' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                    </svg>
                    <span>{{ __('Pricing Slabs & Bonuses') }} ({{ count($plans) }})</span>
                </button>

                <button wire:click="$set('activeTab', 'schools')" 
                        class="px-4 py-2 rounded-xl text-xs font-bold uppercase tracking-wider transition flex items-center gap-2 
                        {{ $activeTab === 'schools' ? 'bg-indigo-600 text-white shadow-md' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    <span>{{ __('School Wallets') }}</span>
                </button>

                <button wire:click="$set('activeTab', 'transactions')" 
                        class="px-4 py-2 rounded-xl text-xs font-bold uppercase tracking-wider transition flex items-center gap-2 
                        {{ $activeTab === 'transactions' ? 'bg-indigo-600 text-white shadow-md' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span>{{ __('Transaction Audit Ledger') }}</span>
                </button>
            </div>

            <!-- Search filter -->
            <div class="flex items-center gap-3">
                <div class="relative w-full sm:w-64">
                    <input type="text" wire:model.live.debounce.300ms="searchSchool" 
                           placeholder="{{ __('Search school...') }}" 
                           class="w-full pl-9 pr-4 py-2 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500" />
                    <svg class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- =================== TAB 1: RECHARGE ORDERS =================== -->
        @if($activeTab === 'orders')
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-bold text-gray-800 dark:text-gray-200 uppercase tracking-wider">{{ __('All Credit Purchase & Top-up Orders') }}</h3>
                    <div class="flex items-center gap-2">
                        <select wire:model.live="orderStatusFilter" class="text-xs bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl px-3 py-1.5 text-gray-700 dark:text-gray-300">
                            <option value="">{{ __('All Statuses') }}</option>
                            <option value="pending">{{ __('Pending Approval') }}</option>
                            <option value="approved">{{ __('Approved / Paid') }}</option>
                            <option value="rejected">{{ __('Rejected') }}</option>
                        </select>
                    </div>
                </div>

                <div class="overflow-x-auto rounded-2xl border border-gray-100 dark:border-gray-700">
                    <table class="w-full text-left text-xs text-gray-600 dark:text-gray-300">
                        <thead class="bg-gray-50 dark:bg-gray-900/50 text-[11px] font-bold text-gray-400 uppercase tracking-wider border-b border-gray-100 dark:border-gray-700">
                            <tr>
                                <th class="py-3.5 px-4">{{ __('Order #') }}</th>
                                <th class="py-3.5 px-4">{{ __('School') }}</th>
                                <th class="py-3.5 px-4">{{ __('Ordered + Bonus') }}</th>
                                <th class="py-3.5 px-4">{{ __('Total Credits') }}</th>
                                <th class="py-3.5 px-4">{{ __('Amount (₹)') }}</th>
                                <th class="py-3.5 px-4">{{ __('Method / Ref') }}</th>
                                <th class="py-3.5 px-4">{{ __('Status') }}</th>
                                <th class="py-3.5 px-4 text-right">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60 font-medium">
                            @forelse($orders as $order)
                                <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-700/30 transition">
                                    <td class="py-3.5 px-4 font-mono font-bold text-gray-900 dark:text-gray-100">
                                        #{{ $order->id }}
                                        <div class="text-[10px] text-gray-400 font-sans font-normal">{{ $order->created_at->format('d M Y, h:i A') }}</div>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <div class="font-bold text-gray-900 dark:text-gray-100">{{ $order->school->name }}</div>
                                        <div class="text-[10px] text-gray-400">{{ __('By:') }} {{ $order->user?->name ?? 'System/Admin' }}</div>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <span>{{ number_format($order->ordered_credits) }}</span>
                                        @if($order->bonus_credits > 0)
                                            <span class="text-emerald-600 font-bold text-[11px] ml-1">+{{ number_format($order->bonus_credits) }} Free</span>
                                        @endif
                                        <div class="text-[10px] text-gray-400">@ ₹{{ $order->price_per_credit }}/card</div>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <span class="px-2.5 py-1 rounded-lg bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 font-black text-xs">
                                            +{{ number_format($order->total_credited) }}
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 font-bold text-gray-900 dark:text-gray-100">
                                        ₹{{ number_format($order->total_amount, 2) }}
                                        @if($order->gst_amount > 0)
                                            <div class="text-[10px] text-gray-400 font-normal">{{ __('incl. ₹:gst GST', ['gst' => $order->gst_amount]) }}</div>
                                        @endif
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <span class="px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 uppercase text-[10px] font-bold">
                                            {{ str_replace('_', ' ', $order->payment_method) }}
                                        </span>
                                        @if($order->notes)
                                            <div class="text-[10px] text-gray-400 truncate max-w-xs" title="{{ $order->notes }}">{{ $order->notes }}</div>
                                        @endif
                                    </td>
                                    <td class="py-3.5 px-4">
                                        @if($order->status === 'approved')
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-100 dark:bg-emerald-950/80 text-emerald-700 dark:text-emerald-300">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                {{ __('Approved') }}
                                            </span>
                                        @elseif($order->status === 'pending')
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-100 dark:bg-amber-950/80 text-amber-700 dark:text-amber-300">
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-ping"></span>
                                                {{ __('Pending') }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-rose-100 dark:bg-rose-950/80 text-rose-700 dark:text-rose-300">
                                                {{ ucfirst($order->status) }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-3.5 px-4 text-right">
                                        @if($order->status === 'pending')
                                            <div class="flex items-center justify-end gap-2">
                                                <button wire:click="approveOrder({{ $order->id }})" 
                                                        class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold transition shadow-sm">
                                                    {{ __('Approve & Credit') }}
                                                </button>
                                                <button wire:click="rejectOrder({{ $order->id }})" 
                                                        class="px-2 py-1 bg-gray-100 dark:bg-gray-700 hover:bg-rose-100 hover:text-rose-700 text-gray-600 dark:text-gray-300 rounded-lg text-xs font-bold transition">
                                                    {{ __('Reject') }}
                                                </button>
                                            </div>
                                        @else
                                            <span class="text-[11px] text-gray-400">
                                                {{ $order->approvedByUser ? 'Approved by ' . $order->approvedByUser->name : 'Completed' }}
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-8 text-gray-400">
                                        {{ __('No recharge orders found matching current filters.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div>
                    {{ $orders->links() }}
                </div>
            </div>
        @endif

        <!-- =================== TAB 2: PRICING SLABS & BONUSES =================== -->
        @if($activeTab === 'plans')
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-gray-800 dark:text-gray-200 uppercase tracking-wider">{{ __('Dynamic Volume Pricing Slabs') }}</h3>
                        <p class="text-xs text-gray-400">{{ __('When a school selects card count, the system automatically applies cheaper rates and awards free bonus cards.') }}</p>
                    </div>
                    <button wire:click="openNewPlanModal" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold flex items-center gap-1.5 transition">
                        <span>+ {{ __('Add New Tier') }}</span>
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4">
                    @foreach($plans as $plan)
                        <div class="bg-gray-50 dark:bg-gray-900/60 rounded-2xl p-5 border {{ $plan->is_active ? 'border-gray-200 dark:border-gray-700' : 'border-gray-200/40 opacity-60' }} flex flex-col justify-between relative group hover:border-indigo-500 transition">
                            @if($plan->badge_text)
                                <div class="absolute -top-2.5 right-3 px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider {{ $plan->badge_color ?? 'bg-indigo-600 text-white' }} shadow-sm">
                                    {{ $plan->badge_text }}
                                </div>
                            @endif

                            <div>
                                <div class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ $plan->name }}</div>
                                <div class="mt-2 flex items-baseline gap-1">
                                    <span class="text-2xl font-black text-gray-900 dark:text-gray-100">₹{{ number_format($plan->price_per_credit, 2) }}</span>
                                    <span class="text-xs text-gray-400">/ card</span>
                                </div>

                                <div class="mt-3 space-y-1.5 text-xs text-gray-600 dark:text-gray-300">
                                    <div class="flex items-center justify-between">
                                        <span class="text-gray-400">{{ __('Quantity:') }}</span>
                                        <span class="font-bold font-mono">{{ number_format($plan->min_quantity) }} {{ $plan->max_quantity ? '– ' . number_format($plan->max_quantity) : '+' }}</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-gray-400">{{ __('Bonus Free:') }}</span>
                                        <span class="font-bold text-emerald-600 dark:text-emerald-400">+{{ $plan->bonus_percentage }}% Cards</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-gray-400">{{ __('Status:') }}</span>
                                        <span class="font-bold {{ $plan->is_active ? 'text-emerald-600' : 'text-gray-400' }}">{{ $plan->is_active ? 'Active' : 'Disabled' }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-5 pt-3 border-t border-gray-200/60 dark:border-gray-700/60 flex items-center justify-between">
                                <button wire:click="togglePlanStatus({{ $plan->id }})" class="text-[11px] font-bold text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
                                    {{ $plan->is_active ? 'Disable' : 'Enable' }}
                                </button>
                                <button wire:click="editPlan({{ $plan->id }})" class="text-[11px] font-bold text-indigo-600 hover:text-indigo-800 dark:text-indigo-400">
                                    {{ __('Edit Slab') }} →
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- =================== TAB 3: SCHOOL WALLETS =================== -->
        @if($activeTab === 'schools')
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-bold text-gray-800 dark:text-gray-200 uppercase tracking-wider">{{ __('School Wallet Balances') }}</h3>
                </div>

                <div class="overflow-x-auto rounded-2xl border border-gray-100 dark:border-gray-700">
                    <table class="w-full text-left text-xs text-gray-600 dark:text-gray-300">
                        <thead class="bg-gray-50 dark:bg-gray-900/50 text-[11px] font-bold text-gray-400 uppercase tracking-wider border-b border-gray-100 dark:border-gray-700">
                            <tr>
                                <th class="py-3.5 px-4">{{ __('School Name') }}</th>
                                <th class="py-3.5 px-4">{{ __('Contact / Email') }}</th>
                                <th class="py-3.5 px-4">{{ __('Current Credit Balance') }}</th>
                                <th class="py-3.5 px-4">{{ __('Status Health') }}</th>
                                <th class="py-3.5 px-4 text-right">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60 font-medium">
                            @forelse($schoolsWithBalance as $sch)
                                <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-700/30 transition">
                                    <td class="py-3.5 px-4">
                                        <div class="font-bold text-gray-900 dark:text-gray-100">{{ $sch->name }}</div>
                                        <div class="text-[10px] text-gray-400">{{ $sch->campaigns_count }} {{ __('campaigns') }}</div>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <div>{{ $sch->contact_number }}</div>
                                        <div class="text-[10px] text-gray-400">{{ $sch->email ?? 'N/A' }}</div>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <span class="text-base font-black text-indigo-600 dark:text-indigo-400 font-mono">
                                            {{ number_format($sch->credits_balance) }}
                                        </span>
                                        <span class="text-xs text-gray-400">cards</span>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        @if($sch->credits_balance >= 500)
                                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-950/80 dark:text-emerald-300">
                                                🟢 {{ __('Healthy') }}
                                            </span>
                                        @elseif($sch->credits_balance >= 100)
                                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-100 text-amber-700 dark:bg-amber-950/80 dark:text-amber-300">
                                                🟡 {{ __('Low Balance') }}
                                            </span>
                                        @else
                                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-100 text-rose-700 dark:bg-rose-950/80 dark:text-rose-300">
                                                🔴 {{ __('Critical / Empty') }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-3.5 px-4 text-right">
                                        <button wire:click="openGrantModal({{ $sch->id }})" 
                                                class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition shadow-sm inline-flex items-center gap-1">
                                            <span>+ {{ __('Add / Adjust Credits') }}</span>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-8 text-gray-400">
                                        {{ __('No schools found.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div>
                    {{ $schoolsWithBalance->links() }}
                </div>
            </div>
        @endif

        <!-- =================== TAB 4: TRANSACTION LEDGER =================== -->
        @if($activeTab === 'transactions')
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-bold text-gray-800 dark:text-gray-200 uppercase tracking-wider">{{ __('Wallet Transaction Ledger (Audit Trail)') }}</h3>
                    <div>
                        <select wire:model.live="transactionTypeFilter" class="text-xs bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl px-3 py-1.5 text-gray-700 dark:text-gray-300">
                            <option value="">{{ __('All Types') }}</option>
                            <option value="recharge">{{ __('Recharges') }}</option>
                            <option value="export_deduction">{{ __('Export Deductions') }}</option>
                            <option value="admin_adjustment">{{ __('Admin Adjustments') }}</option>
                            <option value="welcome_bonus">{{ __('Welcome Bonuses') }}</option>
                        </select>
                    </div>
                </div>

                <div class="overflow-x-auto rounded-2xl border border-gray-100 dark:border-gray-700">
                    <table class="w-full text-left text-xs text-gray-600 dark:text-gray-300">
                        <thead class="bg-gray-50 dark:bg-gray-900/50 text-[11px] font-bold text-gray-400 uppercase tracking-wider border-b border-gray-100 dark:border-gray-700">
                            <tr>
                                <th class="py-3.5 px-4">{{ __('Date & Time') }}</th>
                                <th class="py-3.5 px-4">{{ __('School') }}</th>
                                <th class="py-3.5 px-4">{{ __('Type') }}</th>
                                <th class="py-3.5 px-4">{{ __('Description') }}</th>
                                <th class="py-3.5 px-4">{{ __('Credits (+/-)') }}</th>
                                <th class="py-3.5 px-4">{{ __('Balance After') }}</th>
                                <th class="py-3.5 px-4">{{ __('Triggered By') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60 font-medium">
                            @forelse($transactions as $trx)
                                <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-700/30 transition">
                                    <td class="py-3.5 px-4 font-mono text-[11px] text-gray-400">
                                        {{ $trx->created_at->format('d M Y, h:i A') }}
                                    </td>
                                    <td class="py-3.5 px-4 font-bold text-gray-900 dark:text-gray-100">
                                        {{ $trx->school->name }}
                                    </td>
                                    <td class="py-3.5 px-4">
                                        @if($trx->type === 'recharge')
                                            <span class="px-2 py-0.5 rounded bg-emerald-100 dark:bg-emerald-950/80 text-emerald-700 dark:text-emerald-300 font-bold text-[10px] uppercase">
                                                {{ __('Recharge') }}
                                            </span>
                                        @elseif($trx->type === 'export_deduction')
                                            <span class="px-2 py-0.5 rounded bg-rose-100 dark:bg-rose-950/80 text-rose-700 dark:text-rose-300 font-bold text-[10px] uppercase">
                                                {{ __('Export Deduct') }}
                                            </span>
                                        @elseif($trx->type === 'welcome_bonus')
                                            <span class="px-2 py-0.5 rounded bg-purple-100 dark:bg-purple-950/80 text-purple-700 dark:text-purple-300 font-bold text-[10px] uppercase">
                                                {{ __('Welcome Gift') }}
                                            </span>
                                        @else
                                            <span class="px-2 py-0.5 rounded bg-indigo-100 dark:bg-indigo-950/80 text-indigo-700 dark:text-indigo-300 font-bold text-[10px] uppercase">
                                                {{ __('Adjustment') }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-3.5 px-4 text-gray-700 dark:text-gray-300">
                                        {{ $trx->description }}
                                    </td>
                                    <td class="py-3.5 px-4 font-mono font-bold text-sm {{ $trx->credits >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                                        {{ $trx->credits > 0 ? '+' : '' }}{{ number_format($trx->credits) }}
                                    </td>
                                    <td class="py-3.5 px-4 font-mono font-bold text-gray-900 dark:text-gray-100">
                                        {{ number_format($trx->balance_after) }}
                                    </td>
                                    <td class="py-3.5 px-4 text-[11px] text-gray-400">
                                        {{ $trx->performedByUser?->name ?? 'System' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-8 text-gray-400">
                                        {{ __('No transactions found.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div>
                    {{ $transactions->links() }}
                </div>
            </div>
        @endif
    </div>

    <!-- =================== MODAL: GRANT / ADJUST CREDITS =================== -->
    @if($showGrantModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/80 backdrop-blur-sm" x-transition.opacity>
            <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 max-w-lg w-full shadow-2xl border border-gray-100 dark:border-gray-700 space-y-5">
                <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700 pb-3">
                    <h3 class="text-lg font-black text-gray-900 dark:text-gray-100">{{ __('Manual Credit Grant / Adjustment') }}</h3>
                    <button wire:click="$set('showGrantModal', false)" class="text-gray-400 hover:text-gray-600 text-lg font-bold">✕</button>
                </div>

                <div class="space-y-4 text-xs">
                    <div>
                        <label class="block font-bold text-gray-700 dark:text-gray-300 uppercase mb-1">{{ __('Target School') }}</label>
                        <select wire:model="selectedSchoolId" class="w-full bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl px-3 py-2 text-gray-900 dark:text-gray-100 font-medium">
                            @foreach($allSchools as $s)
                                <option value="{{ $s->id }}">{{ $s->name }} (Current Balance: {{ number_format($s->credits_balance) }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block font-bold text-gray-700 dark:text-gray-300 uppercase mb-1">{{ __('Action') }}</label>
                        <div class="grid grid-cols-2 gap-2">
                            <button type="button" wire:click="$set('grantAction', 'add')" 
                                    class="py-2 rounded-xl font-bold border transition {{ $grantAction === 'add' ? 'bg-emerald-600 text-white border-emerald-600 shadow' : 'bg-gray-50 dark:bg-gray-900 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-700' }}">
                                + {{ __('Add / Gift Credits') }}
                            </button>
                            <button type="button" wire:click="$set('grantAction', 'deduct')" 
                                    class="py-2 rounded-xl font-bold border transition {{ $grantAction === 'deduct' ? 'bg-rose-600 text-white border-rose-600 shadow' : 'bg-gray-50 dark:bg-gray-900 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-700' }}">
                                - {{ __('Deduct Credits') }}
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold text-gray-700 dark:text-gray-300 uppercase mb-1">{{ __('Number of Credits') }}</label>
                        <input type="number" wire:model="grantCredits" min="1" max="100000" class="w-full bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl px-3 py-2 text-gray-900 dark:text-gray-100 font-mono font-bold text-base" />
                    </div>

                    <div>
                        <label class="block font-bold text-gray-700 dark:text-gray-300 uppercase mb-1">{{ __('Reason / Category') }}</label>
                        <select wire:model="grantType" class="w-full bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl px-3 py-2 text-gray-900 dark:text-gray-100">
                            <option value="admin_adjustment">{{ __('Admin Manual Adjustment') }}</option>
                            <option value="demo_grant">{{ __('Demo / Trial Account Grant') }}</option>
                            <option value="payment_settlement">{{ __('Offline Cash / Direct Bank Settlement') }}</option>
                            <option value="welcome_bonus">{{ __('Bonus / Goodwill Gift') }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-bold text-gray-700 dark:text-gray-300 uppercase mb-1">{{ __('Remarks / Note (Optional)') }}</label>
                        <input type="text" wire:model="grantNote" placeholder="e.g. Paid via NEFT #593021 / Demo setup" class="w-full bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl px-3 py-2 text-gray-900 dark:text-gray-100" />
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100 dark:border-gray-700">
                    <button wire:click="$set('showGrantModal', false)" class="px-4 py-2 rounded-xl text-xs font-bold text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700">
                        {{ __('Cancel') }}
                    </button>
                    <button wire:click="submitManualGrant" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold uppercase tracking-wider shadow">
                        {{ __('Confirm & Apply to Wallet') }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- =================== MODAL: CREATE / EDIT PRICING SLAB =================== -->
    @if($showPlanModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/80 backdrop-blur-sm" x-transition.opacity>
            <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 max-w-lg w-full shadow-2xl border border-gray-100 dark:border-gray-700 space-y-5">
                <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700 pb-3">
                    <h3 class="text-lg font-black text-gray-900 dark:text-gray-100">{{ $editingPlanId ? __('Edit Pricing Slab') : __('Create Pricing Slab') }}</h3>
                    <button wire:click="$set('showPlanModal', false)" class="text-gray-400 hover:text-gray-600 text-lg font-bold">✕</button>
                </div>

                <div class="space-y-4 text-xs">
                    <div>
                        <label class="block font-bold text-gray-700 dark:text-gray-300 uppercase mb-1">{{ __('Plan / Slab Name') }}</label>
                        <input type="text" wire:model="planName" placeholder="e.g. Growth Pack" class="w-full bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl px-3 py-2 text-gray-900 dark:text-gray-100" />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-gray-700 dark:text-gray-300 uppercase mb-1">{{ __('Min Cards') }}</label>
                            <input type="number" wire:model="planMinQuantity" min="1" class="w-full bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl px-3 py-2 text-gray-900 dark:text-gray-100 font-mono" />
                        </div>
                        <div>
                            <label class="block font-bold text-gray-700 dark:text-gray-300 uppercase mb-1">{{ __('Max Cards (Empty for ∞)') }}</label>
                            <input type="number" wire:model="planMaxQuantity" placeholder="Unlimited" class="w-full bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl px-3 py-2 text-gray-900 dark:text-gray-100 font-mono" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-gray-700 dark:text-gray-300 uppercase mb-1">{{ __('Price per Card (₹)') }}</label>
                            <input type="number" step="0.50" wire:model="planPricePerCredit" class="w-full bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl px-3 py-2 text-gray-900 dark:text-gray-100 font-mono font-bold" />
                        </div>
                        <div>
                            <label class="block font-bold text-gray-700 dark:text-gray-300 uppercase mb-1">{{ __('Free Bonus %') }}</label>
                            <input type="number" wire:model="planBonusPercentage" min="0" max="100" class="w-full bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl px-3 py-2 text-gray-900 dark:text-gray-100 font-mono" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-gray-700 dark:text-gray-300 uppercase mb-1">{{ __('Badge Text (Optional)') }}</label>
                            <input type="text" wire:model="planBadgeText" placeholder="e.g. Popular, Best Value" class="w-full bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl px-3 py-2 text-gray-900 dark:text-gray-100" />
                        </div>
                        <div>
                            <label class="block font-bold text-gray-700 dark:text-gray-300 uppercase mb-1">{{ __('Sort Order') }}</label>
                            <input type="number" wire:model="planSortOrder" class="w-full bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl px-3 py-2 text-gray-900 dark:text-gray-100 font-mono" />
                        </div>
                    </div>

                    <div class="flex items-center gap-2 pt-2">
                        <input type="checkbox" id="planActive" wire:model="planIsActive" class="rounded text-indigo-600 focus:ring-indigo-500" />
                        <label for="planActive" class="font-bold text-gray-700 dark:text-gray-300">{{ __('Plan is active and visible to schools') }}</label>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100 dark:border-gray-700">
                    <button wire:click="$set('showPlanModal', false)" class="px-4 py-2 rounded-xl text-xs font-bold text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700">
                        {{ __('Cancel') }}
                    </button>
                    <button wire:click="savePlan" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold uppercase tracking-wider shadow">
                        {{ __('Save Pricing Slab') }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
