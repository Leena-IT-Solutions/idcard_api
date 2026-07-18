<?php

use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use App\Models\School;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

new class extends Component {
    use WithFileUploads;

    // Search
    public $search = '';

    // Model properties
    public $schools = [];
    public $schoolId = null;
    public $name = '';
    public $logo = null;
    public $currentLogoPath = null;
    public $address = '';
    public $contact_number = '';
    public $email = '';
    public $website = '';
    public $school_code = '';
    public $principal_name = '';

    // Modal state
    public $isModalOpen = false;
    public $confirmingDeletion = false;
    public $schoolToDelete = null;

    public function mount()
    {
        $this->loadSchools();
    }

    public function updatedSearch()
    {
        $this->loadSchools();
    }

    public function loadSchools()
    {
        $user = auth()->user();
        if (!$user) {
            $this->schools = [];
            return;
        }

        $query = School::query();

        // If not a saas_admin, scope by school user roles mapping
        if (!$user->hasRole('saas_admin')) {
            $assignedSchoolIds = \App\Models\SchoolUserRole::where('user_id', $user->id)->pluck('school_id');
            $query->whereIn('id', $assignedSchoolIds);
        }

        if ($this->search) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('school_code', 'like', '%' . $this->search . '%')
                  ->orWhere('address', 'like', '%' . $this->search . '%');
            });
        }
        $this->schools = $query->orderBy('name', 'asc')->get();
    }

    // --- CRUD ---
    public function openCreateModal()
    {
        $this->resetValidation();
        $this->reset([
            'schoolId', 'name', 'logo', 'currentLogoPath', 'address', 
            'contact_number', 'email', 'website', 'school_code', 'principal_name'
        ]);
        $this->isModalOpen = true;
    }

    public function openEditModal($id)
    {
        $user = auth()->user();
        if (!$user->hasRole('saas_admin')) {
            $assignedSchoolIds = \App\Models\SchoolUserRole::where('user_id', $user->id)->pluck('school_id')->toArray();
            if (!in_array($id, $assignedSchoolIds)) {
                abort(403);
            }
        }

        $this->resetValidation();
        $school = School::findOrFail($id);
        
        $this->schoolId = $school->id;
        $this->name = $school->name;
        $this->currentLogoPath = $school->logo_path;
        $this->address = $school->address;
        $this->contact_number = $school->contact_number;
        $this->email = $school->email;
        $this->website = $school->website;
        $this->school_code = $school->school_code;
        $this->principal_name = $school->principal_name;
        $this->logo = null;

        $this->isModalOpen = true;
    }

    public function saveSchool()
    {
        $validated = $this->validate([
            'name' => 'required|string|max:255',
            'logo' => 'nullable|image|max:2048', // 2MB max logo
            'address' => 'required|string',
            'contact_number' => 'required|string|max:50',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|url|max:255',
            'school_code' => 'nullable|string|max:50',
            'principal_name' => 'nullable|string|max:255',
        ]);

        $logoPath = $this->currentLogoPath;

        if ($this->logo) {
            // Delete old logo if it exists
            if ($this->currentLogoPath && Storage::disk('public')->exists($this->currentLogoPath)) {
                Storage::disk('public')->delete($this->currentLogoPath);
            }
            // Store new logo
            $logoPath = $this->logo->store('logos', 'public');
        }

        $isNew = !$this->schoolId;

        $school = School::updateOrCreate(
            ['id' => $this->schoolId],
            [
                'name' => $this->name,
                'logo_path' => $logoPath,
                'address' => $this->address,
                'contact_number' => $this->contact_number,
                'email' => $this->email,
                'website' => $this->website,
                'school_code' => $this->school_code,
                'principal_name' => $this->principal_name,
            ]
        );

        if ($isNew) {
            $schoolAdminRole = \App\Models\Role::where('slug', 'school_admin')->first();
            if ($schoolAdminRole) {
                \App\Models\SchoolUserRole::create([
                    'school_id' => $school->id,
                    'user_id' => auth()->id(),
                    'role_id' => $schoolAdminRole->id,
                ]);
            }
        }

        // If active school is not set, set this as active school
        if (!session('active_school_id')) {
            session(['active_school_id' => $school->id]);
        }

        $this->isModalOpen = false;
        
        // Redirect to refresh selector headers
        $this->redirect(route('schools'), navigate: true);
    }

    public function confirmDeletion($id)
    {
        $user = auth()->user();
        if (!$user->hasRole('saas_admin')) {
            $assignedSchoolIds = \App\Models\SchoolUserRole::where('user_id', $user->id)->pluck('school_id')->toArray();
            if (!in_array($id, $assignedSchoolIds)) {
                abort(403);
            }
        }

        $this->schoolToDelete = $id;
        $this->confirmingDeletion = true;
    }

    public function deleteSchool()
    {
        if ($this->schoolToDelete) {
            $school = School::findOrFail($this->schoolToDelete);
            
            // Delete logo from disk
            if ($school->logo_path && Storage::disk('public')->exists($school->logo_path)) {
                Storage::disk('public')->delete($school->logo_path);
            }

            $school->delete();

            // Clear session if active school was deleted
            if (session('active_school_id') == $this->schoolToDelete) {
                session()->forget('active_school_id');
            }

            $this->schoolToDelete = null;
            $this->confirmingDeletion = false;

            // Redirect to refresh select headers
            $this->redirect(route('schools'), navigate: true);
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
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
            </div>
            <div>
                <h3 class="text-xl font-extrabold text-gray-900 dark:text-gray-100">{{ __('School Profiles') }}</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    {{ __('Manage school institutes, addresses, logos, and global contexts') }}
                </p>
            </div>
        </div>
        <div>
            <button wire:click="openCreateModal" class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs uppercase tracking-wider rounded-xl transition shadow">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                </svg>
                <span>{{ __('Add School') }}</span>
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
        <input type="text" wire:model.live.debounce.150ms="search" placeholder="Search schools by name, code or address..." class="w-full pl-10 pr-4 py-3 bg-white dark:bg-gray-800 border-gray-100 dark:border-gray-700 rounded-2xl dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 shadow-md shadow-gray-200/20 dark:shadow-none" />
    </div>

    <!-- Listings Grid of School Cards -->
    <div class="flex flex-col gap-6">
        @forelse ($schools as $school)
            <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 shadow-xl shadow-gray-200/40 dark:shadow-none border border-gray-100 dark:border-gray-700 hover:border-indigo-500/30 dark:hover:border-indigo-400/20 transition-all duration-300 flex flex-col md:flex-row gap-6 group">
                <!-- Left Side: School Logo -->
                <div class="relative w-full md:w-44 h-44 rounded-2xl overflow-hidden shrink-0 border border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 flex items-center justify-center">
                    @if ($school->logo_path)
                        <img src="{{ asset('storage/' . $school->logo_path) }}" alt="{{ $school->name }}" class="object-contain w-full h-full p-2 group-hover:scale-105 transition-transform duration-500" />
                    @else
                        <div class="w-full h-full bg-gradient-to-br from-indigo-500 to-purple-650 flex flex-col items-center justify-center text-white p-3 text-center">
                            <span class="font-black text-3xl">{{ strtoupper(substr($school->name, 0, 2)) }}</span>
                            <span class="text-[9px] uppercase tracking-widest font-black mt-1 bg-white/20 px-2 py-0.5 rounded-full">{{ __('NO LOGO') }}</span>
                        </div>
                    @endif
                </div>

                <!-- Right Side: Details -->
                <div class="flex-1 flex flex-col justify-between space-y-4">
                    <div class="space-y-2">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                            <h4 class="font-extrabold text-gray-900 dark:text-gray-100 text-2xl leading-tight">
                                {{ $school->name }}
                            </h4>
                            @if ($school->school_code)
                                <span class="px-2.5 py-1 rounded-lg text-[9px] font-black uppercase tracking-wider bg-indigo-50 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-400 border border-indigo-100/50 dark:border-indigo-900/30 shadow-sm shrink-0 self-start sm:self-auto">
                                    {{ __('Code:') }} {{ $school->school_code }}
                                </span>
                            @endif
                        </div>

                        <!-- Info Grid -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs pt-2 border-t border-gray-150/40 dark:border-gray-800">
                            <div class="flex flex-col gap-1">
                                <span class="text-[9px] uppercase font-black text-gray-400 dark:text-gray-500 tracking-wider">{{ __('Phone') }}</span>
                                <span class="text-gray-800 dark:text-gray-200 font-semibold select-all">{{ $school->contact_number }}</span>
                            </div>
                            <div class="flex flex-col gap-1">
                                <span class="text-[9px] uppercase font-black text-gray-400 dark:text-gray-500 tracking-wider">{{ __('Email') }}</span>
                                <span class="text-gray-800 dark:text-gray-200 font-semibold select-all">{{ $school->email ?? 'N/A' }}</span>
                            </div>
                            <div class="flex flex-col gap-1">
                                <span class="text-[9px] uppercase font-black text-gray-400 dark:text-gray-500 tracking-wider">{{ __('Website') }}</span>
                                @if ($school->website)
                                    <a href="{{ $school->website }}" target="_blank" class="text-indigo-600 dark:text-indigo-400 font-semibold hover:underline">{{ $school->website }}</a>
                                @else
                                    <span class="text-gray-400 dark:text-gray-500">{{ 'N/A' }}</span>
                                @endif
                            </div>
                            <div class="flex flex-col gap-1">
                                <span class="text-[9px] uppercase font-black text-gray-400 dark:text-gray-500 tracking-wider">{{ __('Principal') }}</span>
                                <span class="text-gray-800 dark:text-gray-200 font-semibold">{{ $school->principal_name ?? 'N/A' }}</span>
                            </div>
                            <div class="sm:col-span-2 flex flex-col gap-1">
                                <span class="text-[9px] uppercase font-black text-gray-400 dark:text-gray-500 tracking-wider">{{ __('Address') }}</span>
                                <span class="text-gray-700 dark:text-gray-300 font-medium leading-relaxed">{{ $school->address }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Actions -->
                    <div class="pt-4 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between">
                        <span class="text-[9px] uppercase font-black tracking-widest text-gray-400 dark:text-gray-500">
                            {{ __('ID:') }} #{{ $school->id }}
                        </span>
                        <div class="flex items-center gap-1.5">
                            <button wire:click="openEditModal({{ $school->id }})" class="p-2.5 hover:bg-gray-50 dark:hover:bg-gray-900 rounded-xl text-gray-400 hover:text-indigo-605 dark:text-gray-500 dark:hover:text-indigo-405 transition-colors">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </button>
                            <button wire:click="confirmDeletion({{ $school->id }})" class="p-2.5 hover:bg-red-50 dark:hover:bg-red-950/20 rounded-xl text-gray-400 hover:text-red-655 dark:text-gray-500 dark:hover:text-red-450 transition-colors">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white dark:bg-gray-800 rounded-3xl p-12 text-center text-gray-400 dark:text-gray-500 border border-gray-100 dark:border-gray-700">
                {{ __('No schools found.') }}
            </div>
        @endforelse
    </div>

    <!-- Create/Edit Modal -->
    @if ($isModalOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-gray-950/60 backdrop-blur-sm transition-opacity" wire:click="$set('isModalOpen', false)"></div>

            <!-- Modal Container -->
            <div class="bg-white dark:bg-gray-800 rounded-3xl overflow-hidden shadow-xl transform transition-all w-full max-w-2xl z-50 border border-gray-100 dark:border-gray-700">
                <form wire:submit="saveSchool" class="p-6 sm:p-8 space-y-6">
                    <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-gray-700">
                        <h3 class="text-base font-bold text-gray-900 dark:text-gray-100">
                            {{ $schoolId ? __('Edit School') : __('Add New School') }}
                        </h3>
                        <button type="button" wire:click="$set('isModalOpen', false)" class="text-gray-400 hover:text-gray-500">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <!-- School Name -->
                        <div class="md:col-span-2">
                            <x-input-label for="name" :value="__('School Name')" />
                            <x-text-input wire:model="name" id="name" type="text" class="mt-1 block w-full" required placeholder="e.g. Info Leena International School" />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <!-- School Code -->
                        <div>
                            <x-input-label for="school_code" :value="__('School Registration Code')" />
                            <x-text-input wire:model="school_code" id="school_code" type="text" class="mt-1 block w-full" placeholder="e.g. SCH123" />
                            <x-input-error :messages="$errors->get('school_code')" class="mt-2" />
                        </div>

                        <!-- Principal Name -->
                        <div>
                            <x-input-label for="principal_name" :value="__('Principal Name')" />
                            <x-text-input wire:model="principal_name" id="principal_name" type="text" class="mt-1 block w-full" placeholder="e.g. Dr. John Doe" />
                            <x-input-error :messages="$errors->get('principal_name')" class="mt-2" />
                        </div>

                        <!-- Contact Number -->
                        <div>
                            <x-input-label for="contact_number" :value="__('Contact Phone Number')" />
                            <x-text-input wire:model="contact_number" id="contact_number" type="text" class="mt-1 block w-full" required placeholder="e.g. +91 9664588677" />
                            <x-input-error :messages="$errors->get('contact_number')" class="mt-2" />
                        </div>

                        <!-- Email -->
                        <div>
                            <x-input-label for="email" :value="__('Email Address')" />
                            <x-text-input wire:model="email" id="email" type="email" class="mt-1 block w-full" placeholder="e.g. contact@school.com" />
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>

                        <!-- Website -->
                        <div class="md:col-span-2">
                            <x-input-label for="website" :value="__('School Website URL')" />
                            <x-text-input wire:model="website" id="website" type="text" class="mt-1 block w-full" placeholder="e.g. https://www.school.com" />
                            <x-input-error :messages="$errors->get('website')" class="mt-2" />
                        </div>

                        <!-- Full Address -->
                        <div class="md:col-span-2">
                            <x-input-label for="address" :value="__('Full Address')" />
                            <textarea wire:model="address" id="address" rows="3" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-xl shadow-sm" required></textarea>
                            <x-input-error :messages="$errors->get('address')" class="mt-2" />
                        </div>

                        <!-- Logo Upload -->
                        <div class="md:col-span-2">
                            <x-input-label :value="__('School Logo')" />
                            <div class="mt-2 flex items-center gap-5">
                                @if ($logo)
                                    <img src="{{ $logo->temporaryUrl() }}" class="h-20 w-20 object-contain rounded-2xl border border-gray-250 bg-gray-50 p-1" />
                                @elseif ($currentLogoPath)
                                    <img src="{{ asset('storage/' . $currentLogoPath) }}" class="h-20 w-20 object-contain rounded-2xl border border-gray-250 bg-gray-50 p-1" />
                                @else
                                    <div class="h-20 w-20 bg-gray-100 dark:bg-gray-900 rounded-2xl flex items-center justify-center text-gray-400">
                                        <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 00-2-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                @endif

                                <div class="flex-1">
                                    <input type="file" wire:model="logo" id="logo" class="hidden" accept="image/*" />
                                    <label type="button" for="logo" class="cursor-pointer inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-xl font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-900 focus:outline-none transition ease-in-out duration-150">
                                        {{ __('Choose Logo') }}
                                    </label>
                                    <span class="text-xs text-gray-500 dark:text-gray-400 block mt-2">{{ __('JPEG, PNG up to 2MB') }}</span>
                                </div>
                            </div>
                            <x-input-error :messages="$errors->get('logo')" class="mt-2" />
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
                            {{ __('Delete School Profile') }}
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-2 leading-relaxed">
                            {{ __('Are you sure you want to permanently delete this school? All grades, divisions, and students mapped to this school profile will be permanently deleted. This action cannot be undone.') }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                    <button type="button" wire:click="$set('confirmingDeletion', false)" class="px-5 py-2.5 bg-transparent hover:bg-gray-50 dark:hover:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 font-bold text-xs uppercase tracking-wider rounded-xl transition">
                        {{ __('Cancel') }}
                    </button>
                    <button type="button" wire:click="deleteSchool" class="px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white font-bold text-xs uppercase tracking-wider rounded-xl transition shadow">
                        {{ __('Delete') }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
