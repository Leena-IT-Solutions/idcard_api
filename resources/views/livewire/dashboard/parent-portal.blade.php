<?php

use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use App\Models\Student;
use App\Models\Campaign;
use App\Models\CampaignStudent;
use App\Models\ParentAccess;
use App\Models\Grade;
use App\Models\Division;
use Illuminate\Support\Facades\Storage;

new class extends Component {
    use WithFileUploads;

    // Child Profile Form fields
    public $studentId = null;
    public string $first_name = '';
    public string $middle_name = '';
    public string $last_name = '';
    public string $blood_group = '';
    public string $dob = '';
    public string $address = '';
    public string $pincode = '';
    public string $contact_number = '';
    public $photo = null;
    public ?string $currentPhotoPath = null;

    // Enrollment Form fields
    public $enrollStudentId = '';
    public $enrollCampaignId = '';
    public $enrollGradeId = '';
    public $enrollDivisionId = '';

    // Modal states
    public bool $isChildModalOpen = false;
    public bool $isEnrollModalOpen = false;
    public bool $isConfirmDeleteOpen = false;
    public $studentToDeleteId = null;

    public function mount()
    {
        // Set default contact number to parent's mobile if blank
        $this->contact_number = auth()->user()->mobile ?? '';
    }

    public function getChildrenProperty()
    {
        return Student::where('user_id', auth()->id())
            ->orderBy('first_name', 'asc')
            ->get();
    }

    public function getAvailableCampaignsProperty()
    {
        $mobile = auth()->user()->mobile;
        if (!$mobile) {
            return collect();
        }

        // Find schools that authorized this mobile number
        $schoolIds = ParentAccess::where('mobile', $mobile)->pluck('school_id');

        if ($schoolIds->isEmpty()) {
            return collect();
        }

        // Get campaigns for these schools
        return Campaign::whereIn('school_id', $schoolIds)
            ->with(['school', 'campaignStudents.student'])
            ->orderBy('registration_end_date', 'asc')
            ->get();
    }

    // --- Child profile CRUD handlers ---
    public function openCreateChildModal()
    {
        $this->resetChildForm();
        $this->isChildModalOpen = true;
    }

    public function openEditChildModal($id)
    {
        $this->resetChildForm();
        $student = Student::where('user_id', auth()->id())->findOrFail($id);

        $this->studentId = $student->id;
        $this->first_name = $student->first_name;
        $this->middle_name = $student->middle_name ?? '';
        $this->last_name = $student->last_name;
        $this->blood_group = $student->blood_group ?? '';
        $this->dob = $student->dob;
        $this->address = $student->address;
        $this->pincode = $student->pincode;
        $this->contact_number = $student->contact_number;
        $this->currentPhotoPath = $student->photo_path;

        $this->isChildModalOpen = true;
    }

    public function resetChildForm()
    {
        $this->studentId = null;
        $this->first_name = '';
        $this->middle_name = '';
        $this->last_name = '';
        $this->blood_group = '';
        $this->dob = '';
        $this->address = '';
        $this->pincode = '';
        $this->contact_number = auth()->user()->mobile ?? '';
        $this->photo = null;
        $this->currentPhotoPath = null;
        $this->resetErrorBag();
    }

    public function saveChild()
    {
        $rules = [
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'blood_group' => ['nullable', 'string', 'max:10'],
            'dob' => ['required', 'date'],
            'address' => ['required', 'string'],
            'pincode' => ['required', 'string', 'max:20'],
            'contact_number' => ['required', 'string', 'max:20'],
            'photo' => ['nullable', 'image', 'max:2048'],
        ];

        $this->validate($rules);

        $photoPath = $this->currentPhotoPath;
        if ($this->photo) {
            if ($this->currentPhotoPath && Storage::disk('public')->exists($this->currentPhotoPath)) {
                Storage::disk('public')->delete($this->currentPhotoPath);
            }
            $photoPath = $this->photo->store('photos', 'public');
        }

        $studentData = [
            'user_id' => auth()->id(),
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name ?: null,
            'last_name' => $this->last_name,
            'blood_group' => $this->blood_group ?: null,
            'dob' => $this->dob,
            'address' => $this->address,
            'pincode' => $this->pincode,
            'contact_number' => $this->contact_number,
            'photo_path' => $photoPath,
        ];

        if ($this->studentId) {
            $student = Student::where('user_id', auth()->id())->findOrFail($this->studentId);
            $student->update($studentData);
            session()->flash('message', 'Child profile updated successfully.');
        } else {
            Student::create($studentData);
            session()->flash('message', 'Child profile added successfully.');
        }

        $this->isChildModalOpen = false;
        $this->resetChildForm();
    }

    public function confirmDeleteChild($id)
    {
        $this->studentToDeleteId = $id;
        $this->isConfirmDeleteOpen = true;
    }

    public function deleteChild()
    {
        if ($this->studentToDeleteId) {
            $student = Student::where('user_id', auth()->id())->findOrFail($this->studentToDeleteId);
            if ($student->photo_path) {
                Storage::disk('public')->delete($student->photo_path);
            }
            $student->delete();
            session()->flash('message', 'Child profile removed successfully.');
        }
        $this->isConfirmDeleteOpen = false;
        $this->studentToDeleteId = null;
    }

    // --- Enrollment modal handlers ---
    public function openEnrollModal($campaignId)
    {
        $this->resetEnrollmentForm();
        $this->enrollCampaignId = $campaignId;
        $this->isEnrollModalOpen = true;
    }

    public function resetEnrollmentForm()
    {
        $this->enrollStudentId = '';
        $this->enrollGradeId = '';
        $this->enrollDivisionId = '';
        $this->resetValidation();
    }

    public function enrollStudent()
    {
        $campaign = Campaign::findOrFail($this->enrollCampaignId);

        $rules = [
            'enrollStudentId' => ['required', 'exists:students,id'],
            'enrollGradeId' => ['required', 'exists:grades,id'],
            'enrollDivisionId' => ['required', 'exists:divisions,id'],
        ];

        $this->validate($rules);

        // Security context check
        $student = Student::where('user_id', auth()->id())->findOrFail($this->enrollStudentId);
        $grade = Grade::where('school_id', $campaign->school_id)->findOrFail($this->enrollGradeId);
        $division = Division::where('grade_id', $grade->id)->findOrFail($this->enrollDivisionId);

        CampaignStudent::updateOrCreate(
            [
                'campaign_id' => $this->enrollCampaignId,
                'student_id' => $student->id,
            ],
            [
                'grade_id' => $grade->id,
                'division_id' => $division->id,
            ]
        );

        $this->isEnrollModalOpen = false;
        $this->resetEnrollmentForm();
        session()->flash('message', "{$student->first_name} has been enrolled in {$campaign->name} successfully.");
    }
};

?>

<div class="space-y-8">
    @if (session()->has('message'))
        <div class="p-4 bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-100 dark:border-emerald-900/30 text-emerald-600 dark:text-emerald-400 rounded-2xl text-sm font-semibold flex items-center gap-2">
            <svg class="h-5 w-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span>{{ session('message') }}</span>
        </div>
    @endif

    <!-- Parent Campaigns List Section -->
    <div class="space-y-4">
        <h3 class="text-base font-black uppercase text-gray-400 dark:text-gray-500 tracking-wider">
            {{ __('Active School Campaigns') }}
        </h3>

        @php
            $campaigns = $this->availableCampaigns;
        @endphp

        @if ($campaigns->isEmpty())
            <div class="bg-white dark:bg-gray-800 rounded-3xl p-8 border border-gray-100 dark:border-gray-700 text-center">
                <div class="w-12 h-12 bg-amber-50 dark:bg-amber-950/20 text-amber-500 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <h4 class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ __('No active campaigns found') }}</h4>
                <p class="text-xs text-gray-400 mt-1 max-w-sm mx-auto">
                    {{ __('Your mobile number must be added to a school\'s Parent Access list by a school administrator to enroll your children.') }}
                </p>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach ($campaigns as $camp)
                    <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 border border-gray-100 dark:border-gray-700 flex flex-col justify-between shadow-sm relative overflow-hidden">
                        <div>
                            <div class="flex items-start justify-between mb-4">
                                <div>
                                    <span class="text-[9px] uppercase font-black tracking-widest text-indigo-600 dark:text-indigo-400 block mb-0.5">{{ $camp->school->name }}</span>
                                    <h4 class="font-extrabold text-gray-900 dark:text-gray-100 text-lg leading-tight">{{ $camp->name }}</h4>
                                </div>
                                <span class="px-2.5 py-1 bg-emerald-50 dark:bg-emerald-950/30 text-emerald-600 dark:text-emerald-400 rounded-xl text-[9px] font-black uppercase tracking-wider">
                                    {{ __('Active') }}
                                </span>
                            </div>

                            <p class="text-[11px] text-gray-450 dark:text-gray-400 leading-normal mb-5">
                                {{ __('Registration Period:') }} <span class="font-bold text-gray-700 dark:text-gray-300">{{ $camp->registration_start_date->format('d M, Y') }}</span> {{ __('to') }} <span class="font-bold text-gray-700 dark:text-gray-300">{{ $camp->registration_end_date->format('d M, Y') }}</span>
                            </p>

                            <!-- Enrolled Children -->
                            <div class="space-y-2 mb-6">
                                <h5 class="text-[10px] font-black uppercase text-gray-400 tracking-wider">{{ __('Enrolled Children:') }}</h5>
                                @php
                                    $enrolledPivots = $camp->campaignStudents()->whereIn('student_id', $this->children->pluck('id'))->with(['student', 'grade', 'division'])->get();
                                @endphp

                                @if ($enrolledPivots->isEmpty())
                                    <p class="text-xs text-gray-400 italic">{{ __('No children enrolled in this campaign yet.') }}</p>
                                @else
                                    <div class="space-y-1.5">
                                        @foreach ($enrolledPivots as $piv)
                                            <div class="flex items-center justify-between p-2.5 bg-gray-50 dark:bg-gray-900 rounded-xl border border-gray-100 dark:border-gray-750">
                                                <div class="flex items-center space-x-2">
                                                    <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></span>
                                                    <span class="text-xs font-bold text-gray-800 dark:text-gray-250">{{ $piv->student->first_name }} {{ $piv->student->last_name }}</span>
                                                </div>
                                                <span class="px-2 py-0.5 rounded bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-400 text-[9px] font-bold">
                                                    {{ $piv->grade->name }} - {{ $piv->division->name }}
                                                </span>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="pt-4 border-t border-gray-100 dark:border-gray-800">
                            @php
                                $unenrolledChildren = $this->children->filter(function($child) use ($enrolledPivots) {
                                    return !$enrolledPivots->pluck('student_id')->contains($child->id);
                                });
                            @endphp

                            @if ($unenrolledChildren->isEmpty() && !$this->children->isEmpty())
                                <button disabled class="w-full py-2.5 bg-gray-100 dark:bg-gray-900 text-gray-400 dark:text-gray-650 font-bold text-xs uppercase tracking-wider rounded-xl cursor-not-allowed text-center">
                                    {{ __('All children enrolled') }}
                                </button>
                            @else
                                <button wire:click="openEnrollModal({{ $camp->id }})" class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-xs uppercase tracking-wider rounded-xl transition shadow-md shadow-indigo-600/10 text-center cursor-pointer">
                                    {{ __('Enroll a Child') }}
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Children Profile Management Section -->
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="text-base font-black uppercase text-gray-400 dark:text-gray-500 tracking-wider">
                {{ __('My Children Profiles') }}
            </h3>
            <button wire:click="openCreateChildModal" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-[11px] uppercase tracking-wider rounded-xl transition shadow flex items-center gap-1.5 cursor-pointer">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                </svg>
                {{ __('Add Profile') }}
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @forelse ($this->children as $child)
                <div class="bg-white dark:bg-gray-800 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden flex flex-col justify-between group hover:shadow-md transition">
                    <div>
                        <!-- Photo Header -->
                        <div class="relative h-44 bg-gray-100 dark:bg-gray-900 overflow-hidden shrink-0">
                            @if ($child->photo_path)
                                <img src="{{ asset('storage/' . $child->photo_path) }}" alt="{{ $child->first_name }}" class="object-cover w-full h-full group-hover:scale-105 transition-transform duration-500" />
                            @else
                                <div class="w-full h-full bg-gradient-to-br from-indigo-500 via-purple-500 to-pink-500 flex items-center justify-center text-white font-bold text-2xl">
                                    {{ strtoupper(substr($child->first_name, 0, 1) . substr($child->last_name, 0, 1)) }}
                                </div>
                            @endif
                        </div>

                        <!-- Card Body -->
                        <div class="p-5 space-y-3">
                            <h4 class="font-extrabold text-gray-905 dark:text-gray-100 text-lg leading-tight">
                                {{ $child->first_name }} {{ $child->middle_name ? $child->middle_name . ' ' : '' }}{{ $child->last_name }}
                            </h4>
                            <div class="text-[10px] text-gray-400 dark:text-gray-500 font-semibold space-y-1">
                                <p class="flex items-center gap-1">
                                    <span>DOB:</span>
                                    <span class="text-gray-700 dark:text-gray-300 font-bold">{{ \Carbon\Carbon::parse($child->dob)->format('d M, Y') }}</span>
                                </p>
                                @if ($child->blood_group)
                                    <p class="flex items-center gap-1">
                                        <span>Blood Group:</span>
                                        <span class="text-gray-700 dark:text-gray-300 font-bold">{{ $child->blood_group }}</span>
                                    </p>
                                @endif
                                <p class="flex items-start gap-1">
                                    <span>Address:</span>
                                    <span class="text-gray-600 dark:text-gray-400 font-normal leading-tight">{{ $child->address }}, {{ $child->pincode }}</span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Actions -->
                    <div class="p-5 pt-4 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between">
                        <span class="text-[9px] uppercase font-black tracking-widest text-gray-400 dark:text-gray-500">
                            ID: #{{ $child->id }}
                        </span>
                        <div class="flex items-center gap-1">
                            <button wire:click="openEditChildModal({{ $child->id }})" class="p-2 hover:bg-gray-50 dark:hover:bg-gray-900 rounded-xl text-gray-400 hover:text-indigo-600 dark:text-gray-500 transition cursor-pointer">
                                <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </button>
                            <button wire:click="confirmDeleteChild({{ $child->id }})" class="p-2 hover:bg-red-50 dark:hover:bg-red-950/20 rounded-xl text-gray-400 hover:text-red-600 dark:text-gray-500 transition cursor-pointer">
                                <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full bg-white dark:bg-gray-800 rounded-3xl p-12 text-center text-gray-400 dark:text-gray-500 border border-gray-100 dark:border-gray-700">
                    {{ __('No child profiles created yet. Click "Add Profile" to register your children.') }}
                </div>
            @endforelse
        </div>
    </div>

    <!-- Add/Edit Child Modal -->
    @if ($isChildModalOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-gray-950/65 backdrop-blur-sm transition-opacity" wire:click="$set('isChildModalOpen', false)"></div>

            <div class="bg-white dark:bg-gray-800 rounded-3xl overflow-hidden shadow-2xl transform transition-all max-w-xl w-full border border-gray-100 dark:border-gray-700 z-10 p-6 sm:p-8">
                <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-gray-700 mb-6">
                    <h3 class="text-lg font-black text-gray-900 dark:text-gray-100">
                        {{ $studentId ? __('Edit Child Profile') : __('Add Child Profile') }}
                    </h3>
                    <button wire:click="$set('isChildModalOpen', false)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form wire:submit="saveChild" class="space-y-5">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <!-- First Name -->
                        <div>
                            <x-input-label for="f_name" :value="__('First Name')" />
                            <x-text-input wire:model="first_name" id="f_name" type="text" class="mt-1 block w-full" required />
                            <x-input-error :messages="$errors->get('first_name')" class="mt-2" />
                        </div>
                        <!-- Middle Name -->
                        <div>
                            <x-input-label for="m_name" :value="__('Middle Name')" />
                            <x-text-input wire:model="middle_name" id="m_name" type="text" class="mt-1 block w-full" />
                            <x-input-error :messages="$errors->get('middle_name')" class="mt-2" />
                        </div>
                        <!-- Last Name -->
                        <div>
                            <x-input-label for="l_name" :value="__('Last Name')" />
                            <x-text-input wire:model="last_name" id="l_name" type="text" class="mt-1 block w-full" required />
                            <x-input-error :messages="$errors->get('last_name')" class="mt-2" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <!-- DOB -->
                        <div>
                            <x-input-label for="dob" :value="__('Date of Birth')" />
                            <x-text-input wire:model="dob" id="dob" type="date" class="mt-1 block w-full" required />
                            <x-input-error :messages="$errors->get('dob')" class="mt-2" />
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
                        <!-- Contact Number -->
                        <div>
                            <x-input-label for="c_num" :value="__('Contact Number')" />
                            <x-text-input wire:model="contact_number" id="c_num" type="text" class="mt-1 block w-full" required />
                            <x-input-error :messages="$errors->get('contact_number')" class="mt-2" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <!-- Full Address -->
                        <div class="sm:col-span-2">
                            <x-input-label for="addr" :value="__('Full Address')" />
                            <x-text-input wire:model="address" id="addr" type="text" class="mt-1 block w-full" required />
                            <x-input-error :messages="$errors->get('address')" class="mt-2" />
                        </div>
                        <!-- Pincode -->
                        <div>
                            <x-input-label for="pin" :value="__('Pincode')" />
                            <x-text-input wire:model="pincode" id="pin" type="text" class="mt-1 block w-full" required />
                            <x-input-error :messages="$errors->get('pincode')" class="mt-2" />
                        </div>
                    </div>

                    <!-- Photo Upload -->
                    <div>
                        <x-input-label :value="__('Student Photo')" />
                        @if ($photo)
                            <div class="mt-2 mb-4">
                                <img src="{{ $photo->temporaryUrl() }}" class="h-28 w-28 object-cover rounded-2xl shadow border border-gray-150" />
                            </div>
                        @elseif ($currentPhotoPath)
                            <div class="mt-2 mb-4">
                                <img src="{{ asset('storage/' . $currentPhotoPath) }}" class="h-28 w-28 object-cover rounded-2xl shadow border border-gray-155" />
                            </div>
                        @endif
                        <input type="file" wire:model="photo" id="photo_file" accept="image/*" class="mt-1 block w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 transition cursor-pointer" />
                        <x-input-error :messages="$errors->get('photo')" class="mt-2" />
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700 mt-6">
                        <button type="button" wire:click="$set('isChildModalOpen', false)" class="px-5 py-2.5 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/60 rounded-xl font-bold text-xs uppercase text-gray-700 dark:text-gray-300 transition cursor-pointer">
                            {{ __('Cancel') }}
                        </button>
                        <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold text-xs uppercase shadow transition cursor-pointer">
                            {{ __('Save Profile') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Enroll Child Modal -->
    @if ($isEnrollModalOpen)
        @php
            $selectedCampaign = \App\Models\Campaign::find($enrollCampaignId);
        @endphp
        <div class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-gray-950/65 backdrop-blur-sm transition-opacity" wire:click="$set('isEnrollModalOpen', false)"></div>

            <div class="bg-white dark:bg-gray-800 rounded-3xl overflow-hidden shadow-2xl transform transition-all max-w-md w-full border border-gray-100 dark:border-gray-700 z-10 p-6 sm:p-8">
                <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-gray-700 mb-6">
                    <h3 class="text-lg font-black text-gray-900 dark:text-gray-100">
                        {{ __('Enroll Child in Campaign') }}
                    </h3>
                    <button wire:click="$set('isEnrollModalOpen', false)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form wire:submit="enrollStudent" class="space-y-4">
                    <!-- Child Selection -->
                    <div>
                        <x-input-label for="enroll_student" :value="__('Select Child')" />
                        <select wire:model.live="enrollStudentId" id="enroll_student" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl shadow-sm" required>
                            <option value="">-- Choose Child Profile --</option>
                            @php
                                $enrolledIds = $selectedCampaign ? $selectedCampaign->campaignStudents()->whereIn('student_id', $this->children->pluck('id'))->pluck('student_id')->toArray() : [];
                            @endphp
                            @foreach ($this->children as $child)
                                @if (!in_array($child->id, $enrolledIds))
                                    <option value="{{ $child->id }}">{{ $child->first_name }} {{ $child->last_name }}</option>
                                @endif
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('enrollStudentId')" class="mt-2" />
                    </div>

                    <!-- Grade (Standard) -->
                    <div>
                        <x-input-label for="enroll_grade" :value="__('Standard / Grade')" />
                        <select wire:model.live="enrollGradeId" id="enroll_grade" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl shadow-sm" required>
                            <option value="">-- Choose Grade --</option>
                            @if ($selectedCampaign)
                                @foreach (Grade::where('school_id', $selectedCampaign->school_id)->orderBy('name', 'asc')->get() as $g)
                                    <option value="{{ $g->id }}">{{ $g->name }}</option>
                                @endforeach
                            @endif
                        </select>
                        <x-input-error :messages="$errors->get('enrollGradeId')" class="mt-2" />
                    </div>

                    <!-- Division -->
                    <div>
                        <x-input-label for="enroll_div" :value="__('Division / Section')" />
                        <select wire:model="enrollDivisionId" id="enroll_div" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl shadow-sm" required>
                            <option value="">-- Choose Division --</option>
                            @if ($enrollGradeId)
                                @foreach (Division::where('grade_id', $enrollGradeId)->orderBy('name', 'asc')->get() as $d)
                                    <option value="{{ $d->id }}">{{ $d->name }}</option>
                                @endforeach
                            @endif
                        </select>
                        <x-input-error :messages="$errors->get('enrollDivisionId')" class="mt-2" />
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700 mt-6">
                        <button type="button" wire:click="$set('isEnrollModalOpen', false)" class="px-5 py-2.5 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/60 rounded-xl font-bold text-xs uppercase text-gray-700 dark:text-gray-300 transition cursor-pointer">
                            {{ __('Cancel') }}
                        </button>
                        <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold text-xs uppercase shadow transition cursor-pointer">
                            {{ __('Enroll Child') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Delete Confirmation Modal -->
    @if ($isConfirmDeleteOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-gray-950/65 backdrop-blur-sm transition-opacity" wire:click="$set('isConfirmDeleteOpen', false)"></div>

            <div class="bg-white dark:bg-gray-800 rounded-3xl overflow-hidden shadow-2xl transform transition-all max-w-sm w-full border border-gray-100 dark:border-gray-700 z-10 p-6">
                <div class="text-center">
                    <div class="w-12 h-12 bg-red-50 dark:bg-red-950/20 text-red-650 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </div>
                    <h3 class="text-base font-extrabold text-gray-905 dark:text-gray-100">{{ __('Remove Child Profile') }}</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-2 leading-relaxed">
                        {{ __('Are you sure you want to permanently delete this child profile? This action will also cancel any active campaign registrations.') }}
                    </p>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700 mt-6">
                    <button type="button" wire:click="$set('isConfirmDeleteOpen', false)" class="px-4 py-2 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/60 rounded-xl font-bold text-xs uppercase text-gray-700 dark:text-gray-300 transition cursor-pointer">
                        {{ __('Cancel') }}
                    </button>
                    <button type="button" wire:click="deleteChild" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-xl font-bold text-xs uppercase shadow transition cursor-pointer">
                        {{ __('Remove') }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
