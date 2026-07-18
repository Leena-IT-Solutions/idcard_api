<?php

use Livewire\Volt\Component;
use App\Models\Campaign;
use App\Models\SchoolUserRole;

new class extends Component {
    // Search
    public $search = '';

    // Form fields
    public $campaignId = null;
    public $name = '';
    public $registration_start_date = '';
    public $registration_end_date = '';

    // Component states
    public $isModalOpen = false;
    public $confirmingDeletion = false;
    public $campaignToDelete = null;

    public function mount()
    {
        $this->checkAuthorization();
    }

    protected function checkAuthorization()
    {
        $user = auth()->user();
        $activeSchoolId = session('active_school_id');

        $isSchoolAdmin = $activeSchoolId && SchoolUserRole::where('user_id', $user->id)
            ->where('school_id', $activeSchoolId)
            ->whereHas('role', function($q) { $q->where('slug', 'school_admin'); })
            ->exists();

        if (!$user->hasRole('saas_admin') && !$isSchoolAdmin) {
            abort(403);
        }
    }

    public function loadCampaigns()
    {
        $activeSchoolId = session('active_school_id');
        if (!$activeSchoolId && !auth()->user()->hasRole('saas_admin')) {
            return collect();
        }

        $query = Campaign::query();

        if (!auth()->user()->hasRole('saas_admin')) {
            $query->where('school_id', $activeSchoolId);
        }

        if ($this->search) {
            $query->where('name', 'like', '%' . $this->search . '%');
        }

        return $query->orderBy('registration_start_date', 'desc')->get();
    }

    // --- CRUD Actions ---
    public function openCreateModal()
    {
        $this->resetValidation();
        $this->reset(['campaignId', 'name', 'registration_start_date', 'registration_end_date']);
        $this->isModalOpen = true;
    }

    public function openEditModal($id)
    {
        $this->resetValidation();
        $campaign = Campaign::findOrFail($id);

        // Security check
        if (!auth()->user()->hasRole('saas_admin') && $campaign->school_id != session('active_school_id')) {
            abort(403);
        }

        $this->campaignId = $campaign->id;
        $this->name = $campaign->name;
        $this->registration_start_date = $campaign->registration_start_date->format('Y-m-d');
        $this->registration_end_date = $campaign->registration_end_date->format('Y-m-d');
        $this->isModalOpen = true;
    }

    public function saveCampaign()
    {
        $this->checkAuthorization();
        $activeSchoolId = session('active_school_id');

        $validated = $this->validate([
            'name' => 'required|string|max:255',
            'registration_start_date' => 'required|date',
            'registration_end_date' => 'required|date|after_or_equal:registration_start_date',
        ]);

        Campaign::updateOrCreate(
            ['id' => $this->campaignId],
            [
                'school_id' => $activeSchoolId,
                'name' => $this->name,
                'registration_start_date' => $this->registration_start_date,
                'registration_end_date' => $this->registration_end_date,
            ]
        );

        $this->isModalOpen = false;
        session()->flash('message', $this->campaignId ? 'Campaign updated successfully.' : 'Campaign created successfully.');
        $this->reset(['campaignId', 'name', 'registration_start_date', 'registration_end_date']);
    }

    public function confirmDeletion($id)
    {
        $campaign = Campaign::findOrFail($id);

        // Security check
        if (!auth()->user()->hasRole('saas_admin') && $campaign->school_id != session('active_school_id')) {
            abort(403);
        }

        $this->campaignToDelete = $id;
        $this->confirmingDeletion = true;
    }

    public function deleteCampaign()
    {
        if ($this->campaignToDelete) {
            $campaign = Campaign::findOrFail($this->campaignToDelete);

            // Security check
            if (!auth()->user()->hasRole('saas_admin') && $campaign->school_id != session('active_school_id')) {
                abort(403);
            }

            $campaign->delete();
            session()->flash('message', 'Campaign deleted successfully.');
        }

        $this->confirmingDeletion = false;
        $this->campaignToDelete = null;
    }
};

?>

<div class="space-y-6">
    <!-- Session Messages -->
    @if (session()->has('message'))
        <div class="p-4 bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-100 dark:border-emerald-900/30 text-emerald-600 dark:text-emerald-400 rounded-2xl text-sm font-semibold">
            {{ session('message') }}
        </div>
    @endif

    @if (!session('active_school_id') && !auth()->user()->hasRole('saas_admin'))
        <!-- Warning Card for Empty Context -->
        <div class="bg-white dark:bg-gray-800 rounded-3xl p-8 border border-gray-100 dark:border-gray-700 text-center">
            <div class="w-16 h-16 bg-amber-50 dark:bg-amber-950/20 text-amber-500 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <h3 class="text-base font-extrabold text-gray-900 dark:text-gray-100">{{ __('No Active School Selected') }}</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 max-w-sm mx-auto leading-relaxed">
                {{ __('Please select a school in the header drop-down or register a school profile to get started.') }}
            </p>
        </div>
    @else
        @php
            $campaigns = $this->loadCampaigns();
        @endphp

        <!-- Header Dashboard Controls -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="relative w-full sm:w-80">
                <input wire:model.live="search" type="text" placeholder="{{ __('Search campaigns by name...') }}" class="w-full pl-10 pr-4 py-2.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:text-gray-200 transition">
                <div class="absolute left-3 top-3 text-gray-400">
                    <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
            </div>
            <button wire:click="openCreateModal" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-xs uppercase tracking-wider rounded-2xl transition shadow-md shadow-indigo-600/10 flex items-center justify-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                </svg>
                {{ __('Add Campaign') }}
            </button>
        </div>

        <!-- Grid Cards View -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse ($campaigns as $campaign)
                <div class="bg-white dark:bg-gray-800 rounded-3xl border border-gray-100 dark:border-gray-700 p-6 flex flex-col justify-between shadow-sm relative overflow-hidden group hover:border-indigo-100 dark:hover:border-indigo-900/50 hover:shadow-md transition duration-300">
                    <div>
                        <!-- Header Details -->
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <span class="text-[9px] uppercase font-black tracking-widest text-indigo-600 dark:text-indigo-400 block mb-0.5">{{ __('Registration Campaign') }}</span>
                                <h4 class="font-bold text-gray-900 dark:text-gray-100 text-base group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors leading-tight">
                                    {{ $campaign->name }}
                                </h4>
                            </div>
                        </div>

                        <!-- Info Grid -->
                        <div class="grid grid-cols-2 gap-4 text-xs pt-4 border-t border-gray-150/40 dark:border-gray-700/50">
                            <div class="flex flex-col gap-0.5">
                                <span class="text-[9px] uppercase font-black text-gray-400 dark:text-gray-500 tracking-wider">{{ __('Start Date') }}</span>
                                <span class="text-gray-800 dark:text-gray-200 font-semibold select-all">{{ $campaign->registration_start_date->format('d M, Y') }}</span>
                            </div>
                            <div class="flex flex-col gap-0.5">
                                <span class="text-[9px] uppercase font-black text-gray-400 dark:text-gray-500 tracking-wider">{{ __('End Date') }}</span>
                                <span class="text-gray-800 dark:text-gray-200 font-semibold select-all">{{ $campaign->registration_end_date->format('d M, Y') }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Actions -->
                    <div class="pt-4 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between mt-6">
                        <span class="text-[9px] uppercase font-black tracking-widest text-gray-400 dark:text-gray-500">
                            {{ __('ID:') }} #{{ $campaign->id }}
                        </span>
                        <div class="flex items-center gap-1">
                            <button wire:click="openEditModal({{ $campaign->id }})" class="p-2 hover:bg-gray-50 dark:hover:bg-gray-900 rounded-xl text-gray-400 hover:text-indigo-600 dark:text-gray-500 dark:hover:text-indigo-400 transition-colors">
                                <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                            </button>
                            <button wire:click="confirmDeletion({{ $campaign->id }})" class="p-2 hover:bg-red-50 dark:hover:bg-red-950/20 rounded-xl text-gray-400 hover:text-red-600 dark:text-gray-500 dark:hover:text-red-400 transition-colors">
                                <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full bg-white dark:bg-gray-800 rounded-3xl p-12 text-center text-gray-400 dark:text-gray-500 border border-gray-100 dark:border-gray-700">
                    {{ __('No campaigns created yet.') }}
                </div>
            @endforelse
        </div>
    @endif

    <!-- Create/Edit Modal -->
    @if ($isModalOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-gray-950/65 backdrop-blur-sm transition-opacity" wire:click="$set('isModalOpen', false)"></div>

            <div class="bg-white dark:bg-gray-800 rounded-3xl overflow-hidden shadow-2xl transform transition-all max-w-md w-full border border-gray-100 dark:border-gray-700 z-10 p-6 sm:p-8">
                <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-gray-700 mb-6">
                    <h3 class="text-lg font-black text-gray-900 dark:text-gray-100">
                        {{ $campaignId ? __('Edit Campaign') : __('Add New Campaign') }}
                    </h3>
                    <button wire:click="$set('isModalOpen', false)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form wire:submit="saveCampaign" class="space-y-4">
                    <!-- Campaign Name -->
                    <div>
                        <x-input-label for="name" :value="__('Campaign Name')" />
                        <x-text-input wire:model="name" id="name" type="text" class="mt-1 block w-full" placeholder="e.g. Admission 2026-27" required />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <!-- Registration Start Date -->
                    <div>
                        <x-input-label for="registration_start_date" :value="__('Registration Start Date')" />
                        <x-text-input wire:model="registration_start_date" id="registration_start_date" type="date" class="mt-1 block w-full" required />
                        <x-input-error :messages="$errors->get('registration_start_date')" class="mt-2" />
                    </div>

                    <!-- Registration End Date -->
                    <div>
                        <x-input-label for="registration_end_date" :value="__('Registration End Date')" />
                        <x-text-input wire:model="registration_end_date" id="registration_end_date" type="date" class="mt-1 block w-full" required />
                        <x-input-error :messages="$errors->get('registration_end_date')" class="mt-2" />
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700 mt-6">
                        <button type="button" wire:click="$set('isModalOpen', false)" class="px-5 py-2.5 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/60 rounded-xl font-bold text-xs uppercase text-gray-700 dark:text-gray-300 transition">
                            {{ __('Cancel') }}
                        </button>
                        <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold text-xs uppercase shadow transition">
                            {{ __('Save') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Delete Confirmation Modal -->
    @if ($confirmingDeletion)
        <div class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-gray-950/65 backdrop-blur-sm transition-opacity" wire:click="$set('confirmingDeletion', false)"></div>

            <div class="bg-white dark:bg-gray-800 rounded-3xl overflow-hidden shadow-2xl transform transition-all max-w-sm w-full border border-gray-100 dark:border-gray-700 z-10 p-6">
                <div class="text-center">
                    <div class="w-12 h-12 bg-red-50 dark:bg-red-950/20 text-red-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <h3 class="text-base font-extrabold text-gray-900 dark:text-gray-100">{{ __('Delete Campaign') }}</h3>
                    <p class="text-xs text-gray-550 dark:text-gray-400 mt-2 leading-relaxed">
                        {{ __('Are you sure you want to permanently delete this registration campaign? This action cannot be undone.') }}
                    </p>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700 mt-6">
                    <button type="button" wire:click="$set('confirmingDeletion', false)" class="px-4 py-2 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/60 rounded-xl font-bold text-xs uppercase text-gray-700 dark:text-gray-300 transition">
                        {{ __('Cancel') }}
                    </button>
                    <button type="button" wire:click="deleteCampaign" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-xl font-bold text-xs uppercase shadow transition">
                        {{ __('Delete') }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
