<?php

use Livewire\Volt\Component;
use App\Models\User;
use App\Models\Role;
use App\Models\School;
use App\Models\Grade;
use App\Models\Division;
use App\Models\SchoolUserRole;
use Illuminate\Validation\Rule;

new class extends Component {
    // Search
    public $search = '';

    // Model properties
    public $assignments = [];
    public $assignmentId = null;

    // Form inputs
    public $userId = null;
    public $roleSlug = '';
    public $gradeId = null;
    public $divisionId = null;

    // Mode: Select user vs Create user
    public $isNewUserMode = false;
    public $newUserName = '';
    public $newUserEmail = '';
    public $newUserMobile = '';
    public $newUserPassword = '';

    // Modal state
    public $isModalOpen = false;
    public $confirmingDeletion = false;
    public $assignmentToDelete = null;

    public function mount()
    {
        $this->loadAssignments();
    }

    public function updatedSearch()
    {
        $this->loadAssignments();
    }

    public function updatedRoleSlug($value)
    {
        if ($value !== 'teacher') {
            $this->gradeId = null;
            $this->divisionId = null;
        }
    }

    public function updatedGradeId($value)
    {
        $this->divisionId = null;
    }

    public function loadAssignments()
    {
        $activeSchoolId = session('active_school_id');
        if (!$activeSchoolId) {
            $this->assignments = [];
            return;
        }

        $query = SchoolUserRole::with(['user', 'role', 'grade', 'division'])
            ->where('school_id', $activeSchoolId);

        if ($this->search) {
            $query->whereHas('user', function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%')
                  ->orWhere('mobile', 'like', '%' . $this->search . '%');
            });
        }

        $this->assignments = $query->latest()->get();
    }

    public function openCreateModal()
    {
        if (!session('active_school_id')) {
            $this->addError('userId', 'Please select a school first.');
            return;
        }

        $this->resetValidation();
        $this->reset([
            'assignmentId', 'userId', 'roleSlug', 'gradeId', 'divisionId',
            'isNewUserMode', 'newUserName', 'newUserEmail', 'newUserMobile', 'newUserPassword'
        ]);
        $this->isModalOpen = true;
    }

    public function openEditModal($id)
    {
        $this->resetValidation();
        $assignment = SchoolUserRole::findOrFail($id);

        $this->assignmentId = $assignment->id;
        $this->userId = $assignment->user_id;
        
        $role = Role::findOrFail($assignment->role_id);
        $this->roleSlug = $role->slug;
        
        $this->gradeId = $assignment->grade_id;
        $this->divisionId = $assignment->division_id;
        
        $this->isNewUserMode = false;
        $this->isModalOpen = true;
    }

    public function saveAssignment()
    {
        $activeSchoolId = session('active_school_id');
        if (!$activeSchoolId) {
            $this->addError('roleSlug', 'Please select a school first.');
            return;
        }

        $role = Role::whereIn('slug', ['school_admin', 'teacher'])->where('slug', $this->roleSlug)->first();
        if (!$role) {
            $this->addError('roleSlug', 'Please select a valid role.');
            return;
        }

        $userIdToSave = $this->userId;

        if ($this->isNewUserMode) {
            $validatedUser = $this->validate([
                'newUserName' => 'required|string|max:255',
                'newUserEmail' => 'required|email|max:255|unique:users,email',
                'newUserMobile' => 'required|string|max:20|unique:users,mobile',
                'newUserPassword' => 'required|string|min:8',
            ], [
                'newUserName.required' => 'User name is required.',
                'newUserEmail.required' => 'Email is required.',
                'newUserEmail.unique' => 'This email already exists.',
                'newUserMobile.required' => 'Mobile number is required.',
                'newUserMobile.unique' => 'This mobile number already exists.',
                'newUserPassword.required' => 'Password is required.',
                'newUserPassword.min' => 'Password must be at least 8 characters.',
            ]);

            $newUser = User::create([
                'name' => $this->newUserName,
                'email' => $this->newUserEmail,
                'mobile' => $this->newUserMobile,
                'password' => bcrypt($this->newUserPassword),
            ]);

            $userIdToSave = $newUser->id;
        } else {
            $this->validate([
                'userId' => 'required|exists:users,id',
            ], [
                'userId.required' => 'Please select a user.',
            ]);
        }

        // Validate teacher context details
        if ($this->roleSlug === 'teacher') {
            $this->validate([
                'gradeId' => 'required|exists:grades,id',
                'divisionId' => 'required|exists:divisions,id',
            ], [
                'gradeId.required' => 'Grade assignment is required for teachers.',
                'divisionId.required' => 'Division assignment is required for teachers.',
            ]);
        }

        // Validate duplicates for user-school-role combination
        $duplicateCheck = SchoolUserRole::where('school_id', $activeSchoolId)
            ->where('user_id', $userIdToSave)
            ->where('role_id', $role->id)
            ->where('grade_id', $this->gradeId)
            ->where('division_id', $this->divisionId)
            ->where('id', '!=', $this->assignmentId)
            ->exists();

        if ($duplicateCheck) {
            $this->addError('roleSlug', 'This user already has this exact assignment under this school.');
            return;
        }

        // Save Assignment
        $assignment = SchoolUserRole::updateOrCreate(
            ['id' => $this->assignmentId],
            [
                'user_id' => $userIdToSave,
                'school_id' => $activeSchoolId,
                'role_id' => $role->id,
                'grade_id' => $this->gradeId,
                'division_id' => $this->divisionId,
            ]
        );

        // Sync role to user's standard roles pivot table
        $user = User::find($userIdToSave);
        $user->roles()->syncWithoutDetaching([$role->id]);

        $this->isModalOpen = false;
        $this->loadAssignments();
    }

    public function confirmDeletion($id)
    {
        $this->assignmentToDelete = $id;
        $this->confirmingDeletion = true;
    }

    public function deleteAssignment()
    {
        if ($this->assignmentToDelete) {
            $assignment = SchoolUserRole::findOrFail($this->assignmentToDelete);
            $assignment->delete();

            $this->assignmentToDelete = null;
            $this->confirmingDeletion = false;
            $this->loadAssignments();
        }
    }
};

?>

<div class="space-y-8">
    <!-- Top Header Card -->
    <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 sm:p-8 shadow-xl shadow-gray-200/40 dark:shadow-none border border-gray-100 dark:border-gray-700 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6">
        <div class="flex items-center space-x-4">
            <div class="p-3.5 bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 rounded-2xl">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                </svg>
            </div>
            <div>
                <h3 class="text-xl font-extrabold text-gray-900 dark:text-gray-100">{{ __('User Roles & Assignments') }}</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    {{ __('Assign administrators and class teachers to specific grades and division sections') }}
                </p>
            </div>
        </div>
        <div>
            <button wire:click="openCreateModal" class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs uppercase tracking-wider rounded-xl transition shadow">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                </svg>
                <span>{{ __('Add Assignment') }}</span>
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
        <input type="text" wire:model.live.debounce.150ms="search" placeholder="Search assigned users by name, email or mobile..." class="w-full pl-10 pr-4 py-3 bg-white dark:bg-gray-800 border-gray-100 dark:border-gray-700 rounded-2xl dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 shadow-md shadow-gray-200/20 dark:shadow-none" />
    </div>

    <!-- Listings Grid of Assigned Users -->
    <div class="flex flex-col gap-6">
        @forelse ($assignments as $assign)
            <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 shadow-xl shadow-gray-200/40 dark:shadow-none border border-gray-100 dark:border-gray-700 hover:border-indigo-500/30 dark:hover:border-indigo-400/20 transition-all duration-300 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6 group">
                <div class="flex items-center space-x-4">
                    <span class="w-12 h-12 rounded-2xl bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-black text-lg shadow-sm">
                        {{ strtoupper(substr($assign->user->name, 0, 1)) }}
                    </span>
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <h4 class="font-extrabold text-gray-900 dark:text-gray-100 text-base leading-none">
                                {{ $assign->user->name }}
                            </h4>
                            <!-- Role Badge -->
                            <span class="px-2 py-0.5 rounded text-[8px] font-black uppercase tracking-wider {{ $assign->role->slug === 'school_admin' ? 'bg-purple-50 dark:bg-purple-950/40 text-purple-700 dark:text-purple-300 border border-purple-100/50 dark:border-purple-900/30' : 'bg-teal-50 dark:bg-teal-950/40 text-teal-700 dark:text-teal-300 border border-teal-100/50 dark:border-teal-900/30' }}">
                                {{ $assign->role->name }}
                            </span>
                        </div>
                        <div class="text-[10px] text-gray-400 dark:text-gray-500 font-semibold space-x-2">
                            <span>{{ $assign->user->email }}</span>
                            <span>&bull;</span>
                            <span>{{ $assign->user->mobile }}</span>
                        </div>
                    </div>
                </div>

                <!-- Assignment/Grade details -->
                <div class="flex-1 flex flex-wrap items-center gap-2 sm:px-6">
                    @if ($assign->role->slug === 'teacher' && $assign->grade && $assign->division)
                        <div class="flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-bold bg-indigo-50/50 dark:bg-indigo-950/20 text-indigo-600 dark:text-indigo-400 border border-indigo-100/40 dark:border-indigo-900/20 shadow-sm">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z" />
                            </svg>
                            <span>{{ $assign->grade->name }} - {{ $assign->division->name }}</span>
                        </div>
                    @else
                        <div class="flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-bold bg-gray-50 dark:bg-gray-900/60 text-gray-500 dark:text-gray-400 border border-gray-100 dark:border-gray-700/60 shadow-sm">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                            <span>{{ __('Global School Access') }}</span>
                        </div>
                    @endif
                </div>

                <!-- Actions -->
                <div class="flex items-center gap-1.5 shrink-0 self-end sm:self-auto border-t sm:border-t-0 pt-4 sm:pt-0 w-full sm:w-auto justify-end">
                    <button wire:click="openEditModal({{ $assign->id }})" class="p-2.5 hover:bg-gray-50 dark:hover:bg-gray-900 rounded-xl text-gray-400 hover:text-indigo-600 dark:text-gray-500 dark:hover:text-indigo-400 transition-colors">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </button>
                    <button wire:click="confirmDeletion({{ $assign->id }})" class="p-2.5 hover:bg-red-50 dark:hover:bg-red-950/20 rounded-xl text-gray-400 hover:text-red-605 dark:text-gray-500 dark:hover:text-red-400 transition-colors">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </div>
            </div>
        @empty
            <div class="bg-white dark:bg-gray-800 rounded-3xl p-12 text-center text-gray-400 dark:text-gray-500 border border-gray-100 dark:border-gray-700">
                {{ __('No assignments found for this school profile.') }}
            </div>
        @endforelse
    </div>

    <!-- Create/Edit Modal -->
    @if ($isModalOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-gray-950/60 backdrop-blur-sm transition-opacity" wire:click="$set('isModalOpen', false)"></div>

            <!-- Modal Container -->
            <div class="bg-white dark:bg-gray-800 rounded-3xl overflow-hidden shadow-xl transform transition-all w-full max-w-lg z-50 border border-gray-100 dark:border-gray-700">
                <form wire:submit="saveAssignment" class="p-6 sm:p-8 space-y-5">
                    <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-gray-700">
                        <h3 class="text-base font-bold text-gray-900 dark:text-gray-100">
                            {{ $assignmentId ? __('Edit Assignment') : __('Add New Assignment') }}
                        </h3>
                        <button type="button" wire:click="$set('isModalOpen', false)" class="text-gray-400 hover:text-gray-500">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- User Mode Selection Toggle -->
                    @if (!$assignmentId)
                        <div class="flex bg-gray-50 dark:bg-gray-900/50 p-1.5 rounded-2xl border border-gray-100 dark:border-gray-800">
                            <button type="button" wire:click="$set('isNewUserMode', false)" class="flex-1 text-center py-2 text-xs font-bold uppercase tracking-wider rounded-xl transition {{ !$isNewUserMode ? 'bg-indigo-600 text-white shadow' : 'text-gray-550 hover:text-gray-900 dark:hover:text-gray-100' }}">
                                {{ __('Select User') }}
                            </button>
                            <button type="button" wire:click="$set('isNewUserMode', true)" class="flex-1 text-center py-2 text-xs font-bold uppercase tracking-wider rounded-xl transition {{ $isNewUserMode ? 'bg-indigo-600 text-white shadow' : 'text-gray-550 hover:text-gray-900 dark:hover:text-gray-100' }}">
                                {{ __('Create User') }}
                            </button>
                        </div>
                    @endif

                    <!-- Select User Mode -->
                    @if (!$isNewUserMode)
                        <div>
                            <x-input-label for="userId" :value="__('Select User Account')" />
                            <select wire:model="userId" id="userId" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-xl shadow-sm" required {{ $assignmentId ? 'disabled' : '' }}>
                                <option value="">Select User</option>
                                @foreach (User::orderBy('name', 'asc')->get() as $u)
                                    <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('userId')" class="mt-1" />
                        </div>
                    @else
                        <!-- Create User Mode Form Fields -->
                        <div class="space-y-4 bg-gray-50/50 dark:bg-gray-900/20 p-4 rounded-2xl border border-gray-150/40 dark:border-gray-800">
                            <div>
                                <x-input-label for="newUserName" :value="__('Full Name')" />
                                <x-text-input wire:model="newUserName" id="newUserName" type="text" class="mt-1 block w-full" placeholder="e.g. John Doe" />
                                <x-input-error :messages="$errors->get('newUserName')" class="mt-1" />
                            </div>
                            <div>
                                <x-input-label for="newUserEmail" :value="__('Email Address')" />
                                <x-text-input wire:model="newUserEmail" id="newUserEmail" type="email" class="mt-1 block w-full" placeholder="e.g. john@school.com" />
                                <x-input-error :messages="$errors->get('newUserEmail')" class="mt-1" />
                            </div>
                            <div>
                                <x-input-label for="newUserMobile" :value="__('Mobile Number')" />
                                <x-text-input wire:model="newUserMobile" id="newUserMobile" type="text" class="mt-1 block w-full" placeholder="e.g. 9876543210" />
                                <x-input-error :messages="$errors->get('newUserMobile')" class="mt-1" />
                            </div>
                            <div>
                                <x-input-label for="newUserPassword" :value="__('Password')" />
                                <x-text-input wire:model="newUserPassword" id="newUserPassword" type="password" class="mt-1 block w-full" placeholder="Min 8 characters" />
                                <x-input-error :messages="$errors->get('newUserPassword')" class="mt-1" />
                            </div>
                        </div>
                    @endif

                    <!-- Select Role -->
                    <div>
                        <x-input-label for="roleSlug" :value="__('Assign Role')" />
                        <select wire:model.live="roleSlug" id="roleSlug" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-xl shadow-sm" required>
                            <option value="">Select Role</option>
                            <option value="school_admin">School Admin</option>
                            <option value="teacher">Teacher</option>
                        </select>
                        <x-input-error :messages="$errors->get('roleSlug')" class="mt-1" />
                    </div>

                    <!-- Teacher Grade & Division Scopes (Visible only for Teacher role) -->
                    @if ($roleSlug === 'teacher')
                        <div class="grid grid-cols-2 gap-4 bg-indigo-50/20 dark:bg-indigo-950/10 p-4 rounded-2xl border border-indigo-100/30 dark:border-indigo-900/10">
                            <!-- Grade -->
                            <div>
                                <x-input-label for="gradeId" :value="__('Assign Grade')" />
                                <select wire:model.live="gradeId" id="gradeId" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-xl shadow-sm" required>
                                    <option value="">Select Grade</option>
                                    @foreach (Grade::where('school_id', session('active_school_id'))->orderBy('name', 'asc')->get() as $g)
                                        <option value="{{ $g->id }}">{{ $g->name }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('gradeId')" class="mt-1" />
                            </div>

                            <!-- Division -->
                            <div>
                                <x-input-label for="divisionId" :value="__('Assign Division')" />
                                <select wire:model="divisionId" id="divisionId" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-xl shadow-sm" required>
                                    <option value="">Select Division</option>
                                    @php
                                        $selectedGrade = Grade::find($gradeId);
                                        $divs = $selectedGrade ? $selectedGrade->divisions : collect();
                                    @endphp
                                    @foreach ($divs as $d)
                                        <option value="{{ $d->id }}">{{ $d->name }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('divisionId')" class="mt-1" />
                            </div>
                        </div>
                    @endif

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
                    <div class="p-3 bg-red-50 dark:bg-red-950/40 text-red-655 dark:text-red-400 rounded-2xl shrink-0">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-gray-100">
                            {{ __('Delete Assignment') }}
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-2 leading-relaxed">
                            {{ __('Are you sure you want to permanently delete this user role assignment? This user will no longer be authorized as an administrator or class teacher for this scope.') }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                    <button type="button" wire:click="$set('confirmingDeletion', false)" class="px-5 py-2.5 bg-transparent hover:bg-gray-50 dark:hover:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 font-bold text-xs uppercase tracking-wider rounded-xl transition">
                        {{ __('Cancel') }}
                    </button>
                    <button type="button" wire:click="deleteAssignment" class="px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white font-bold text-xs uppercase tracking-wider rounded-xl transition shadow">
                        {{ __('Delete') }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
