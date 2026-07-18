<?php

use Livewire\Volt\Component;
use App\Models\Grade;
use App\Models\Division;
use Illuminate\Validation\Rule;

new class extends Component {
    // Search properties
    public $search = '';

    // Grade Properties
    public $grades = [];
    public $gradeId = null;
    public $gradeName = '';

    // Dynamic Divisions list inside the Grade form
    public $tempDivisions = [];
    public $newDivisionName = '';

    // Modal states
    public $isModalOpen = false;
    public $confirmingDeletion = false;
    public $gradeToDelete = null;

    // Pagination properties
    public $perPage = 6;
    public $hasMore = false;

    public function mount()
    {
        $this->loadGrades();
    }

    public function updatedSearch()
    {
        $this->perPage = 6;
        $this->loadGrades();
    }

    public function loadMore()
    {
        $this->perPage += 6;
        $this->loadGrades();
    }

    public function loadGrades()
    {
        $activeSchoolId = session('active_school_id');
        if (!$activeSchoolId) {
            $this->grades = [];
            return;
        }

        $query = Grade::with('divisions')->where('school_id', $activeSchoolId);
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhereHas('divisions', function ($sub) {
                      $sub->where('name', 'like', '%' . $this->search . '%');
                  });
            });
        }
        $totalCount = $query->count();
        $this->grades = $query->orderBy('name', 'asc')->take($this->perPage)->get();
        $this->hasMore = $totalCount > $this->perPage;
    }

    // --- Dynamic Divisions handlers ---
    public function addDivision()
    {
        $name = trim($this->newDivisionName);
        if (!$name) {
            return;
        }

        // Validate duplicates in temporary list
        if (in_array(strtolower($name), array_map('strtolower', $this->tempDivisions))) {
            $this->addError('newDivisionName', 'This division is already added.');
            return;
        }

        $this->tempDivisions[] = $name;
        $this->newDivisionName = '';
        $this->resetErrorBag('newDivisionName');
    }

    public function removeTempDivision($index)
    {
        if (isset($this->tempDivisions[$index])) {
            unset($this->tempDivisions[$index]);
            $this->tempDivisions = array_values($this->tempDivisions);
        }
    }

    // --- CRUD ---
    public function openCreateModal()
    {
        if (!session('active_school_id')) {
            $this->addError('gradeName', 'Please select a school first.');
            return;
        }
        $this->resetValidation();
        $this->resetErrorBag();
        $this->gradeId = null;
        $this->gradeName = '';
        $this->tempDivisions = [];
        $this->newDivisionName = '';
        $this->isModalOpen = true;
    }

    public function openEditModal($id)
    {
        $this->resetValidation();
        $this->resetErrorBag();
        $grade = Grade::with('divisions')->findOrFail($id);
        $this->gradeId = $grade->id;
        $this->gradeName = $grade->name;
        $this->tempDivisions = $grade->divisions->pluck('name')->toArray();
        $this->newDivisionName = '';
        $this->isModalOpen = true;
    }

    public function saveGrade()
    {
        $activeSchoolId = session('active_school_id');
        if (!$activeSchoolId) {
            $this->addError('gradeName', 'Please select a school first.');
            return;
        }

        $this->resetErrorBag('newDivisionName');

        $this->validate([
            'gradeName' => [
                'required',
                'string',
                'max:255',
                Rule::unique('grades', 'name')
                    ->where('school_id', $activeSchoolId)
                    ->ignore($this->gradeId),
            ],
            'tempDivisions' => 'required|array|min:1',
            'tempDivisions.*' => 'required|string|max:50',
        ], [
            'gradeName.required' => 'The grade name is required.',
            'gradeName.unique' => 'This grade name already exists under this school.',
            'tempDivisions.required' => 'You must add at least one division.',
            'tempDivisions.min' => 'You must add at least one division.',
        ]);

        // Save Grade
        $grade = Grade::updateOrCreate(
            ['id' => $this->gradeId],
            [
                'name' => $this->gradeName,
                'school_id' => $activeSchoolId,
            ]
        );

        // Sync Divisions
        $currentDivNames = $grade->divisions()->pluck('name')->toArray();
        
        // 1. Delete removed divisions
        $removedDivs = array_diff($currentDivNames, $this->tempDivisions);
        $grade->divisions()->whereIn('name', $removedDivs)->delete();

        // 2. Insert new divisions
        $newDivs = array_diff($this->tempDivisions, $currentDivNames);
        foreach ($newDivs as $name) {
            $grade->divisions()->create(['name' => $name]);
        }

        $this->isModalOpen = false;
        $this->loadGrades();
    }

    public function confirmDeletion($id)
    {
        $this->gradeToDelete = $id;
        $this->confirmingDeletion = true;
    }

    public function deleteGrade()
    {
        if ($this->gradeToDelete) {
            Grade::destroy($this->gradeToDelete);
            $this->gradeToDelete = null;
            $this->confirmingDeletion = false;
            $this->loadGrades();
        }
    }
};

?>

<div class="space-y-8">
    <!-- Top Header Overview Card -->
    <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 sm:p-8 shadow-xl shadow-gray-200/40 dark:shadow-none border border-gray-100 dark:border-gray-700 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6">
        <div class="flex items-center space-x-4">
            <div class="p-3.5 bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 rounded-2xl">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
            </div>
            <div>
                <h3 class="text-xl font-extrabold text-gray-900 dark:text-gray-100">{{ __('Grade & Division Management') }}</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    {{ __('Manage school standards and their associated division sections') }}
                </p>
            </div>
        </div>
        <div>
            <button wire:click="openCreateModal" class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs uppercase tracking-wider rounded-xl transition shadow">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                </svg>
                <span>{{ __('Add Grade') }}</span>
            </button>
        </div>
    </div>

    <!-- Search bar -->
    <div class="relative w-full">
        <span class="absolute inset-y-0 left-0 flex items-center pl-3">
            <svg class="h-5 w-5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </span>
        <input type="text" wire:model.live.debounce.150ms="search" placeholder="Search grades or divisions..." class="w-full pl-10 pr-4 py-3 bg-white dark:bg-gray-800 border-gray-100 dark:border-gray-700 rounded-2xl dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 shadow-md shadow-gray-200/20 dark:shadow-none" />
    </div>

    <!-- Grid of Grade Cards -->
    <div class="flex flex-col gap-6">
        @forelse ($grades as $grade)
            <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 shadow-xl shadow-gray-200/40 dark:shadow-none border border-gray-100 dark:border-gray-700 hover:border-indigo-500/30 dark:hover:border-indigo-400/20 transition-all duration-300 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6 group">
                <div class="flex items-center space-x-4">
                    <span class="w-12 h-12 rounded-2xl bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-black text-lg shadow-sm">
                        {{ strtoupper(substr($grade->name, 0, 1)) }}
                    </span>
                    <div class="space-y-1">
                        <h4 class="font-extrabold text-gray-900 dark:text-gray-100 text-xl leading-none">
                            {{ $grade->name }}
                        </h4>
                        <span class="block text-[9px] text-gray-400 dark:text-gray-500 uppercase tracking-widest font-black">ID: #{{ $grade->id }}</span>
                    </div>
                </div>

                <!-- Divisions Badges section -->
                <div class="flex-1 flex flex-wrap items-center gap-2 sm:px-6">
                    @foreach ($grade->divisions as $division)
                        <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-teal-50 dark:bg-teal-500/10 text-teal-700 dark:text-teal-400 border border-teal-100 dark:border-teal-500/20 shadow-sm">
                            {{ $division->name }}
                        </span>
                    @endforeach
                </div>

                <!-- Action buttons -->
                <div class="flex items-center gap-1.5 shrink-0 self-end sm:self-auto border-t sm:border-t-0 pt-4 sm:pt-0 w-full sm:w-auto justify-end">
                    <button wire:click="openEditModal({{ $grade->id }})" class="p-2.5 hover:bg-gray-50 dark:hover:bg-gray-900 rounded-xl text-gray-400 hover:text-indigo-600 dark:text-gray-500 dark:hover:text-indigo-400 transition-colors">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </button>
                    <button wire:click="confirmDeletion({{ $grade->id }})" class="p-2.5 hover:bg-red-50 dark:hover:bg-red-950/20 rounded-xl text-gray-400 hover:text-red-605 dark:text-gray-500 dark:hover:text-red-400 transition-colors">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </div>
            </div>
        @empty
            <div class="bg-white dark:bg-gray-800 rounded-3xl p-12 text-center text-gray-400 dark:text-gray-500 border border-gray-100 dark:border-gray-700">
                {{ __('No grades found.') }}
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

    <!-- --- GRADE MODAL --- -->

    <!-- Create/Edit Grade Modal -->
    @if ($isModalOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-gray-950/60 backdrop-blur-sm transition-opacity" wire:click="$set('isModalOpen', false)"></div>

            <!-- Modal Container -->
            <div class="bg-white dark:bg-gray-800 rounded-3xl overflow-hidden shadow-xl transform transition-all w-full max-w-lg z-50 border border-gray-100 dark:border-gray-700">
                <form wire:submit="saveGrade" class="p-6 sm:p-8 space-y-6">
                    <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-gray-700">
                        <h3 class="text-base font-bold text-gray-900 dark:text-gray-100">
                            {{ $gradeId ? __('Edit Grade') : __('Add New Grade') }}
                        </h3>
                        <button type="button" wire:click="$set('isModalOpen', false)" class="text-gray-400 hover:text-gray-500">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Grade Name -->
                    <div>
                        <x-input-label for="gradeName" :value="__('Grade Name')" />
                        <x-text-input wire:model="gradeName" id="gradeName" type="text" class="mt-1 block w-full" required placeholder="e.g. Standard 1" />
                        <x-input-error :messages="$errors->get('gradeName')" class="mt-2" />
                    </div>

                    <!-- Add Divisions Section -->
                    <div class="space-y-3 pt-2 border-t border-gray-100 dark:border-gray-700">
                        <x-input-label :value="__('Add Divisions')" />
                        <div class="flex gap-2">
                            <x-text-input wire:model="newDivisionName" wire:keydown.enter.prevent="addDivision" type="text" class="block w-full" placeholder="e.g. Div A" />
                            <button type="button" wire:click="addDivision" class="px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white font-bold text-xs uppercase tracking-wider rounded-xl transition shadow shrink-0">
                                {{ __('Add') }}
                            </button>
                        </div>
                        <x-input-error :messages="$errors->get('newDivisionName')" class="mt-1" />
                        <x-input-error :messages="$errors->get('tempDivisions')" class="mt-1" />

                        <!-- List of Temporary Divisions -->
                        <div class="pt-2">
                            @if (count($tempDivisions) > 0)
                                <div class="flex flex-wrap gap-2 max-h-36 overflow-y-auto p-2 bg-gray-50 dark:bg-gray-900/60 rounded-2xl border border-gray-100 dark:border-gray-700">
                                    @foreach ($tempDivisions as $index => $div)
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-teal-50 dark:bg-teal-500/10 text-teal-700 dark:text-teal-400 border border-teal-100 dark:border-teal-500/20">
                                            {{ $div }}
                                            <button type="button" wire:click="removeTempDivision({{ $index }})" class="text-teal-400 hover:text-teal-650 dark:text-teal-500 dark:hover:text-teal-300 font-extrabold focus:outline-none">
                                                &times;
                                            </button>
                                        </span>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-xs text-gray-400 dark:text-gray-500 italic">{{ __('No divisions added yet. Type a division name and click Add.') }}</p>
                            @endif
                        </div>
                    </div>

                    <!-- Footer Actions -->
                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                        <button type="button" wire:click="$set('isModalOpen', false)" class="px-5 py-2.5 bg-transparent hover:bg-gray-50 dark:hover:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 font-bold text-xs uppercase tracking-wider rounded-xl transition">
                            {{ __('Cancel') }}
                        </button>
                        <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs uppercase tracking-wider rounded-xl transition shadow">
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
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-gray-950/60 backdrop-blur-sm transition-opacity" wire:click="$set('confirmingDeletion', false)"></div>

            <!-- Modal Container -->
            <div class="bg-white dark:bg-gray-800 rounded-3xl overflow-hidden shadow-xl transform transition-all w-full max-w-md z-50 border border-gray-100 dark:border-gray-700 p-6 sm:p-8 space-y-6">
                <div class="flex items-start space-x-4">
                    <div class="p-3 bg-red-50 dark:bg-red-950/40 text-red-650 dark:text-red-400 rounded-2xl shrink-0">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-gray-100">
                            {{ __('Delete Grade') }}
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-2 leading-relaxed">
                            {{ __('Are you sure you want to permanently delete this grade? All divisions linked to this grade will also be permanently deleted. This action cannot be undone.') }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                    <button type="button" wire:click="$set('confirmingDeletion', false)" class="px-5 py-2.5 bg-transparent hover:bg-gray-50 dark:hover:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 font-bold text-xs uppercase tracking-wider rounded-xl transition">
                        {{ __('Cancel') }}
                    </button>
                    <button type="button" wire:click="deleteGrade" class="px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white font-bold text-xs uppercase tracking-wider rounded-xl transition shadow">
                        {{ __('Delete') }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
