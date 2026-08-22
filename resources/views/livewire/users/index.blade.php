<?php

use App\Models\User;
use App\Models\Role;
use Livewire\Volt\Component;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;

new class extends Component
{
    public $users = [];
    public $roles = [];

    // Filter & Search state
    public string $search = '';
    public string $filterRole = '';
    public int $totalUsersCount = 0;
    public int $filteredUsersCount = 0;
    public array $roleCounts = [];

    // Form fields
    public $userId = null;
    public string $name = '';
    public string $email = '';
    public string $mobile = '';
    public string $password = '';
    public array $selectedRoles = [];

    // Modal state
    public bool $isModalOpen = false;
    public bool $isConfirmDeleteOpen = false;
    public $userToDeleteId = null;

    // Pagination properties
    public int $perPage = 12;
    public bool $hasMore = false;

    public function mount()
    {
        $this->roles = Role::orderBy('id')->get();
        $this->loadUsers();
    }

    public function updatedSearch()
    {
        $this->perPage = 12;
        $this->loadUsers();
    }

    public function updatedFilterRole()
    {
        $this->perPage = 12;
        $this->loadUsers();
    }

    public function setRoleFilter(string $slug)
    {
        $this->filterRole = ($this->filterRole === $slug) ? '' : $slug;
        $this->perPage = 12;
        $this->loadUsers();
    }

    public function resetFilters()
    {
        $this->search = '';
        $this->filterRole = '';
        $this->perPage = 12;
        $this->loadUsers();
    }

    public function loadMore()
    {
        $this->perPage += 12;
        $this->loadUsers();
    }

    public function loadUsers()
    {
        if (! auth()->user()->hasAnyRole(['saas_admin', 'school_admin'])) {
            abort(403);
        }

        // Global total count in system database
        $this->totalUsersCount = User::count();

        // Calculate role breakdown counts
        $rolesWithCounts = Role::withCount('users')->get();
        $this->roleCounts = $rolesWithCounts->pluck('users_count', 'slug')->toArray();

        // Build query
        $query = User::with('roles');

        if (!empty(trim($this->search))) {
            $s = '%' . trim($this->search) . '%';
            $query->where(function($q) use ($s) {
                $q->where('name', 'like', $s)
                  ->orWhere('email', 'like', $s)
                  ->orWhere('mobile', 'like', $s);
            });
        }

        if (!empty($this->filterRole)) {
            $query->whereHas('roles', function($q) {
                $q->where('slug', $this->filterRole);
            });
        }

        $this->filteredUsersCount = (clone $query)->count();
        $this->users = $query->orderBy('name', 'asc')->take($this->perPage)->get();
        $this->hasMore = $this->filteredUsersCount > $this->perPage;
    }

    public function openCreateModal()
    {
        $this->resetForm();
        $this->isModalOpen = true;
    }

    public function openEditModal($id)
    {
        $this->resetForm();
        $user = User::with('roles')->findOrFail($id);
        $this->userId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->mobile = $user->mobile;
        $this->selectedRoles = $user->roles->pluck('slug')->toArray();
        $this->isModalOpen = true;
    }

    public function resetForm()
    {
        $this->userId = null;
        $this->name = '';
        $this->email = '';
        $this->mobile = '';
        $this->password = '';
        $this->selectedRoles = [];
        $this->resetErrorBag();
    }

    public function saveUser()
    {
        if (! auth()->user()->hasAnyRole(['saas_admin', 'school_admin'])) {
            abort(403);
        }

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($this->userId)],
            'mobile' => ['required', 'string', 'max:255', Rule::unique(User::class)->ignore($this->userId)],
            'selectedRoles' => ['required', 'array', 'min:1'],
            'selectedRoles.*' => ['string', 'exists:roles,slug'],
        ];

        if (!$this->userId) {
            $rules['password'] = ['required', 'string', 'min:8'];
        } else {
            $rules['password'] = ['nullable', 'string', 'min:8'];
        }

        $validated = $this->validate($rules);

        if ($this->userId) {
            $user = User::findOrFail($this->userId);
            $user->update([
                'name' => $this->name,
                'email' => $this->email,
                'mobile' => $this->mobile,
            ]);
            if ($this->password) {
                $user->update(['password' => Hash::make($this->password)]);
            }
        } else {
            $user = User::create([
                'name' => $this->name,
                'email' => $this->email,
                'mobile' => $this->mobile,
                'password' => Hash::make($this->password),
            ]);
        }

        // Sync roles
        $roleIds = Role::whereIn('slug', $this->selectedRoles)->pluck('id')->toArray();
        $user->roles()->sync($roleIds);

        $this->isModalOpen = false;
        $this->resetForm();
        $this->loadUsers();

        session()->flash('message', $this->userId ? 'User updated successfully.' : 'User created successfully.');
    }

    public function confirmDelete($id)
    {
        $this->userToDeleteId = $id;
        $this->isConfirmDeleteOpen = true;
    }

    public function deleteUser()
    {
        if (! auth()->user()->hasAnyRole(['saas_admin', 'school_admin'])) {
            abort(403);
        }
        if ($this->userToDeleteId) {
            if ($this->userToDeleteId == auth()->id()) {
                session()->flash('error', 'You cannot delete yourself.');
                $this->isConfirmDeleteOpen = false;
                return;
            }

            $user = User::findOrFail($this->userToDeleteId);
            $user->roles()->detach();
            $user->delete();
            
            $this->loadUsers();
            session()->flash('message', 'User deleted successfully.');
        }
        $this->isConfirmDeleteOpen = false;
    }
}; ?>

<div class="space-y-6">
    <!-- Messages Notifications -->
    @if (session()->has('message'))
        <div class="p-4 mb-4 text-sm text-emerald-800 rounded-2xl bg-emerald-50 dark:bg-emerald-950/30 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-900/40 flex items-center gap-2">
            <svg class="h-5 w-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span>{{ session('message') }}</span>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="p-4 mb-4 text-sm text-red-800 rounded-2xl bg-red-50 dark:bg-red-950/30 dark:text-red-400 border border-red-100 dark:border-red-900/40 flex items-center gap-2">
            <svg class="h-5 w-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- Header & Action Row -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 bg-white dark:bg-gray-800 p-6 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-xl shadow-gray-200/50 dark:shadow-none">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-indigo-50 dark:bg-indigo-950/20 text-indigo-600 dark:text-indigo-400 rounded-xl flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
            </div>
            <div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ __('System Users') }}</h3>
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1 flex flex-wrap items-center gap-2 sm:gap-3">
                    <span class="inline-flex items-center gap-1.5 text-gray-600 dark:text-gray-300 font-medium whitespace-nowrap">
                        <span class="w-2 h-2 rounded-full bg-indigo-500 shrink-0"></span>
                        <strong class="font-bold text-gray-900 dark:text-gray-100">{{ $totalUsersCount }}</strong> {{ __('Total Registered Users') }}
                    </span>
                    @if (!empty($search) || !empty($filterRole))
                        <span class="text-gray-300 dark:text-gray-600 select-none">•</span>
                        <span class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 border border-indigo-200/80 dark:border-indigo-700/40 whitespace-nowrap shadow-sm">
                            <svg class="w-3.5 h-3.5 shrink-0 text-indigo-500 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                            </svg>
                            <span><strong class="font-bold text-indigo-700 dark:text-indigo-300">{{ $filteredUsersCount }}</strong> {{ __('Filtered') }}</span>
                        </span>
                    @endif
                </div>

                <!-- Role Breakdown Quick Filter Pills -->
                <div class="mt-3 flex flex-wrap items-center gap-1.5 text-[11px]">
                    <button type="button" wire:click="$set('filterRole', '')" class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full font-bold transition cursor-pointer {{ empty($filterRole) ? 'bg-indigo-600 text-white shadow-sm ring-2 ring-indigo-400' : 'bg-gray-100 dark:bg-gray-700/80 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700' }}" title="Show All Users">
                        <span>All: <strong>{{ $totalUsersCount }}</strong></span>
                    </button>
                    @foreach ($roles as $r)
                        @php
                            $rClass = match($r->slug) {
                                'saas_admin' => $filterRole === $r->slug ? 'bg-indigo-600 text-white shadow-sm ring-2 ring-indigo-400' : 'bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-300 hover:bg-indigo-100',
                                'school_admin' => $filterRole === $r->slug ? 'bg-amber-600 text-white shadow-sm ring-2 ring-amber-400' : 'bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 hover:bg-amber-100',
                                'teacher' => $filterRole === $r->slug ? 'bg-emerald-600 text-white shadow-sm ring-2 ring-emerald-400' : 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-100',
                                'parent' => $filterRole === $r->slug ? 'bg-purple-600 text-white shadow-sm ring-2 ring-purple-400' : 'bg-purple-50 dark:bg-purple-950/40 text-purple-700 dark:text-purple-300 hover:bg-purple-100',
                                default => $filterRole === $r->slug ? 'bg-slate-700 text-white shadow-sm ring-2 ring-slate-400' : 'bg-gray-50 dark:bg-gray-900 text-gray-600 dark:text-gray-400 hover:bg-gray-100',
                            };
                            $dotColor = match($r->slug) {
                                'saas_admin' => 'bg-indigo-400',
                                'school_admin' => 'bg-amber-400',
                                'teacher' => 'bg-emerald-400',
                                'parent' => 'bg-purple-400',
                                default => 'bg-gray-400',
                            };
                        @endphp
                        <button type="button" wire:click="setRoleFilter('{{ $r->slug }}')" class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full font-bold transition cursor-pointer {{ $rClass }}" title="Click to filter by {{ $r->name }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $dotColor }}"></span>
                            <span>{{ $r->name }}: <strong>{{ $roleCounts[$r->slug] ?? 0 }}</strong></span>
                        </button>
                    @endforeach
                </div>
            </div>
        </div>
        <div>
            <button wire:click="openCreateModal" class="inline-flex items-center justify-center gap-2 w-full sm:w-auto px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs uppercase tracking-wider rounded-xl transition shadow cursor-pointer">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                </svg>
                <span>{{ __('Add User') }}</span>
            </button>
        </div>
    </div>

    <!-- Filters & Search Bar -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 bg-white dark:bg-gray-800 p-5 rounded-3xl border border-gray-200 dark:border-gray-700 shadow-xl shadow-gray-200/50 dark:shadow-none">
        <!-- Search Input -->
        <div class="sm:col-span-2">
            <label class="text-[9px] uppercase font-bold text-gray-500 dark:text-gray-400 tracking-wider block mb-1.5">{{ __('Search User') }}</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="{{ __('Search by Name, Email, or Mobile number...') }}" class="w-full pl-9 pr-8 border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-xl text-xs focus:ring-indigo-500 focus:border-indigo-500 placeholder-gray-400 dark:placeholder-gray-500" />
                @if(!empty($search))
                    <button type="button" wire:click="$set('search', '')" class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 cursor-pointer">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                @endif
            </div>
        </div>

        <!-- Role Filter Dropdown -->
        <div>
            <label class="text-[9px] uppercase font-bold text-gray-500 dark:text-gray-400 tracking-wider block mb-1.5">{{ __('Filter by Role') }}</label>
            <select wire:model.live="filterRole" class="w-full border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-xl text-xs focus:ring-indigo-500 focus:border-indigo-500">
                <option value="">{{ __('All Roles') }} ({{ $totalUsersCount }})</option>
                @foreach ($roles as $role)
                    <option value="{{ $role->slug }}">{{ $role->name }} ({{ $roleCounts[$role->slug] ?? 0 }})</option>
                @endforeach
            </select>
        </div>

        <!-- Reset Button -->
        <div class="flex items-end">
            @if(!empty($search) || !empty($filterRole))
                <button type="button" wire:click="resetFilters" class="w-full px-4 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-xl text-xs font-bold transition flex items-center justify-center gap-1.5 cursor-pointer">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <span>{{ __('Reset Filters') }}</span>
                </button>
            @else
                <div class="text-[11px] text-gray-400 dark:text-gray-500 py-2 flex items-center gap-1.5">
                    <span>{{ __('Showing') }} <strong class="text-gray-700 dark:text-gray-300">{{ count($users) }}</strong> {{ __('of') }} <strong class="text-gray-700 dark:text-gray-300">{{ $totalUsersCount }}</strong> {{ __('users') }}</span>
                </div>
            @endif
        </div>
    </div>

    <!-- Grid of User Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
        @forelse ($users as $user)
            <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 shadow-xl shadow-gray-200/40 dark:shadow-none border border-gray-100 dark:border-gray-700 hover:border-indigo-500/30 dark:hover:border-indigo-400/20 transition-all duration-300 flex flex-col justify-between relative group">
                <!-- User Profile Header -->
                <div>
                    <div class="flex items-start gap-4">
                        @php
                            $initials = collect(explode(' ', $user->name))->map(fn($n) => substr($n, 0, 1))->take(2)->join('');
                            // Deterministic color class based on user ID or name hash
                            $avatarColors = match($user->id % 4) {
                                0 => 'bg-indigo-100 dark:bg-indigo-950/70 text-indigo-700 dark:text-indigo-300',
                                1 => 'bg-emerald-100 dark:bg-emerald-950/70 text-emerald-700 dark:text-emerald-300',
                                2 => 'bg-amber-100 dark:bg-amber-950/70 text-amber-700 dark:text-amber-300',
                                default => 'bg-purple-100 dark:bg-purple-950/70 text-purple-700 dark:text-purple-300',
                            };
                        @endphp
                        <div class="h-12 w-12 rounded-2xl flex items-center justify-center font-bold text-sm shrink-0 {{ $avatarColors }} shadow-sm group-hover:scale-105 transition-transform duration-200">
                            {{ strtoupper($initials) }}
                        </div>
                        <div class="space-y-0.5 min-w-0">
                            <h4 class="font-bold text-gray-900 dark:text-gray-100 truncate text-base">{{ $user->name }}</h4>
                            <p class="text-xs text-gray-500 dark:text-gray-400 font-mono truncate select-all">{{ $user->email }}</p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 font-medium select-all">{{ $user->mobile }}</p>
                        </div>
                    </div>

                    <!-- Roles List -->
                    <div class="mt-5 flex flex-wrap gap-1.5">
                        @foreach ($user->roles as $role)
                            @php
                                $badgeClass = match($role->slug) {
                                    'saas_admin' => 'bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-300 border border-indigo-100/50 dark:border-indigo-900/30',
                                    'school_admin' => 'bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border border-amber-100/50 dark:border-amber-900/30',
                                    'teacher' => 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-100/50 dark:border-emerald-900/30',
                                    'parent' => 'bg-purple-50 dark:bg-purple-950/40 text-purple-700 dark:text-purple-300 border border-purple-100/50 dark:border-purple-900/30',
                                    default => 'bg-gray-50 dark:bg-gray-950 text-gray-600 dark:text-gray-400 border border-gray-100 dark:border-gray-900',
                                };
                            @endphp
                            <span class="px-2.5 py-1 rounded-lg text-[9px] font-bold uppercase tracking-wider {{ $badgeClass }}">
                                {{ $role->name }}
                            </span>
                        @endforeach
                    </div>
                </div>

                <!-- Divider & Actions -->
                <div class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <span class="text-[9px] uppercase font-black tracking-widest text-gray-400 dark:text-gray-500">
                        ID: #{{ $user->id }}
                    </span>
                    <div class="flex items-center gap-1">
                        <button wire:click="openEditModal({{ $user->id }})" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-900 rounded-xl text-gray-400 hover:text-indigo-600 dark:text-gray-500 dark:hover:text-indigo-400 transition-colors cursor-pointer" title="Edit user">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </button>
                        @if ($user->id !== auth()->id())
                            <button wire:click="confirmDelete({{ $user->id }})" class="p-2 hover:bg-red-50 dark:hover:bg-red-950/20 rounded-xl text-gray-400 hover:text-red-650 dark:text-gray-500 dark:hover:text-red-400 transition-colors cursor-pointer" title="Delete user">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white dark:bg-gray-800 rounded-3xl p-12 text-center text-gray-400 dark:text-gray-500 border border-gray-100 dark:border-gray-700">
                <svg class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                <p class="font-medium text-sm">{{ __('No users found matching your criteria.') }}</p>
                @if(!empty($search) || !empty($filterRole))
                    <button type="button" wire:click="resetFilters" class="mt-3 inline-flex items-center gap-1 px-4 py-2 bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 rounded-xl text-xs font-bold transition hover:bg-indigo-100 cursor-pointer">
                        {{ __('Clear Filters') }}
                    </button>
                @endif
            </div>
        @endforelse
    </div>

    @if ($hasMore)
        <div class="flex flex-col items-center justify-center pt-8 space-y-2">
            <button wire:click="loadMore" class="px-6 py-3 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/60 text-gray-700 dark:text-gray-300 font-extrabold text-xs uppercase tracking-wider rounded-2xl transition shadow-sm flex items-center gap-2 cursor-pointer">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 13l-7 7-7-7m14-6l-7 7-7-7"/>
                </svg>
                <span>{{ __('Load More Users') }}</span>
            </button>
            <p class="text-[11px] text-gray-400 dark:text-gray-500">
                {{ __('Showing') }} {{ count($users) }} {{ __('of') }} {{ $filteredUsersCount }} {{ __('users') }}
            </p>
        </div>
    @endif

    <!-- Create/Edit Modal -->
    @if ($isModalOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-gray-950/60 backdrop-blur-sm transition-opacity" wire:click="$set('isModalOpen', false)"></div>

            <!-- Modal Container -->
            <div class="bg-white dark:bg-gray-800 rounded-3xl overflow-hidden shadow-xl transform transition-all w-full max-w-xl z-50 border border-gray-100 dark:border-gray-700">
                <form wire:submit="saveUser" class="p-6 sm:p-8 space-y-6">
                    <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-gray-700">
                        <h3 class="text-base font-bold text-gray-900 dark:text-gray-100">
                            {{ $userId ? __('Edit User') : __('Add New User') }}
                        </h3>
                        <button type="button" wire:click="$set('isModalOpen', false)" class="text-gray-400 hover:text-gray-500">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div class="md:col-span-2">
                            <x-input-label for="form_name" :value="__('Name')" />
                            <x-text-input wire:model="name" id="form_name" type="text" class="mt-1 block w-full" required />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="form_email" :value="__('Email')" />
                            <x-text-input wire:model="email" id="form_email" type="email" class="mt-1 block w-full" required />
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="form_mobile" :value="__('Mobile Number')" />
                            <x-text-input wire:model="mobile" id="form_mobile" type="text" class="mt-1 block w-full" required />
                            <x-input-error :messages="$errors->get('mobile')" class="mt-2" />
                        </div>

                        <div class="md:col-span-2">
                            <x-input-label for="form_password" :value="__('Password')" />
                            <x-text-input wire:model="password" id="form_password" type="password" class="mt-1 block w-full" :placeholder="$userId ? __('Leave blank to keep current password') : ''" :required="!$userId" />
                            <x-input-error :messages="$errors->get('password')" class="mt-2" />
                        </div>

                        <div class="md:col-span-2">
                            <x-input-label :value="__('Roles')" />
                            <div class="mt-2 grid grid-cols-2 gap-4">
                                @foreach ($roles as $role)
                                    <label class="inline-flex items-center">
                                        <input type="checkbox" wire:model="selectedRoles" value="{{ $role->slug }}" class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                        <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">{{ $role->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                            <x-input-error :messages="$errors->get('selectedRoles')" class="mt-2" />
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700">
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

    <!-- Delete Confirmation Modal -->
    @if ($isConfirmDeleteOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-gray-950/60 backdrop-blur-sm transition-opacity" wire:click="$set('isConfirmDeleteOpen', false)"></div>

            <!-- Modal Container -->
            <div class="bg-white dark:bg-gray-800 rounded-3xl overflow-hidden shadow-xl transform transition-all w-full max-w-md z-50 border border-gray-100 dark:border-gray-700">
                <div class="p-6 sm:p-8">
                    <div class="flex items-center gap-4 text-red-650 dark:text-red-400 mb-4">
                        <div class="h-12 w-12 rounded-2xl bg-red-50 dark:bg-red-950/30 flex items-center justify-center border border-red-100/50 dark:border-red-950/50 shrink-0">
                            <svg class="h-6 w-6 text-red-650 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-gray-900 dark:text-gray-100">
                                {{ __('Delete User') }}
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                {{ __('Action Confirmation Required') }}
                            </p>
                        </div>
                    </div>

                    <p class="text-xs text-gray-600 dark:text-gray-300 mb-6 leading-relaxed">
                        {{ __('Are you sure you want to permanently delete this user? This action cannot be undone.') }}
                    </p>

                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                        <button type="button" wire:click="$set('isConfirmDeleteOpen', false)" class="px-5 py-2.5 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/60 rounded-xl font-bold text-xs uppercase text-gray-700 dark:text-gray-300 transition cursor-pointer">
                            {{ __('Cancel') }}
                        </button>
                        <button type="button" wire:click="deleteUser" class="px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-xl font-bold text-xs uppercase shadow transition cursor-pointer">
                            {{ __('Delete') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
