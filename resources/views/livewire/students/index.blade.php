<?php

use App\Models\Student;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;

new class extends Component
{
    use WithFileUploads;

    public $students = [];

    // Form fields
    public $studentId = null;
    public string $first_name = '';
    public string $middle_name = '';
    public string $last_name = '';
    public string $standard = '';
    public string $division = '';
    public string $blood_group = '';
    public string $dob = '';
    public string $address = '';
    public string $pincode = '';
    public string $contact_number = '';
    public $photo = null;
    public ?string $currentPhotoPath = null;

    // Modal state
    public bool $isModalOpen = false;
    public bool $isConfirmDeleteOpen = false;
    public $studentToDeleteId = null;

    public function mount()
    {
        $this->loadStudents();
    }

    public function loadStudents()
    {
        if (! auth()->user()->hasAnyRole(['saas_admin', 'school_admin', 'teacher'])) {
            abort(403);
        }

        $activeSchoolId = session('active_school_id');
        if (!$activeSchoolId) {
            $this->students = [];
            return;
        }

        $this->students = Student::where('school_id', $activeSchoolId)->orderBy('created_at', 'desc')->get();
    }

    public function updatedStandard($value)
    {
        $this->division = '';
    }

    public function openCreateModal()
    {
        $this->resetForm();
        $this->isModalOpen = true;
    }

    public function openEditModal($id)
    {
        $this->resetForm();
        $student = Student::findOrFail($id);
        $this->studentId = $student->id;
        $this->first_name = $student->first_name;
        $this->middle_name = $student->middle_name ?? '';
        $this->last_name = $student->last_name;
        $this->standard = $student->standard;
        $this->division = $student->division;
        $this->blood_group = $student->blood_group ?? '';
        $this->dob = $student->dob;
        $this->address = $student->address;
        $this->pincode = $student->pincode;
        $this->contact_number = $student->contact_number;
        $this->currentPhotoPath = $student->photo_path;
        $this->isModalOpen = true;
    }

    public function resetForm()
    {
        $this->studentId = null;
        $this->first_name = '';
        $this->middle_name = '';
        $this->last_name = '';
        $this->standard = '';
        $this->division = '';
        $this->blood_group = '';
        $this->dob = '';
        $this->address = '';
        $this->pincode = '';
        $this->contact_number = '';
        $this->photo = null;
        $this->currentPhotoPath = null;
        $this->resetErrorBag();
    }

    public function saveStudent()
    {
        if (! auth()->user()->hasAnyRole(['saas_admin', 'school_admin', 'teacher'])) {
            abort(403);
        }

        $activeSchoolId = session('active_school_id');
        if (!$activeSchoolId) {
            $this->addError('first_name', 'Please select a school first.');
            return;
        }

        $rules = [
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'standard' => ['required', 'string', 'max:255'],
            'division' => ['required', 'string', 'max:255'],
            'blood_group' => ['nullable', 'string', 'max:10'],
            'dob' => ['required', 'date'],
            'address' => ['required', 'string'],
            'pincode' => ['required', 'string', 'max:20'],
            'contact_number' => ['required', 'string', 'max:20'],
            'photo' => ['nullable', 'image', 'max:2048'], // Max 2MB
        ];

        $validated = $this->validate($rules);

        $photoPath = $this->currentPhotoPath;
        if ($this->photo) {
            // Delete old photo if editing and exists
            if ($this->currentPhotoPath && Storage::disk('public')->exists($this->currentPhotoPath)) {
                Storage::disk('public')->delete($this->currentPhotoPath);
            }
            $photoPath = $this->photo->store('photos', 'public');
        }

        $data = [
            'school_id' => $activeSchoolId,
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name ?: null,
            'last_name' => $this->last_name,
            'standard' => $this->standard,
            'division' => $this->division,
            'blood_group' => $this->blood_group ?: null,
            'dob' => $this->dob,
            'address' => $this->address,
            'pincode' => $this->pincode,
            'contact_number' => $this->contact_number,
            'photo_path' => $photoPath,
        ];

        if ($this->studentId) {
            $student = Student::findOrFail($this->studentId);
            $student->update($data);
        } else {
            Student::create($data);
        }

        $this->isModalOpen = false;
        $this->resetForm();
        $this->loadStudents();

        session()->flash('message', $this->studentId ? 'Student updated successfully.' : 'Student created successfully.');
    }

    public function confirmDelete($id)
    {
        $this->studentToDeleteId = $id;
        $this->isConfirmDeleteOpen = true;
    }

    public function deleteStudent()
    {
        if (! auth()->user()->hasAnyRole(['saas_admin', 'school_admin', 'teacher'])) {
            abort(403);
        }
        if ($this->studentToDeleteId) {
            $student = Student::findOrFail($this->studentToDeleteId);
            if ($student->photo_path) {
                Storage::disk('public')->delete($student->photo_path);
            }
            $student->delete();
            $this->loadStudents();
            session()->flash('message', 'Student deleted successfully.');
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

    <!-- Header & Action Row -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white dark:bg-gray-800 p-6 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-xl shadow-gray-200/50 dark:shadow-none">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-indigo-50 dark:bg-indigo-950/20 text-indigo-600 dark:text-indigo-400 rounded-xl flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
            </div>
            <div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ __('Students Directory') }}</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                    {{ count($students) }} {{ __('students registered in the system') }}
                </p>
            </div>
        </div>
        <div>
            <button wire:click="openCreateModal" class="inline-flex items-center justify-center gap-2 w-full sm:w-auto px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs uppercase tracking-wider rounded-xl transition shadow">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                </svg>
                <span>{{ __('Add Student') }}</span>
            </button>
        </div>
    </div>

    <!-- Grid of Student Cards -->
    <div class="flex flex-col gap-6">
        @forelse ($students as $student)
            <div class="bg-white dark:bg-gray-800 rounded-3xl overflow-hidden shadow-xl shadow-gray-200/40 dark:shadow-none border border-gray-100 dark:border-gray-700 hover:border-indigo-500/30 dark:hover:border-indigo-400/20 transition-all duration-300 flex flex-col md:flex-row group">
                <!-- Left Side Square Photo -->
                <div class="relative w-full md:w-56 h-56 md:h-auto md:aspect-square bg-gray-100 dark:bg-gray-900 overflow-hidden shrink-0 border-r border-gray-50 dark:border-gray-850">
                    @if ($student->photo_path)
                        <img src="{{ asset('storage/' . $student->photo_path) }}" alt="{{ $student->first_name }}" class="object-cover w-full h-full group-hover:scale-105 transition-transform duration-500" />
                    @else
                        <div class="w-full h-full bg-gradient-to-br from-indigo-500 via-purple-500 to-pink-500 flex items-center justify-center text-white font-bold text-3xl">
                            {{ strtoupper(substr($student->first_name, 0, 1) . substr($student->last_name, 0, 1)) }}
                        </div>
                    @endif
                </div>

                <!-- Right Side Card Body & Actions -->
                <div class="p-6 flex-1 flex flex-col justify-between space-y-5">
                    <div>
                        <!-- Header Line: Name, Contact & Badges -->
                        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                            <div class="space-y-1">
                                <h4 class="font-extrabold text-gray-905 dark:text-gray-100 text-2xl leading-none">
                                    {{ $student->first_name }} {{ $student->middle_name ? $student->middle_name . ' ' : '' }}{{ $student->last_name }}
                                </h4>
                                <p class="text-xs font-semibold text-indigo-650 dark:text-indigo-400 flex items-center gap-1.5 pt-1 select-all">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.94.725l.548 2.2a1 1 0 01-.321.988l-1.305.98a10.582 10.582 0 004.872 4.872l.98-1.305a1 1 0 01.988-.321l2.2.548a1 1 0 01.725.94V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                    </svg>
                                    <span>{{ $student->contact_number }}</span>
                                </p>
                            </div>
                            
                            <!-- Badges -->
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-300 border border-indigo-100/50 dark:border-indigo-900/30">
                                    Std: {{ $student->standard }}
                                </span>
                                <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-teal-50 dark:bg-teal-950/40 text-teal-700 dark:text-teal-300 border border-teal-100/50 dark:border-teal-900/30">
                                    Div: {{ $student->division }}
                                </span>
                                @if ($student->blood_group)
                                    <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 border border-rose-100/50 dark:border-rose-900/30">
                                        Blood: {{ $student->blood_group }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        <!-- Info Grid (Clean layout, no sub-card) -->
                        <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm border-t border-gray-100 dark:border-gray-850 pt-5">
                            <div class="flex flex-col gap-1">
                                <span class="text-[9px] uppercase font-black text-gray-405 dark:text-gray-500 tracking-wider">{{ __('Date of Birth') }}</span>
                                <span class="text-gray-800 dark:text-gray-200 font-semibold">{{ \Carbon\Carbon::parse($student->dob)->format('M d, Y') }}</span>
                            </div>
                            <div class="flex flex-col gap-1">
                                <span class="text-[9px] uppercase font-black text-gray-405 dark:text-gray-500 tracking-wider">{{ __('Pincode') }}</span>
                                <span class="text-gray-800 dark:text-gray-200 font-semibold font-mono">{{ $student->pincode }}</span>
                            </div>
                            <div class="sm:col-span-2 flex flex-col gap-1">
                                <span class="text-[9px] uppercase font-black text-gray-405 dark:text-gray-500 tracking-wider">{{ __('Address') }}</span>
                                <span class="text-gray-700 dark:text-gray-300 font-medium leading-relaxed">{{ $student->address }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Card Actions -->
                    <div class="pt-4 border-t border-gray-100 dark:border-gray-850 flex items-center justify-between">
                        <span class="text-[9px] uppercase font-black tracking-widest text-gray-400 dark:text-gray-500">
                            ST-ID: #{{ $student->id }}
                        </span>
                        <div class="flex items-center gap-1.5">
                            <button wire:click="openEditModal({{ $student->id }})" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-900 rounded-xl text-gray-400 hover:text-indigo-600 dark:text-gray-500 dark:hover:text-indigo-400 transition-colors">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </button>
                            <button wire:click="confirmDelete({{ $student->id }})" class="p-2 hover:bg-red-50 dark:hover:bg-red-950/20 rounded-xl text-gray-400 hover:text-red-650 dark:text-gray-500 dark:hover:text-red-400 transition-colors">
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
                {{ __('No students found.') }}
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
                <form wire:submit="saveStudent" class="p-6 sm:p-8 space-y-6">
                    <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-gray-700">
                        <h3 class="text-base font-bold text-gray-900 dark:text-gray-100">
                            {{ $studentId ? __('Edit Student') : __('Add New Student') }}
                        </h3>
                        <button type="button" wire:click="$set('isModalOpen', false)" class="text-gray-400 hover:text-gray-500">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                        <!-- First Name -->
                        <div>
                            <x-input-label for="first_name" :value="__('First Name')" />
                            <x-text-input wire:model="first_name" id="first_name" type="text" class="mt-1 block w-full" required />
                            <x-input-error :messages="$errors->get('first_name')" class="mt-2" />
                        </div>

                        <!-- Middle Name -->
                        <div>
                            <x-input-label for="middle_name" :value="__('Middle Name')" />
                            <x-text-input wire:model="middle_name" id="middle_name" type="text" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('middle_name')" class="mt-2" />
                        </div>

                        <!-- Last Name -->
                        <div>
                            <x-input-label for="last_name" :value="__('Last Name')" />
                            <x-text-input wire:model="last_name" id="last_name" type="text" class="mt-1 block w-full" required />
                            <x-input-error :messages="$errors->get('last_name')" class="mt-2" />
                        </div>

                        <!-- Standard -->
                        <div>
                            <x-input-label for="standard" :value="__('Standard / Class')" />
                            <select wire:model.live="standard" id="standard" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-xl shadow-sm" required>
                                <option value="">Select Standard</option>
                                @foreach (\App\Models\Grade::where('school_id', session('active_school_id'))->orderBy('name', 'asc')->get() as $grade)
                                    <option value="{{ $grade->name }}">{{ $grade->name }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('standard')" class="mt-2" />
                        </div>

                        <!-- Division -->
                        <div>
                            <x-input-label for="division" :value="__('Division / Section')" />
                            <select wire:model="division" id="division" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-xl shadow-sm" required>
                                <option value="">Select Division</option>
                                @php
                                    $selectedGrade = \App\Models\Grade::where('school_id', session('active_school_id'))->where('name', $standard)->first();
                                    $divisions = $selectedGrade ? $selectedGrade->divisions : collect();
                                @endphp
                                @foreach ($divisions as $div)
                                    <option value="{{ $div->name }}">{{ $div->name }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('division')" class="mt-2" />
                        </div>

                        <!-- Blood Group -->
                        <div>
                            <x-input-label for="blood_group" :value="__('Blood Group')" />
                            <select wire:model="blood_group" id="blood_group" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-xl shadow-sm">
                                <option value="">Select Blood Group</option>
                                <option value="A+">A+</option>
                                <option value="A-">A-</option>
                                <option value="B+">B+</option>
                                <option value="B-">B-</option>
                                <option value="AB+">AB+</option>
                                <option value="AB-">AB-</option>
                                <option value="O+">O+</option>
                                <option value="O-">O-</option>
                            </select>
                            <x-input-error :messages="$errors->get('blood_group')" class="mt-2" />
                        </div>

                        <!-- DOB -->
                        <div>
                            <x-input-label for="dob" :value="__('Date of Birth')" />
                            <x-text-input wire:model="dob" id="dob" type="date" class="mt-1 block w-full" required />
                            <x-input-error :messages="$errors->get('dob')" class="mt-2" />
                        </div>

                        <!-- Contact Number -->
                        <div>
                            <x-input-label for="contact_number" :value="__('Contact Number')" />
                            <x-text-input wire:model="contact_number" id="contact_number" type="text" class="mt-1 block w-full" required />
                            <x-input-error :messages="$errors->get('contact_number')" class="mt-2" />
                        </div>

                        <!-- Pincode -->
                        <div>
                            <x-input-label for="pincode" :value="__('Pincode')" />
                            <x-text-input wire:model="pincode" id="pincode" type="text" class="mt-1 block w-full" required />
                            <x-input-error :messages="$errors->get('pincode')" class="mt-2" />
                        </div>

                        <!-- Address -->
                        <div class="md:col-span-3">
                            <x-input-label for="address" :value="__('Full Address')" />
                            <textarea wire:model="address" id="address" rows="3" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-xl shadow-sm" required></textarea>
                            <x-input-error :messages="$errors->get('address')" class="mt-2" />
                        </div>

                        <!-- Photo Upload -->
                        <div class="md:col-span-3">
                            <x-input-label :value="__('Student Photo')" />
                            <div class="mt-2 flex items-center gap-5">
                                @if ($photo)
                                    <img src="{{ $photo->temporaryUrl() }}" class="h-20 w-20 object-cover rounded-2xl border border-gray-200" />
                                @elseif ($currentPhotoPath)
                                    <img src="{{ asset('storage/' . $currentPhotoPath) }}" class="h-20 w-20 object-cover rounded-2xl border border-gray-200" />
                                @else
                                    <div class="h-20 w-20 bg-gray-100 dark:bg-gray-900 rounded-2xl flex items-center justify-center text-gray-400">
                                        <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                @endif

                                <div class="flex-1">
                                    <input type="file" wire:model="photo" id="photo" class="hidden" accept="image/*" />
                                    <label for="photo" class="cursor-pointer inline-flex items-center gap-2 px-4 py-2 border border-gray-300 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/60 text-gray-700 dark:text-gray-300 font-bold text-xs uppercase rounded-xl transition">
                                        {{ __('Choose Photo') }}
                                    </label>
                                    <p class="text-[10px] text-gray-450 dark:text-gray-500 mt-2">JPEG, PNG up to 2MB</p>
                                </div>
                            </div>
                            <x-input-error :messages="$errors->get('photo')" class="mt-2" />
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
                    <div class="flex items-center gap-4 text-red-600 dark:text-red-400 mb-4">
                        <div class="h-12 w-12 rounded-2xl bg-red-50 dark:bg-red-950/30 flex items-center justify-center border border-red-100/50 dark:border-red-950/50 shrink-0">
                            <svg class="h-6 w-6 text-red-650 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-gray-900 dark:text-gray-100">
                                {{ __('Delete Student') }}
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                {{ __('Action Confirmation Required') }}
                            </p>
                        </div>
                    </div>

                    <p class="text-xs text-gray-600 dark:text-gray-300 mb-6 leading-relaxed">
                        {{ __('Are you sure you want to permanently delete this student record? This action cannot be undone.') }}
                    </p>

                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                        <button type="button" wire:click="$set('isConfirmDeleteOpen', false)" class="px-5 py-2.5 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/60 rounded-xl font-bold text-xs uppercase text-gray-700 dark:text-gray-300 transition cursor-pointer">
                            {{ __('Cancel') }}
                        </button>
                        <button type="button" wire:click="deleteStudent" class="px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-xl font-bold text-xs uppercase shadow transition cursor-pointer">
                            {{ __('Delete') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
