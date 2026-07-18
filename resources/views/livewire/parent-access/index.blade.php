<?php

use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use App\Models\ParentAccess;
use App\Models\SchoolUserRole;

new class extends Component {
    use WithFileUploads;

    // Search
    public $search = '';

    // Form fields (Single insert/edit)
    public $accessId = null;
    public $mobile = '';

    // Bulk upload fields
    public $bulkText = '';
    public $csvFile = null;

    // Component states
    public $isModalOpen = false;
    public $isBulkModalOpen = false;
    public $confirmingDeletion = false;
    public $accessToDelete = null;

    // Feedback messages
    public $bulkFeedback = '';

    // Pagination properties
    public $perPage = 12;
    public $hasMore = false;

    public function mount()
    {
        $this->checkAuthorization();
    }

    public function updatedSearch()
    {
        $this->perPage = 12;
    }

    public function loadMore()
    {
        $this->perPage += 12;
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

    public function loadAccesses()
    {
        $activeSchoolId = session('active_school_id');
        if (!$activeSchoolId) {
            $this->hasMore = false;
            return collect();
        }

        $query = ParentAccess::query()->where('school_id', $activeSchoolId);

        if ($this->search) {
            $query->where('mobile', 'like', '%' . $this->search . '%');
        }

        $totalCount = $query->count();
        $this->hasMore = $totalCount > $this->perPage;

        return $query->orderBy('created_at', 'desc')->take($this->perPage)->get();
    }

    // --- Single Insert Actions ---
    public function openCreateModal()
    {
        $this->resetValidation();
        $this->reset(['accessId', 'mobile']);
        $this->isModalOpen = true;
    }

    public function openEditModal($id)
    {
        $this->resetValidation();
        $access = ParentAccess::findOrFail($id);

        // Context check
        if ($access->school_id != session('active_school_id')) {
            abort(403);
        }

        $this->accessId = $access->id;
        $this->mobile = $access->mobile;
        $this->isModalOpen = true;
    }

    public function saveAccess()
    {
        $this->checkAuthorization();
        $activeSchoolId = session('active_school_id');

        if (!$activeSchoolId) {
            return;
        }

        $rules = [
            'mobile' => [
                'required',
                'string',
                'regex:/^[6-9]\d{9}$/', // Clean 10-digit Indian mobile validation
                function ($attribute, $value, $fail) use ($activeSchoolId) {
                    $query = ParentAccess::where('school_id', $activeSchoolId)
                        ->where('mobile', $value);
                    if ($this->accessId) {
                        $query->where('id', '!=', $this->accessId);
                    }
                    if ($query->exists()) {
                        $fail('This mobile number is already authorized for parent access.');
                    }
                }
            ],
        ];

        $this->validate($rules);

        ParentAccess::updateOrCreate(
            ['id' => $this->accessId],
            [
                'school_id' => $activeSchoolId,
                'mobile' => $this->mobile,
            ]
        );

        $this->isModalOpen = false;
        session()->flash('message', $this->accessId ? 'Parent mobile access updated successfully.' : 'Parent mobile access added successfully.');
        $this->reset(['accessId', 'mobile']);
    }

    // --- Bulk Insert Actions ---
    public function openBulkModal()
    {
        $this->resetValidation();
        $this->reset(['bulkText', 'csvFile', 'bulkFeedback']);
        $this->isBulkModalOpen = true;
    }

    public function importBulk()
    {
        $this->checkAuthorization();
        $activeSchoolId = session('active_school_id');

        if (!$activeSchoolId) {
            return;
        }

        $mobileList = [];

        // 1. Process Text Input Box
        if ($this->bulkText) {
            // Split by any comma, spaces, or newline characters
            $rawNumbers = preg_split('/[\s,\n\r]+/', $this->bulkText);
            foreach ($rawNumbers as $raw) {
                $clean = preg_replace('/\D/', '', $raw); // Remove any non-digits
                // If it's an Indian mobile with country code (e.g. 919876543210), trim to 10 digits
                if (strlen($clean) === 12 && str_starts_with($clean, '91')) {
                    $clean = substr($clean, 2);
                }
                if ($clean) {
                    $mobileList[] = $clean;
                }
            }
        }

        // 2. Process CSV Upload File
        if ($this->csvFile) {
            $path = $this->csvFile->getRealPath();
            if (($handle = fopen($path, 'r')) !== false) {
                while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                    foreach ($row as $cell) {
                        $clean = preg_replace('/\D/', '', $cell);
                        if (strlen($clean) === 12 && str_starts_with($clean, '91')) {
                            $clean = substr($clean, 2);
                        }
                        if ($clean) {
                            $mobileList[] = $clean;
                        }
                    }
                }
                fclose($handle);
            }
        }

        if (empty($mobileList)) {
            $this->addError('bulkText', 'No mobile numbers found in text input or CSV upload.');
            return;
        }

        // Filter valid 10-digit mobile numbers matching standard pattern (starts with 6-9)
        $validMobiles = array_filter($mobileList, function ($num) {
            return preg_match('/^[6-9]\d{9}$/', $num);
        });

        $validMobiles = array_unique($validMobiles);

        $insertedCount = 0;
        $skippedCount = 0;

        foreach ($validMobiles as $mobile) {
            $exists = ParentAccess::where('school_id', $activeSchoolId)
                ->where('mobile', $mobile)
                ->exists();

            if (!$exists) {
                ParentAccess::create([
                    'school_id' => $activeSchoolId,
                    'mobile' => $mobile,
                ]);
                $insertedCount++;
            } else {
                $skippedCount++;
            }
        }

        $invalidCount = count($mobileList) - count($validMobiles);

        $this->bulkFeedback = "Import complete! Successfully added {$insertedCount} parent mobile(s).";
        if ($skippedCount > 0) {
            $this->bulkFeedback .= " Skipped {$skippedCount} duplicate(s).";
        }
        if ($invalidCount > 0) {
            $this->bulkFeedback .= " Ignored {$invalidCount} invalid format number(s).";
        }

        $this->reset(['bulkText', 'csvFile']);
        session()->flash('message', $this->bulkFeedback);
        $this->isBulkModalOpen = false;
    }

    // --- Deletion Actions ---
    public function confirmDeletion($id)
    {
        $access = ParentAccess::findOrFail($id);

        // Context check
        if ($access->school_id != session('active_school_id')) {
            abort(403);
        }

        $this->accessToDelete = $id;
        $this->confirmingDeletion = true;
    }

    public function deleteAccess()
    {
        if ($this->accessToDelete) {
            $access = ParentAccess::findOrFail($this->accessToDelete);

            // Context check
            if ($access->school_id != session('active_school_id')) {
                abort(403);
            }

            $access->delete();
            session()->flash('message', 'Parent mobile access revoked successfully.');
        }

        $this->confirmingDeletion = false;
        $this->accessToDelete = null;
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

    @if (!session('active_school_id'))
        <!-- Warning Card for Empty Context -->
        <div class="bg-white dark:bg-gray-800 rounded-3xl p-8 border border-gray-100 dark:border-gray-700 text-center">
            <div class="w-16 h-16 bg-amber-50 dark:bg-amber-950/20 text-amber-500 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <h3 class="text-base font-extrabold text-gray-900 dark:text-gray-100">{{ __('No Active School Selected') }}</h3>
            <p class="text-xs text-gray-550 dark:text-gray-400 mt-1 max-w-sm mx-auto leading-relaxed">
                {{ __('Please select a school in the header drop-down or register a school profile to get started.') }}
            </p>
        </div>
    @else
        @php
            $accesses = $this->loadAccesses();
        @endphp

        <!-- Controls Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="relative w-full sm:w-80">
                <input wire:model.live="search" type="text" placeholder="{{ __('Search parent mobile number...') }}" class="w-full pl-10 pr-4 py-2.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:text-gray-200 transition">
                <div class="absolute left-3 top-3 text-gray-400">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <button wire:click="openBulkModal" class="px-5 py-2.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 font-extrabold text-xs uppercase tracking-wider rounded-2xl transition hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center justify-center gap-2 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                    </svg>
                    {{ __('Bulk Import') }}
                </button>
                <button wire:click="openCreateModal" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-xs uppercase tracking-wider rounded-2xl transition shadow-md shadow-indigo-600/10 flex items-center justify-center gap-2 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                    </svg>
                    {{ __('Add Single Mobile') }}
                </button>
            </div>
        </div>

        <!-- Parent Access Grid Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            @forelse ($accesses as $access)
                <div class="bg-white dark:bg-gray-800 rounded-3xl border border-gray-100 dark:border-gray-700 p-6 flex flex-col justify-between shadow-sm relative overflow-hidden group hover:border-indigo-100 dark:hover:border-indigo-900/50 hover:shadow-md transition duration-300">
                    <div>
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <span class="text-[9px] uppercase font-black tracking-widest text-indigo-600 dark:text-indigo-400 block mb-0.5">{{ __('Authorized Mobile') }}</span>
                                <h4 class="font-bold text-gray-900 dark:text-gray-100 text-base leading-tight select-all">
                                    {{ $access->mobile }}
                                </h4>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Actions -->
                    <div class="pt-4 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between mt-4">
                        <span class="text-[9px] uppercase font-black tracking-widest text-gray-400 dark:text-gray-500">
                            {{ __('ID:') }} #{{ $access->id }}
                        </span>
                        <div class="flex items-center gap-1">
                            <button wire:click="openEditModal({{ $access->id }})" class="p-2 hover:bg-gray-50 dark:hover:bg-gray-900 rounded-xl text-gray-400 hover:text-indigo-600 dark:text-gray-500 dark:hover:text-indigo-400 transition-colors cursor-pointer">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                            </button>
                            <button wire:click="confirmDeletion({{ $access->id }})" class="p-2 hover:bg-red-50 dark:hover:bg-red-950/20 rounded-xl text-gray-400 hover:text-red-600 dark:text-gray-500 dark:hover:text-red-400 transition-colors cursor-pointer">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full bg-white dark:bg-gray-800 rounded-3xl p-12 text-center text-gray-400 dark:text-gray-500 border border-gray-100 dark:border-gray-700">
                    {{ __('No authorized parent mobile numbers configured.') }}
                </div>
            @endforelse
        </div>

        @if ($hasMore)
            <div class="flex justify-center pt-8">
                <button wire:click="loadMore" class="px-6 py-3 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/60 text-gray-700 dark:text-gray-300 font-extrabold text-xs uppercase tracking-wider rounded-2xl transition shadow-sm flex items-center gap-2 cursor-pointer">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 13l-7 7-7-7m14-6l-7 7-7-7"/>
                    </svg>
                    {{ __('Load More') }}
                </button>
            </div>
        @endif
    @endif

    <!-- Add/Edit Single Modal -->
    @if ($isModalOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-gray-950/65 backdrop-blur-sm transition-opacity" wire:click="$set('isModalOpen', false)"></div>

            <div class="bg-white dark:bg-gray-800 rounded-3xl overflow-hidden shadow-2xl transform transition-all max-w-md w-full border border-gray-100 dark:border-gray-700 z-10 p-6 sm:p-8">
                <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-gray-700 mb-6">
                    <h3 class="text-lg font-black text-gray-900 dark:text-gray-100">
                        {{ $accessId ? __('Edit Parent Access') : __('Add Parent Access') }}
                    </h3>
                    <button wire:click="$set('isModalOpen', false)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form wire:submit="saveAccess" class="space-y-4">
                    <!-- Mobile Number -->
                    <div>
                        <x-input-label for="mobile" :value="__('Mobile Number (10 Digits)')" />
                        <x-text-input wire:model="mobile" id="mobile" type="tel" maxlength="10" class="mt-1 block w-full" placeholder="e.g. 9664588677" required />
                        <span class="text-[10px] text-gray-400 dark:text-gray-500 mt-1 block">{{ __('Starts with 6-9, without country code.') }}</span>
                        <x-input-error :messages="$errors->get('mobile')" class="mt-2" />
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700 mt-6">
                        <button type="button" wire:click="$set('isModalOpen', false)" class="px-5 py-2.5 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/60 rounded-xl font-bold text-xs uppercase text-gray-700 dark:text-gray-300 transition cursor-pointer">
                            {{ __('Cancel') }}
                        </button>
                        <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold text-xs uppercase shadow transition cursor-pointer">
                            {{ __('Save') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Bulk Import Modal -->
    @if ($isBulkModalOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-gray-950/65 backdrop-blur-sm transition-opacity" wire:click="$set('isBulkModalOpen', false)"></div>

            <div class="bg-white dark:bg-gray-800 rounded-3xl overflow-hidden shadow-2xl transform transition-all max-w-lg w-full border border-gray-100 dark:border-gray-700 z-10 p-6 sm:p-8">
                <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-gray-700 mb-6">
                    <h3 class="text-lg font-black text-gray-900 dark:text-gray-100">
                        {{ __('Bulk Import Mobile Numbers') }}
                    </h3>
                    <button wire:click="$set('isBulkModalOpen', false)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form wire:submit="importBulk" class="space-y-6">
                    <!-- CSV File Input -->
                    <div>
                        <x-input-label for="csvFile" :value="__('Option 1: Upload CSV / Excel exported file')" />
                        <input wire:model="csvFile" id="csvFile" type="file" accept=".csv" class="mt-2 block w-full text-xs text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-indigo-50 dark:file:bg-indigo-950/30 file:text-indigo-700 dark:file:text-indigo-400 file:cursor-pointer hover:file:bg-indigo-100 dark:hover:file:bg-indigo-900/50 transition">
                        <span class="text-[10px] text-gray-400 dark:text-gray-500 mt-1.5 block leading-normal">{{ __('Accepts standard .csv comma-separated lists containing a column of 10-digit mobile numbers.') }}</span>
                        <x-input-error :messages="$errors->get('csvFile')" class="mt-2" />
                    </div>

                    <div class="relative flex py-2 items-center">
                        <div class="flex-grow border-t border-gray-150/60 dark:border-gray-700"></div>
                        <span class="flex-shrink mx-4 text-[9px] uppercase font-black tracking-widest text-gray-400 dark:text-gray-500">{{ __('OR') }}</span>
                        <div class="flex-grow border-t border-gray-150/60 dark:border-gray-700"></div>
                    </div>

                    <!-- Bulk Text Copy Paste Box -->
                    <div>
                        <x-input-label for="bulkText" :value="__('Option 2: Paste List of Mobile Numbers')" />
                        <textarea wire:model="bulkText" id="bulkText" rows="6" class="mt-2 block w-full rounded-2xl bg-white dark:bg-gray-900 border-gray-200 dark:border-gray-700 text-xs placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:text-gray-200 transition" placeholder="9876543210&#10;9664588677, 9769409405&#10;9988776655"></textarea>
                        <span class="text-[10px] text-gray-400 dark:text-gray-500 mt-1.5 block leading-normal">{{ __('Paste one number per line, or separate them by commas or spaces. Invalid number formats are ignored, duplicates are skipped.') }}</span>
                        <x-input-error :messages="$errors->get('bulkText')" class="mt-2" />
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                        <button type="button" wire:click="$set('isBulkModalOpen', false)" class="px-5 py-2.5 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/60 rounded-xl font-bold text-xs uppercase text-gray-700 dark:text-gray-300 transition cursor-pointer">
                            {{ __('Cancel') }}
                        </button>
                        <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold text-xs uppercase shadow transition cursor-pointer">
                            {{ __('Import Now') }}
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
                    <h3 class="text-base font-extrabold text-gray-900 dark:text-gray-100">{{ __('Revoke Parent Access') }}</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-2 leading-relaxed">
                        {{ __('Are you sure you want to revoke access for this parent mobile number? This will block pending links to this school.') }}
                    </p>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700 mt-6">
                    <button type="button" wire:click="$set('confirmingDeletion', false)" class="px-4 py-2 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/60 rounded-xl font-bold text-xs uppercase text-gray-700 dark:text-gray-300 transition cursor-pointer">
                        {{ __('Cancel') }}
                    </button>
                    <button type="button" wire:click="deleteAccess" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-xl font-bold text-xs uppercase shadow transition cursor-pointer">
                        {{ __('Revoke') }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
