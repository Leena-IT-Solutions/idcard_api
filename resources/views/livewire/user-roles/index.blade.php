<?php

use Livewire\Volt\Component;
use App\Models\User;
use App\Models\Role;
use App\Models\School;
use App\Models\Grade;
use App\Models\Division;
use App\Models\SchoolUserRole;
use App\Models\SchoolInvitation;
use App\Models\SchoolUserRoleAssignment;
use App\Models\SchoolInvitationAssignment;
use Illuminate\Validation\Rule;

new class extends Component {
    // Search
    public $search = '';

    // Model properties
    public $activeMembers = [];
    public $pendingInvites = [];
    public $assignmentId = null;

    // Form inputs
    public $inviteEmail = '';
    public $inviteMobile = '';
    public $roleSlug = '';
    public $gradeId = null;
    public $divisionId = null;
    public array $tempAssignments = [];

    // Modal state
    public $isModalOpen = false;
    public $confirmingDeletion = false;

    // Edit Member Properties
    public $editingMemberId = null;
    public $isEditModalOpen = false;
    public $memberToDelete = null;

    public $confirmingRevocation = false;
    public $inviteToRevoke = null;

    // Pagination properties
    public $perPage = 6;
    public $hasMore = false;

    public function mount()
    {
        $this->loadData();
    }

    public function updatedSearch()
    {
        $this->perPage = 6;
        $this->loadData();
    }

    public function loadMore()
    {
        $this->perPage += 6;
        $this->loadData();
    }

    public function updatedRoleSlug($value)
    {
        if ($value !== 'teacher') {
            $this->gradeId = null;
            $this->divisionId = null;
            $this->tempAssignments = [];
        }
    }

    public function updatedGradeId($value)
    {
        $this->divisionId = null;
    }

    public function addAssignment()
    {
        $this->validate([
            'gradeId' => 'required|exists:grades,id',
            'divisionId' => 'required|exists:divisions,id',
        ], [
            'gradeId.required' => 'Please select a grade.',
            'divisionId.required' => 'Please select a division.',
        ]);

        $grade = Grade::find($this->gradeId);
        $division = Division::find($this->divisionId);

        // Check duplicates in temp list
        foreach ($this->tempAssignments as $assign) {
            if ($assign['grade_id'] == $this->gradeId && $assign['division_id'] == $this->divisionId) {
                $this->addError('divisionId', 'This assignment is already added.');
                return;
            }
        }

        $this->tempAssignments[] = [
            'grade_id' => $grade->id,
            'grade_name' => $grade->name,
            'division_id' => $division->id,
            'division_name' => $division->name,
        ];

        // Reset dropdown inputs for next assignment
        $this->gradeId = null;
        $this->divisionId = null;
        $this->resetErrorBag(['gradeId', 'divisionId']);
    }

    public function removeAssignment($index)
    {
        if (isset($this->tempAssignments[$index])) {
            unset($this->tempAssignments[$index]);
            $this->tempAssignments = array_values($this->tempAssignments);
        }
    }

    public function loadData()
    {
        $activeSchoolId = session('active_school_id');
        if (!$activeSchoolId) {
            $this->activeMembers = [];
            $this->pendingInvites = [];
            return;
        }

        // 1. Load active members
        $memberQuery = SchoolUserRole::with(['user', 'role', 'assignments.grade', 'assignments.division'])
            ->where('school_id', $activeSchoolId);

        if ($this->search) {
            $memberQuery->whereHas('user', function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%')
                  ->orWhere('mobile', 'like', '%' . $this->search . '%');
            });
        }
        
        $totalCount = $memberQuery->count();
        $this->activeMembers = $memberQuery->latest()->take($this->perPage)->get();
        $this->hasMore = $totalCount > $this->perPage;

        // 2. Load pending invites
        $inviteQuery = SchoolInvitation::with(['role', 'assignments.grade', 'assignments.division', 'user'])
            ->where('school_id', $activeSchoolId)
            ->where('status', 'pending');

        if ($this->search) {
            $inviteQuery->where(function ($q) {
                $q->where('email', 'like', '%' . $this->search . '%')
                  ->orWhere('mobile', 'like', '%' . $this->search . '%')
                  ->orWhereHas('user', function ($sub) {
                      $sub->where('name', 'like', '%' . $this->search . '%');
                  });
            });
        }
        $this->pendingInvites = $inviteQuery->latest()->get();
    }

    public function openCreateModal()
    {
        if (!session('active_school_id')) {
            $this->addError('roleSlug', 'Please select a school first.');
            return;
        }

        $this->resetValidation();
        $this->reset([
            'inviteEmail', 'inviteMobile', 'roleSlug', 'gradeId', 'divisionId', 'tempAssignments'
        ]);
        $this->isModalOpen = true;
    }

    public function sendInvitation()
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

        // Validate that at least one of email or mobile is entered
        if (empty(trim($this->inviteEmail)) && empty(trim($this->inviteMobile))) {
            $this->addError('inviteEmail', 'You must provide either an Email ID or a Mobile Number to send an invitation.');
            $this->addError('inviteMobile', 'You must provide either an Email ID or a Mobile Number to send an invitation.');
            return;
        }

        $this->validate([
            'inviteEmail' => 'nullable|email|max:255',
            'inviteMobile' => 'nullable|string|max:20',
        ]);

        if ($this->roleSlug === 'teacher') {
            if (empty($this->tempAssignments)) {
                $this->addError('roleSlug', 'At least one Grade and Division assignment is required for teachers.');
                return;
            }
        }

        // Check if user already exists
        $user = null;
        if (!empty($this->inviteEmail)) {
            $user = User::where('email', trim($this->inviteEmail))->first();
        }
        if (!$user && !empty($this->inviteMobile)) {
            $user = User::where('mobile', trim($this->inviteMobile))->first();
        }

        if ($user) {
            // Check if user is already an active member of this school with this role
            $alreadyActive = SchoolUserRole::where('school_id', $activeSchoolId)
                ->where('user_id', $user->id)
                ->where('role_id', $role->id)
                ->exists();

            if ($alreadyActive) {
                $this->addError('roleSlug', 'This user is already an active member with this role in this school.');
                return;
            }
        }

        // Check for duplicate pending invitation
        $pendingQuery = SchoolInvitation::where('school_id', $activeSchoolId)
            ->where('role_id', $role->id)
            ->where('status', 'pending');

        if (!empty($this->inviteEmail)) {
            $pendingQuery->where(function($q) {
                $q->where('email', trim($this->inviteEmail))
                  ->orWhere('mobile', trim($this->inviteMobile));
            });
        } else {
            $pendingQuery->where('mobile', trim($this->inviteMobile));
        }

        if ($pendingQuery->exists()) {
            $this->addError('roleSlug', 'A pending invitation already exists for this contact with the same role.');
            return;
        }

        // Create Invitation
        $invitation = SchoolInvitation::create([
            'school_id' => $activeSchoolId,
            'role_id' => $role->id,
            'grade_id' => ($this->roleSlug === 'teacher' && !empty($this->tempAssignments)) ? $this->tempAssignments[0]['grade_id'] : null,
            'division_id' => ($this->roleSlug === 'teacher' && !empty($this->tempAssignments)) ? $this->tempAssignments[0]['division_id'] : null,
            'email' => !empty($this->inviteEmail) ? trim($this->inviteEmail) : null,
            'mobile' => !empty($this->inviteMobile) ? trim($this->inviteMobile) : null,
            'user_id' => $user ? $user->id : null,
            'status' => 'pending',
        ]);

        if ($this->roleSlug === 'teacher') {
            foreach ($this->tempAssignments as $assign) {
                SchoolInvitationAssignment::create([
                    'school_invitation_id' => $invitation->id,
                    'grade_id' => $assign['grade_id'],
                    'division_id' => $assign['division_id'],
                ]);
            }
        }

        $this->isModalOpen = false;
        $this->loadData();
    }

    public function confirmDeletion($id)
    {
        $this->memberToDelete = $id;
        $this->confirmingDeletion = true;
    }

    public function deleteMember()
    {
        if ($this->memberToDelete) {
            $assignment = SchoolUserRole::findOrFail($this->memberToDelete);
            $assignment->delete();

            $this->memberToDelete = null;
            $this->confirmingDeletion = false;
            $this->loadData();
        }
    }

    public function confirmRevocation($id)
    {
        $this->inviteToRevoke = $id;
        $this->confirmingRevocation = true;
    }

    public function revokeInvite()
    {
        if ($this->inviteToRevoke) {
            $invitation = SchoolInvitation::findOrFail($this->inviteToRevoke);
            $invitation->delete();

            $this->inviteToRevoke = null;
            $this->confirmingRevocation = false;
            $this->loadData();
        }
    }

    public function openEditModal($id)
    {
        $member = SchoolUserRole::with(['role', 'assignments.grade', 'assignments.division'])->findOrFail($id);
        $this->editingMemberId = $id;
        $this->roleSlug = $member->role->slug;
        $this->tempAssignments = [];
        foreach ($member->assignments as $asg) {
            $this->tempAssignments[] = [
                'grade_id' => $asg->grade_id,
                'grade_name' => $asg->grade->name,
                'division_id' => $asg->division_id,
                'division_name' => $asg->division->name,
            ];
        }
        $this->resetValidation();
        $this->isEditModalOpen = true;
    }

    public function saveEdit()
    {
        $member = SchoolUserRole::findOrFail($this->editingMemberId);
        $role = Role::whereIn('slug', ['school_admin', 'teacher'])->where('slug', $this->roleSlug)->firstOrFail();

        if ($this->roleSlug === 'teacher') {
            if (empty($this->tempAssignments)) {
                $this->addError('roleSlug', 'At least one Grade and Division assignment is required for teachers.');
                return;
            }
        }

        $member->update(['role_id' => $role->id]);

        // Sync class assignments
        $member->assignments()->delete();
        if ($this->roleSlug === 'teacher') {
            foreach ($this->tempAssignments as $assign) {
                SchoolUserRoleAssignment::create([
                    'school_user_role_id' => $member->id,
                    'grade_id' => $assign['grade_id'],
                    'division_id' => $assign['division_id'],
                ]);
            }
        }

        $this->isEditModalOpen = false;
        $this->loadData();
    }
};

?>

<div class="space-y-12">
    <!-- Top Header Card -->
    <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 sm:p-8 shadow-xl shadow-gray-200/40 dark:shadow-none border border-gray-100 dark:border-gray-700 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6">
        <div class="flex items-center space-x-4">
            <div class="p-3.5 bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 rounded-2xl">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                </svg>
            </div>
            <div>
                <h3 class="text-xl font-extrabold text-gray-900 dark:text-gray-100">{{ __('User Roles & Members') }}</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    {{ __('Invite administrators and teachers to join this school profile context securely') }}
                </p>
            </div>
        </div>
        <div>
            <button wire:click="openCreateModal" class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs uppercase tracking-wider rounded-xl transition shadow">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                </svg>
                <span>{{ __('Invite Member') }}</span>
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
        <input type="text" wire:model.live.debounce.150ms="search" placeholder="Search members or invites by name, email or mobile..." class="w-full pl-10 pr-4 py-3 bg-white dark:bg-gray-800 border-gray-100 dark:border-gray-700 rounded-2xl dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 shadow-md shadow-gray-200/20 dark:shadow-none" />
    </div>

    <!-- Active Members Section -->
    <div class="space-y-4">
        <div class="flex items-center space-x-2 px-1">
            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
            <h4 class="text-xs font-black uppercase text-gray-400 dark:text-gray-500 tracking-wider">
                {{ __('Active Members') }} ({{ count($activeMembers) }})
            </h4>
        </div>

        <div class="flex flex-col gap-6">
            @forelse ($activeMembers as $assign)
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
                                <span class="px-2 py-0.5 rounded text-[8px] font-black uppercase tracking-wider {{ $assign->role->slug === 'school_admin' ? 'bg-purple-50 dark:bg-purple-950/40 text-purple-700 dark:text-purple-300 border border-purple-100/50 dark:border-purple-900/30' : 'bg-teal-50 dark:bg-teal-500/10 text-teal-700 dark:text-teal-400 border border-teal-100 dark:border-teal-500/20' }}">
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

                    <div class="flex-1 flex flex-wrap items-center gap-2 sm:px-6">
                        @if ($assign->role->slug === 'teacher' && count($assign->assignments) > 0)
                            @foreach ($assign->assignments as $asg)
                                <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-indigo-50/50 dark:bg-indigo-950/20 text-indigo-600 dark:text-indigo-400 border border-indigo-100/40 dark:border-indigo-900/20 shadow-sm">
                                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z" />
                                    </svg>
                                    <span>{{ $asg->grade->name }} - {{ $asg->division->name }}</span>
                                </div>
                            @endforeach
                        @elseif ($assign->role->slug === 'teacher' && $assign->grade && $assign->division)
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

                    <div class="flex items-center gap-1.5 shrink-0 self-end sm:self-auto border-t sm:border-t-0 pt-4 sm:pt-0 w-full sm:w-auto justify-end">
                        <button wire:click="openEditModal({{ $assign->id }})" class="p-2.5 hover:bg-indigo-50 dark:hover:bg-indigo-950/20 rounded-xl text-gray-400 hover:text-indigo-600 dark:text-gray-500 dark:hover:text-indigo-400 transition-colors" title="Edit Member">
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
                <div class="bg-white dark:bg-gray-800 rounded-3xl p-8 text-center text-gray-400 dark:text-gray-500 border border-gray-100 dark:border-gray-700">
                    {{ __('No active school members found.') }}
                </div>
            @endforelse
        </div>

        @if ($hasMore)
            <div class="flex justify-center pt-2">
                <button wire:click="loadMore" class="px-6 py-3 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/60 text-gray-700 dark:text-gray-300 font-extrabold text-xs uppercase tracking-wider rounded-2xl transition shadow-sm flex items-center gap-2 cursor-pointer">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 13l-7 7-7-7m14-6l-7 7-7-7"/>
                    </svg>
                    {{ __('Load More') }}
                </button>
            </div>
        @endif
    </div>

    <!-- Pending Invites Section -->
    <div class="space-y-4">
        <div class="flex items-center space-x-2 px-1">
            <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
            <h4 class="text-xs font-black uppercase text-gray-400 dark:text-gray-500 tracking-wider">
                {{ __('Pending Invitations') }} ({{ count($pendingInvites) }})
            </h4>
        </div>

        <div class="flex flex-col gap-6">
            @forelse ($pendingInvites as $invite)
                <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 shadow-xl shadow-gray-200/40 dark:shadow-none border border-gray-100 dark:border-gray-700 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6 group">
                    <div class="flex items-center space-x-4">
                        <span class="w-12 h-12 rounded-2xl bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 flex items-center justify-center font-black text-lg shadow-sm">
                            ?
                        </span>
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <h4 class="font-extrabold text-gray-900 dark:text-gray-100 text-base leading-none">
                                    {{ $invite->user ? $invite->user->name : __('Unregistered User') }}
                                </h4>
                                <span class="px-2 py-0.5 rounded text-[8px] font-black uppercase tracking-wider {{ $invite->role->slug === 'school_admin' ? 'bg-purple-50 dark:bg-purple-950/40 text-purple-700 dark:text-purple-300 border border-purple-100/50 dark:border-purple-900/30' : 'bg-teal-50 dark:bg-teal-500/10 text-teal-700 dark:text-teal-400 border border-teal-100 dark:border-teal-500/20' }}">
                                    {{ $invite->role->name }}
                                </span>
                            </div>
                            <div class="text-[10px] text-gray-400 dark:text-gray-500 font-semibold space-x-2">
                                <span>{{ $invite->email ?? 'No Email' }}</span>
                                <span>&bull;</span>
                                <span>{{ $invite->mobile ?? 'No Mobile' }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex-1 flex flex-wrap items-center gap-2 sm:px-6">
                        @if ($invite->role->slug === 'teacher' && count($invite->assignments) > 0)
                            @foreach ($invite->assignments as $asg)
                                <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-indigo-50/50 dark:bg-indigo-950/20 text-indigo-600 dark:text-indigo-400 border border-indigo-100/40 dark:border-indigo-900/20 shadow-sm">
                                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z" />
                                    </svg>
                                    <span>{{ $asg->grade->name }} - {{ $asg->division->name }}</span>
                                </div>
                            @endforeach
                        @elseif ($invite->role->slug === 'teacher' && $invite->grade && $invite->division)
                            <div class="flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-bold bg-indigo-50/50 dark:bg-indigo-950/20 text-indigo-600 dark:text-indigo-400 border border-indigo-100/40 dark:border-indigo-900/20 shadow-sm">
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z" />
                                </svg>
                                <span>{{ $invite->grade->name }} - {{ $invite->division->name }}</span>
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

                    <div class="flex items-center gap-1.5 shrink-0 self-end sm:self-auto border-t sm:border-t-0 pt-4 sm:pt-0 w-full sm:w-auto justify-end">
                        <button wire:click="confirmRevocation({{ $invite->id }})" class="p-2.5 hover:bg-amber-50 dark:hover:bg-amber-950/20 rounded-xl text-gray-400 hover:text-amber-600 dark:text-gray-500 dark:hover:text-amber-400 transition-colors" title="Revoke Invite">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </button>
                    </div>
                </div>
            @empty
                <div class="bg-white dark:bg-gray-800 rounded-3xl p-8 text-center text-gray-400 dark:text-gray-500 border border-gray-100 dark:border-gray-700">
                    {{ __('No pending invitations.') }}
                </div>
            @endforelse
        </div>
    </div>

    <!-- Invite Modal -->
    @if ($isModalOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-gray-950/60 backdrop-blur-sm transition-opacity" wire:click="$set('isModalOpen', false)"></div>

            <!-- Modal Container -->
            <div class="bg-white dark:bg-gray-800 rounded-3xl overflow-hidden shadow-xl transform transition-all w-full max-w-lg z-50 border border-gray-100 dark:border-gray-700">
                <form wire:submit="sendInvitation" class="p-6 sm:p-8 space-y-5">
                    <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-gray-700">
                        <h3 class="text-base font-bold text-gray-900 dark:text-gray-100">
                            {{ __('Invite Member') }}
                        </h3>
                        <button type="button" wire:click="$set('isModalOpen', false)" class="text-gray-400 hover:text-gray-500">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Invitation Inputs -->
                    <div class="space-y-4">
                        <div>
                            <x-input-label for="inviteEmail" :value="__('Email Address')" />
                            <x-text-input wire:model="inviteEmail" id="inviteEmail" type="email" class="mt-1 block w-full" placeholder="e.g. teacher@school.com" />
                            <x-input-error :messages="$errors->get('inviteEmail')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="inviteMobile" :value="__('Mobile Number')" />
                            <x-text-input wire:model="inviteMobile" id="inviteMobile" type="text" class="mt-1 block w-full" placeholder="e.g. 9664588677" />
                            <x-input-error :messages="$errors->get('inviteMobile')" class="mt-1" />
                        </div>
                        <p class="text-[10px] text-gray-400 dark:text-gray-500 italic mt-1">
                            {{ __('* Provide either an Email Address, a Mobile Number, or both. The recipient will see this request on their dashboard.') }}
                        </p>
                    </div>

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

                    <!-- Teacher Grade & Division Scopes -->
                    @if ($roleSlug === 'teacher')
                        <div class="space-y-4 bg-indigo-50/20 dark:bg-indigo-950/10 p-4 rounded-2xl border border-indigo-100/30 dark:border-indigo-900/10">
                            <div class="grid grid-cols-2 gap-4">
                                <!-- Grade -->
                                <div>
                                    <x-input-label for="gradeId" :value="__('Assign Grade')" />
                                    <select wire:model.live="gradeId" id="gradeId" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-xl shadow-sm">
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
                                    <select wire:model="divisionId" id="divisionId" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-xl shadow-sm">
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

                            <div class="flex justify-end">
                                <button type="button" wire:click="addAssignment" class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-900/30 dark:hover:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300 text-xs font-bold uppercase tracking-wider rounded-xl transition cursor-pointer">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                                    </svg>
                                    <span>{{ __('Add Class Assignment') }}</span>
                                </button>
                            </div>

                            <!-- List of Added Assignments -->
                            @if (!empty($tempAssignments))
                                <div class="border-t border-indigo-100/50 dark:border-indigo-900/20 pt-3">
                                    <span class="text-[10px] font-black uppercase text-gray-400 dark:text-gray-500 tracking-wider block mb-2">{{ __('Class Assignments') }}</span>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach ($tempAssignments as $idx => $assign)
                                            <div class="flex items-center gap-1.5 pl-3 pr-1.5 py-1 rounded-xl text-xs font-semibold bg-white dark:bg-gray-900 border border-gray-150 dark:border-gray-700 shadow-sm text-gray-800 dark:text-gray-250">
                                                <span>{{ $assign['grade_name'] }} - {{ $assign['division_name'] }}</span>
                                                <button type="button" wire:click="removeAssignment({{ $idx }})" class="p-1 text-gray-400 hover:text-red-500 rounded-lg transition-colors cursor-pointer">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                    </svg>
                                                </button>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif

                    <!-- Footer Actions -->
                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                        <button type="button" wire:click="$set('isModalOpen', false)" class="px-5 py-2.5 bg-transparent hover:bg-gray-50 dark:hover:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 font-bold text-xs uppercase tracking-wider rounded-xl transition">
                            {{ __('Cancel') }}
                        </button>
                        <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs uppercase tracking-wider rounded-xl transition shadow">
                            {{ __('Send Invite') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Delete active member confirmation modal -->
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
                            {{ __('Remove Member') }}
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-2 leading-relaxed">
                            {{ __('Are you sure you want to permanently remove this user from this school? They will lose all access rights immediately.') }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                    <button type="button" wire:click="$set('confirmingDeletion', false)" class="px-5 py-2.5 bg-transparent hover:bg-gray-50 dark:hover:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 font-bold text-xs uppercase tracking-wider rounded-xl transition">
                        {{ __('Cancel') }}
                    </button>
                    <button type="button" wire:click="deleteMember" class="px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white font-bold text-xs uppercase tracking-wider rounded-xl transition shadow">
                        {{ __('Remove') }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Revoke pending invite confirmation modal -->
    @if ($confirmingRevocation)
        <div class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-gray-950/60 backdrop-blur-sm transition-opacity" wire:click="$set('confirmingRevocation', false)"></div>

            <!-- Modal Container -->
            <div class="bg-white dark:bg-gray-800 rounded-3xl overflow-hidden shadow-xl transform transition-all w-full max-w-md z-50 border border-gray-100 dark:border-gray-700 p-6 sm:p-8 space-y-6">
                <div class="flex items-start space-x-4">
                    <div class="p-3 bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 rounded-2xl shrink-0">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-gray-100">
                            {{ __('Revoke Invitation') }}
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-2 leading-relaxed">
                            {{ __('Are you sure you want to revoke this pending invitation? The user will no longer be able to accept it.') }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                    <button type="button" wire:click="$set('confirmingRevocation', false)" class="px-5 py-2.5 bg-transparent hover:bg-gray-50 dark:hover:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 font-bold text-xs uppercase tracking-wider rounded-xl transition">
                        {{ __('Cancel') }}
                    </button>
                    <button type="button" wire:click="revokeInvite" class="px-5 py-2.5 bg-amber-650 hover:bg-amber-700 text-white font-bold text-xs uppercase tracking-wider rounded-xl transition shadow">
                        {{ __('Revoke') }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Edit Modal -->
    @if ($isEditModalOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-gray-950/60 backdrop-blur-sm transition-opacity" wire:click="$set('isEditModalOpen', false)"></div>

            <!-- Modal Container -->
            <div class="bg-white dark:bg-gray-800 rounded-3xl overflow-hidden shadow-xl transform transition-all w-full max-w-lg z-50 border border-gray-100 dark:border-gray-700">
                <form wire:submit="saveEdit" class="p-6 sm:p-8 space-y-5">
                    <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-gray-700">
                        <h3 class="text-base font-bold text-gray-900 dark:text-gray-100">
                            {{ __('Edit Member Role & Assignments') }}
                        </h3>
                        <button type="button" wire:click="$set('isEditModalOpen', false)" class="text-gray-400 hover:text-gray-500">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Select Role -->
                    <div>
                        <x-input-label for="editRoleSlug" :value="__('Assign Role')" />
                        <select wire:model.live="roleSlug" id="editRoleSlug" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-xl shadow-sm" required>
                            <option value="">Select Role</option>
                            <option value="school_admin">School Admin</option>
                            <option value="teacher">Teacher</option>
                        </select>
                        <x-input-error :messages="$errors->get('roleSlug')" class="mt-1" />
                    </div>

                    <!-- Teacher Grade & Division Scopes -->
                    @if ($roleSlug === 'teacher')
                        <div class="space-y-4 bg-indigo-50/20 dark:bg-indigo-950/10 p-4 rounded-2xl border border-indigo-100/30 dark:border-indigo-900/10">
                            <div class="grid grid-cols-2 gap-4">
                                <!-- Grade -->
                                <div>
                                    <x-input-label for="editGradeId" :value="__('Assign Grade')" />
                                    <select wire:model.live="gradeId" id="editGradeId" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-xl shadow-sm">
                                        <option value="">Select Grade</option>
                                        @foreach (Grade::where('school_id', session('active_school_id'))->orderBy('name', 'asc')->get() as $g)
                                            <option value="{{ $g->id }}">{{ $g->name }}</option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('gradeId')" class="mt-1" />
                                </div>

                                <!-- Division -->
                                <div>
                                    <x-input-label for="editDivisionId" :value="__('Assign Division')" />
                                    <select wire:model.divisionId="divisionId" id="editDivisionId" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-xl shadow-sm">
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

                            <div class="flex justify-end">
                                <button type="button" wire:click="addAssignment" class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-900/30 dark:hover:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300 text-xs font-bold uppercase tracking-wider rounded-xl transition cursor-pointer">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                                    </svg>
                                    <span>{{ __('Add Class Assignment') }}</span>
                                </button>
                            </div>

                            <!-- List of Added Assignments -->
                            @if (!empty($tempAssignments))
                                <div class="border-t border-indigo-100/50 dark:border-indigo-900/20 pt-3">
                                    <span class="text-[10px] font-black uppercase text-gray-400 dark:text-gray-500 tracking-wider block mb-2">{{ __('Class Assignments') }}</span>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach ($tempAssignments as $idx => $assign)
                                            <div class="flex items-center gap-1.5 pl-3 pr-1.5 py-1 rounded-xl text-xs font-semibold bg-white dark:bg-gray-900 border border-gray-150 dark:border-gray-700 shadow-sm text-gray-800 dark:text-gray-250">
                                                <span>{{ $assign['grade_name'] }} - {{ $assign['division_name'] }}</span>
                                                <button type="button" wire:click="removeAssignment({{ $idx }})" class="p-1 text-gray-400 hover:text-red-500 rounded-lg transition-colors cursor-pointer">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                    </svg>
                                                </button>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif

                    <!-- Footer Actions -->
                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                        <button type="button" wire:click="$set('isEditModalOpen', false)" class="px-5 py-2.5 bg-transparent hover:bg-gray-50 dark:hover:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 font-bold text-xs uppercase tracking-wider rounded-xl transition">
                            {{ __('Cancel') }}
                        </button>
                        <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs uppercase tracking-wider rounded-xl transition shadow">
                            {{ __('Save Changes') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
