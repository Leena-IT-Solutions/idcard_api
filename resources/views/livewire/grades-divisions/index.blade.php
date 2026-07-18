<?php

use Livewire\Volt\Component;
use App\Models\Grade;
use App\Models\Division;
use Illuminate\Validation\Rule;

new class extends Component {
    // Grade properties
    public $grades = [];
    public $gradeSearch = '';
    public $gradeId = null;
    public $gradeName = '';
    public $isGradeModalOpen = false;
    public $confirmingGradeDeletion = false;
    public $gradeToDelete = null;

    // Division properties
    public $divisions = [];
    public $divisionSearch = '';
    public $divisionId = null;
    public $divisionName = '';
    public $divisionGradeId = '';
    public $isDivisionModalOpen = false;
    public $confirmingDivisionDeletion = false;
    public $divisionToDelete = null;

    public function mount()
    {
        $this->loadGrades();
        $this->loadDivisions();
    }

    public function updatedGradeSearch()
    {
        $this->loadGrades();
    }

    public function updatedDivisionSearch()
    {
        $this->loadDivisions();
    }

    public function loadGrades()
    {
        $query = Grade::query();
        if ($this->gradeSearch) {
            $query->where('name', 'like', '%' . $this->gradeSearch . '%');
        }
        $this->grades = $query->orderBy('name', 'asc')->get();
    }

    public function loadDivisions()
    {
        $query = Division::with('grade');
        if ($this->divisionSearch) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->divisionSearch . '%')
                  ->orWhereHas('grade', function ($g) {
                      $g->where('name', 'like', '%' . $this->divisionSearch . '%');
                  });
            });
        }
        $this->divisions = $query->orderBy('name', 'asc')->get();
    }

    // --- Grade CRUD ---
    public function openCreateGradeModal()
    {
        $this->resetValidation();
        $this->gradeId = null;
        $this->gradeName = '';
        $this->isGradeModalOpen = true;
    }

    public function openEditGradeModal($id)
    {
        $this->resetValidation();
        $grade = Grade::findOrFail($id);
        $this->gradeId = $grade->id;
        $this->gradeName = $grade->name;
        $this->isGradeModalOpen = true;
    }

    public function saveGrade()
    {
        $validated = $this->validate([
            'gradeName' => [
                'required',
                'string',
                'max:255',
                Rule::unique('grades', 'name')->ignore($this->gradeId),
            ],
        ], [
            'gradeName.required' => 'The grade name is required.',
            'gradeName.unique' => 'This grade name already exists.',
        ]);

        if ($this->gradeId) {
            $grade = Grade::findOrFail($this->gradeId);
            $grade->update(['name' => $this->gradeName]);
        } else {
            Grade::create(['name' => $this->gradeName]);
        }

        $this->isGradeModalOpen = false;
        $this->loadGrades();
        $this->loadDivisions(); // Reload divisions to update relational data if grade names changed
    }

    public function confirmGradeDeletion($id)
    {
        $this->gradeToDelete = $id;
        $this->confirmingGradeDeletion = true;
    }

    public function deleteGrade()
    {
        if ($this->gradeToDelete) {
            Grade::destroy($this->gradeToDelete);
            $this->gradeToDelete = null;
            $this->confirmingGradeDeletion = false;
            $this->loadGrades();
            $this->loadDivisions(); // Reload divisions to reflect cascade delete
        }
    }

    // --- Division CRUD ---
    public function openCreateDivisionModal()
    {
        $this->resetValidation();
        $this->divisionId = null;
        $this->divisionName = '';
        $this->divisionGradeId = '';
        $this->isDivisionModalOpen = true;
    }

    public function openEditDivisionModal($id)
    {
        $this->resetValidation();
        $division = Division::findOrFail($id);
        $this->divisionId = $division->id;
        $this->divisionName = $division->name;
        $this->divisionGradeId = $division->grade_id;
        $this->isDivisionModalOpen = true;
    }

    public function saveDivision()
    {
        $validated = $this->validate([
            'divisionName' => [
                'required',
                'string',
                'max:255',
                Rule::unique('divisions', 'name')
                    ->where('grade_id', $this->divisionGradeId)
                    ->ignore($this->divisionId),
            ],
            'divisionGradeId' => 'required|exists:grades,id',
        ], [
            'divisionName.required' => 'The division name is required.',
            'divisionName.unique' => 'This division name already exists under the selected grade.',
            'divisionGradeId.required' => 'Please select a grade.',
        ]);

        if ($this->divisionId) {
            $division = Division::findOrFail($this->divisionId);
            $division->update([
                'name' => $this->divisionName,
                'grade_id' => $this->divisionGradeId,
            ]);
        } else {
            Division::create([
                'name' => $this->divisionName,
                'grade_id' => $this->divisionGradeId,
            ]);
        }

        $this->isDivisionModalOpen = false;
        $this->loadDivisions();
    }

    public function confirmDivisionDeletion($id)
    {
        $this->divisionToDelete = $id;
        $this->confirmingDivisionDeletion = true;
    }

    public function deleteDivision()
    {
        if ($this->divisionToDelete) {
            Division::destroy($this->divisionToDelete);
            $this->divisionToDelete = null;
            $this->confirmingDivisionDeletion = false;
            $this->loadDivisions();
        }
    }
};

?>

<div class="space-y-8">
    <!-- Top Header Overview Card -->
    <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 sm:p-8 shadow-xl shadow-gray-200/40 dark:shadow-none border border-gray-150/40 dark:border-gray-700 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6">
        <div class="flex items-center space-x-4">
            <div class="p-3.5 bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 rounded-2xl">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
            </div>
            <div>
                <h3 class="text-xl font-extrabold text-gray-900 dark:text-gray-150">{{ __('Grade & Division Management') }}</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    {{ __('Manage school grades and division sections with relational mapping') }}
                </p>
            </div>
        </div>
        <div class="flex items-center gap-4">
            <div class="bg-gray-50 dark:bg-gray-900/60 border border-gray-100 dark:border-gray-850 px-4 py-2 rounded-2xl text-center">
                <span class="block text-2xl font-black text-indigo-600 dark:text-indigo-400">{{ count($grades) }}</span>
                <span class="text-[9px] uppercase font-black text-gray-400 tracking-wider">{{ __('Total Grades') }}</span>
            </div>
            <div class="bg-gray-50 dark:bg-gray-900/60 border border-gray-100 dark:border-gray-850 px-4 py-2 rounded-2xl text-center">
                <span class="block text-2xl font-black text-teal-600 dark:text-teal-400">{{ count($divisions) }}</span>
                <span class="text-[9px] uppercase font-black text-gray-400 tracking-wider">{{ __('Total Divisions') }}</span>
            </div>
        </div>
    </div>

    <!-- Dual Column Grid Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        
        <!-- Left Column: Grades CRUD -->
        <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 shadow-xl shadow-gray-200/40 dark:shadow-none border border-gray-100 dark:border-gray-700 flex flex-col space-y-6">
            <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-850 pb-4">
                <div>
                    <h4 class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ __('Grades') }}</h4>
                    <p class="text-xs text-gray-400 dark:text-gray-500">{{ __('Academic classes and standards') }}</p>
                </div>
                <button wire:click="openCreateGradeModal" class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-650 hover:bg-indigo-700 text-white font-bold text-xs uppercase tracking-wider rounded-xl transition shadow">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>{{ __('Add Grade') }}</span>
                </button>
            </div>

            <!-- Search Field -->
            <div class="relative">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                    <svg class="h-4 w-4 text-gray-405 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </span>
                <input type="text" wire:model.live.debounce.150ms="gradeSearch" placeholder="Search grades..." class="w-full pl-9 pr-4 py-2 text-sm border-gray-200 dark:border-gray-700 dark:bg-gray-900 rounded-xl dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600" />
            </div>

            <!-- List Grid -->
            <div class="flex-1 overflow-y-auto max-h-[500px] pr-1 space-y-3">
                @forelse ($grades as $grade)
                    <div class="group flex items-center justify-between p-4 bg-gray-50/50 dark:bg-gray-900/30 border border-gray-100/50 dark:border-gray-850 rounded-2xl hover:border-indigo-500/20 dark:hover:border-indigo-400/20 transition-all">
                        <div class="flex items-center space-x-3">
                            <span class="w-8 h-8 rounded-lg bg-indigo-50 dark:bg-indigo-950/40 text-indigo-655 dark:text-indigo-400 flex items-center justify-center font-bold text-sm">
                                G
                            </span>
                            <div>
                                <span class="font-bold text-gray-900 dark:text-gray-150 text-sm select-all">{{ $grade->name }}</span>
                                <span class="block text-[9px] text-gray-400 dark:text-gray-500 uppercase tracking-wider font-semibold">ID: #{{ $grade->id }}</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-1">
                            <button wire:click="openEditGradeModal({{ $grade->id }})" class="p-2 hover:bg-white dark:hover:bg-gray-900 rounded-xl text-gray-400 hover:text-indigo-605 transition-colors">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </button>
                            <button wire:click="confirmGradeDeletion({{ $grade->id }})" class="p-2 hover:bg-red-50 dark:hover:bg-red-950/10 rounded-xl text-gray-400 hover:text-red-600 transition-colors">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="py-12 text-center text-gray-400 dark:text-gray-500 text-xs">
                        {{ __('No grades found.') }}
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Right Column: Divisions CRUD -->
        <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 shadow-xl shadow-gray-200/40 dark:shadow-none border border-gray-100 dark:border-gray-700 flex flex-col space-y-6">
            <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-850 pb-4">
                <div>
                    <h4 class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ __('Divisions') }}</h4>
                    <p class="text-xs text-gray-400 dark:text-gray-500">{{ __('Class sections linked to grades') }}</p>
                </div>
                <button wire:click="openCreateDivisionModal" class="inline-flex items-center gap-1.5 px-4 py-2 bg-teal-650 hover:bg-teal-700 text-white font-bold text-xs uppercase tracking-wider rounded-xl transition shadow">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>{{ __('Add Division') }}</span>
                </button>
            </div>

            <!-- Search Field -->
            <div class="relative">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                    <svg class="h-4 w-4 text-gray-405 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </span>
                <input type="text" wire:model.live.debounce.150ms="divisionSearch" placeholder="Search divisions or grades..." class="w-full pl-9 pr-4 py-2 text-sm border-gray-200 dark:border-gray-700 dark:bg-gray-900 rounded-xl dark:text-gray-300 focus:border-teal-500 dark:focus:border-teal-600 focus:ring-teal-500 dark:focus:ring-teal-600" />
            </div>

            <!-- List Grid -->
            <div class="flex-1 overflow-y-auto max-h-[500px] pr-1 space-y-3">
                @forelse ($divisions as $division)
                    <div class="group flex items-center justify-between p-4 bg-gray-50/50 dark:bg-gray-900/30 border border-gray-100/50 dark:border-gray-850 rounded-2xl hover:border-teal-500/20 dark:hover:border-teal-400/20 transition-all">
                        <div class="flex items-center space-x-3">
                            <span class="w-8 h-8 rounded-lg bg-teal-50 dark:bg-teal-950/40 text-teal-655 dark:text-teal-400 flex items-center justify-center font-bold text-sm">
                                D
                            </span>
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-gray-900 dark:text-gray-150 text-sm select-all">{{ $division->name }}</span>
                                    <span class="px-2 py-0.5 rounded-md text-[9px] font-black uppercase tracking-wider bg-indigo-50 dark:bg-indigo-950 text-indigo-750 dark:text-indigo-305 border border-indigo-100/30 dark:border-indigo-900/20">
                                        {{ $division->grade?->name ?? 'No Grade' }}
                                    </span>
                                </div>
                                <span class="block text-[9px] text-gray-400 dark:text-gray-500 uppercase tracking-wider font-semibold">ID: #{{ $division->id }}</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-1">
                            <button wire:click="openEditDivisionModal({{ $division->id }})" class="p-2 hover:bg-white dark:hover:bg-gray-900 rounded-xl text-gray-400 hover:text-teal-605 transition-colors">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </button>
                            <button wire:click="confirmDivisionDeletion({{ $division->id }})" class="p-2 hover:bg-red-50 dark:hover:bg-red-950/10 rounded-xl text-gray-400 hover:text-red-650 transition-colors">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="py-12 text-center text-gray-400 dark:text-gray-500 text-xs">
                        {{ __('No divisions found.') }}
                    </div>
                @endforelse
            </div>
        </div>

    </div>

    <!-- --- GRADE MODALS --- -->

    <!-- Create/Edit Grade Modal -->
    @if ($isGradeModalOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-gray-950/60 backdrop-blur-sm transition-opacity" wire:click="$set('isGradeModalOpen', false)"></div>

            <!-- Modal Container -->
            <div class="bg-white dark:bg-gray-800 rounded-3xl overflow-hidden shadow-xl transform transition-all w-full max-w-md z-50 border border-gray-100 dark:border-gray-700">
                <form wire:submit="saveGrade" class="p-6 sm:p-8 space-y-6">
                    <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-gray-700">
                        <h3 class="text-base font-bold text-gray-900 dark:text-gray-100">
                            {{ $gradeId ? __('Edit Grade') : __('Add New Grade') }}
                        </h3>
                        <button type="button" wire:click="$set('isGradeModalOpen', false)" class="text-gray-400 hover:text-gray-500">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Input -->
                    <div>
                        <x-input-label for="gradeName" :value="__('Grade Name')" />
                        <x-text-input wire:model="gradeName" id="gradeName" type="text" class="mt-1 block w-full" required placeholder="e.g. Standard 1" />
                        <x-input-error :messages="$errors->get('gradeName')" class="mt-2" />
                    </div>

                    <!-- Footer Actions -->
                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                        <button type="button" wire:click="$set('isGradeModalOpen', false)" class="px-5 py-2.5 bg-transparent hover:bg-gray-50 dark:hover:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 font-bold text-xs uppercase tracking-wider rounded-xl transition">
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

    <!-- Delete Confirmation Modal (Grade) -->
    @if ($confirmingGradeDeletion)
        <div class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-gray-950/60 backdrop-blur-sm transition-opacity" wire:click="$set('confirmingGradeDeletion', false)"></div>

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
                            {{ __('Are you sure you want to permanently delete this grade? All data associated with it will be removed. This action cannot be undone.') }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                    <button type="button" wire:click="$set('confirmingGradeDeletion', false)" class="px-5 py-2.5 bg-transparent hover:bg-gray-50 dark:hover:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 font-bold text-xs uppercase tracking-wider rounded-xl transition">
                        {{ __('Cancel') }}
                    </button>
                    <button type="button" wire:click="deleteGrade" class="px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white font-bold text-xs uppercase tracking-wider rounded-xl transition shadow">
                        {{ __('Delete') }}
                    </button>
                </div>
            </div>
        </div>
    @endif


    <!-- --- DIVISION MODALS --- -->

    <!-- Create/Edit Division Modal -->
    @if ($isDivisionModalOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-gray-950/60 backdrop-blur-sm transition-opacity" wire:click="$set('isDivisionModalOpen', false)"></div>

            <!-- Modal Container -->
            <div class="bg-white dark:bg-gray-800 rounded-3xl overflow-hidden shadow-xl transform transition-all w-full max-w-md z-50 border border-gray-100 dark:border-gray-700">
                <form wire:submit="saveDivision" class="p-6 sm:p-8 space-y-6">
                    <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-gray-700">
                        <h3 class="text-base font-bold text-gray-900 dark:text-gray-100">
                            {{ $divisionId ? __('Edit Division') : __('Add New Division') }}
                        </h3>
                        <button type="button" wire:click="$set('isDivisionModalOpen', false)" class="text-gray-400 hover:text-gray-500">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Input: Grade selection -->
                    <div>
                        <x-input-label for="divisionGradeId" :value="__('Select Grade')" />
                        <select wire:model="divisionGradeId" id="divisionGradeId" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-teal-500 dark:focus:border-teal-600 focus:ring-teal-500 dark:focus:ring-teal-600 rounded-xl shadow-sm" required>
                            <option value="">Choose Grade</option>
                            @foreach ($grades as $g)
                                <option value="{{ $g->id }}">{{ $g->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('divisionGradeId')" class="mt-2" />
                    </div>

                    <!-- Input: Division name -->
                    <div>
                        <x-input-label for="divisionName" :value="__('Division Name')" />
                        <x-text-input wire:model="divisionName" id="divisionName" type="text" class="mt-1 block w-full" required placeholder="e.g. Division A" />
                        <x-input-error :messages="$errors->get('divisionName')" class="mt-2" />
                    </div>

                    <!-- Footer Actions -->
                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                        <button type="button" wire:click="$set('isDivisionModalOpen', false)" class="px-5 py-2.5 bg-transparent hover:bg-gray-50 dark:hover:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 font-bold text-xs uppercase tracking-wider rounded-xl transition">
                            {{ __('Cancel') }}
                        </button>
                        <button type="submit" class="px-5 py-2.5 bg-teal-655 hover:bg-teal-705 text-white font-bold text-xs uppercase tracking-wider rounded-xl transition shadow">
                            {{ __('Save') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Delete Confirmation Modal (Division) -->
    @if ($confirmingDivisionDeletion)
        <div class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-gray-950/60 backdrop-blur-sm transition-opacity" wire:click="$set('confirmingDivisionDeletion', false)"></div>

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
                            {{ __('Delete Division') }}
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-2 leading-relaxed">
                            {{ __('Are you sure you want to permanently delete this division? All data associated with it will be removed. This action cannot be undone.') }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                    <button type="button" wire:click="$set('confirmingDivisionDeletion', false)" class="px-5 py-2.5 bg-transparent hover:bg-gray-50 dark:hover:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 font-bold text-xs uppercase tracking-wider rounded-xl transition">
                        {{ __('Cancel') }}
                    </button>
                    <button type="button" wire:click="deleteDivision" class="px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white font-bold text-xs uppercase tracking-wider rounded-xl transition shadow">
                        {{ __('Delete') }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
