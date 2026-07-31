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
    public string $gender = '';
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
    public string $enrollRollNo = '';
    public string $enrollSerialNumber = '';

    // Modal states
    public bool $isChildModalOpen = false;
    public bool $isEnrollModalOpen = false;
    public bool $isConfirmDeleteOpen = false;
    public $studentToDeleteId = null;
    public bool $isPreviewIdCardOpen = false;
    public $previewStudentId = null;

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
        $this->gender = $student->gender ?? '';
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
        $this->gender = '';
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
            'gender' => ['nullable', 'string', 'max:50'],
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
            'gender' => $this->gender ?: null,
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
        $this->enrollRollNo = '';
        $this->enrollSerialNumber = '';
        $this->resetValidation();
    }

    public function enrollStudent()
    {
        $campaign = Campaign::findOrFail($this->enrollCampaignId);

        $rules = [
            'enrollStudentId' => ['required', 'exists:students,id'],
            'enrollGradeId' => ['required', 'exists:grades,id'],
            'enrollDivisionId' => ['required', 'exists:divisions,id'],
            'enrollRollNo' => ['nullable', 'string', 'max:100'],
            'enrollSerialNumber' => ['nullable', 'string', 'max:100'],
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
                'roll_no' => $this->enrollRollNo ?: null,
                'serial_number' => $this->enrollSerialNumber ?: null,
            ]
        );

        $this->isEnrollModalOpen = false;
        $this->resetEnrollmentForm();
        session()->flash('message', "{$student->first_name} has been enrolled in {$campaign->name} successfully.");
    }

    public function syncChildren()
    {
        $linked = auth()->user()->linkUnlinkedStudents();

        if ($linked > 0) {
            session()->flash('message', "Successfully linked {$linked} new profile" . ($linked === 1 ? '' : 's') . '.');
        } else {
            session()->flash('message', 'All child profiles are already up to date.');
        }
    }

    public function openPreviewIdCard($studentId)
    {
        $this->previewStudentId = $studentId;
        $this->isPreviewIdCardOpen = true;
    }

    public function closePreviewIdCard()
    {
        $this->isPreviewIdCardOpen = false;
        $this->previewStudentId = null;
    }

    public function getEffectiveTemplate($schoolId, $gradeId = null)
    {
        $tpl = null;
        if ($gradeId) {
            $grade = \App\Models\Grade::find($gradeId);
            if ($grade) {
                if ($grade->school_template_id && ($st = \App\Models\SchoolTemplate::find($grade->school_template_id))) {
                    $tpl = $st;
                } elseif ($grade->template_id) {
                    if ($st = \App\Models\SchoolTemplate::find($grade->template_id)) $tpl = $st;
                    elseif ($mt = \App\Models\Template::find($grade->template_id)) $tpl = $mt;
                    elseif ($mt = \App\Models\Template::where('slug', $grade->template_id)->first()) $tpl = $mt;
                }
            }
        }

        if (!$tpl && $schoolId) {
            $school = \App\Models\School::find($schoolId);
            if ($school) {
                if ($school->school_template_id && ($st = \App\Models\SchoolTemplate::find($school->school_template_id))) {
                    $tpl = $st;
                } elseif ($school->template_id) {
                    if ($st = \App\Models\SchoolTemplate::find($school->template_id)) $tpl = $st;
                    elseif ($mt = \App\Models\Template::find($school->template_id)) $tpl = $mt;
                    elseif ($mt = \App\Models\Template::where('slug', $school->template_id)->first()) $tpl = $mt;
                }
                if (!$tpl) {
                    $tpl = \App\Models\SchoolTemplate::where('school_id', $schoolId)->where('is_default', true)->first();
                }
                if (!$tpl) {
                    $tpl = \App\Models\SchoolTemplate::where('school_id', $schoolId)->first();
                }
            }
        }

        if (!$tpl) {
            $tpl = \App\Models\Template::first();
        }

        if ($tpl && $tpl instanceof \App\Models\SchoolTemplate) {
            if (empty($tpl->background_image) && $tpl->template_id) {
                $master = \App\Models\Template::find($tpl->template_id);
                if ($master && !empty($master->background_image)) {
                    $tpl->setAttribute('background_image', $master->background_image);
                }
            }
        }

        return $tpl;
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
            <div class="space-y-6">
                @foreach ($campaigns as $camp)
                    @php
                        $enrolledPivots = $camp->campaignStudents()->whereIn('student_id', $this->children->pluck('id'))->with(['student', 'grade', 'division'])->get();
                    @endphp

                    <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 sm:p-8 border border-gray-100 dark:border-gray-700 shadow-sm relative overflow-hidden space-y-6">
                        <div class="flex flex-wrap items-center justify-between gap-4">
                            <div>
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="text-xs font-black uppercase tracking-widest text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/40 px-3 py-1 rounded-full">
                                        {{ $camp->school->name }}
                                    </span>
                                    <span class="px-3 py-1 bg-emerald-50 dark:bg-emerald-950/30 text-emerald-600 dark:text-emerald-400 rounded-full text-xs font-black uppercase tracking-wider">
                                        {{ __('Active') }}
                                    </span>
                                </div>
                                <h4 class="font-extrabold text-gray-900 dark:text-gray-100 text-2xl leading-tight">
                                    {{ $camp->name }}
                                </h4>
                            </div>
                            <div class="text-left sm:text-right">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400 block mb-0.5">{{ __('Registration Period') }}</span>
                                <span class="text-xs font-bold text-gray-700 dark:text-gray-300">
                                    {{ $camp->registration_start_date->format('d M, Y') }} &bull; {{ $camp->registration_end_date->format('d M, Y') }}
                                </span>
                            </div>
                        </div>

                        <!-- Enrolled Children -->
                        <div class="space-y-3">
                            <h5 class="text-xs font-black uppercase text-gray-400 tracking-wider flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                                {{ __('Enrolled Children:') }}
                            </h5>

                            @if ($enrolledPivots->isEmpty())
                                <div class="p-3.5 bg-gray-50 dark:bg-gray-900/60 rounded-2xl border border-dashed border-gray-200 dark:border-gray-700 text-xs text-gray-400 italic">
                                    {{ __('No children enrolled in this campaign yet.') }}
                                </div>
                            @else
                                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                                    @foreach ($enrolledPivots as $piv)
                                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-700">
                                            <div class="flex items-center space-x-2.5">
                                                <span class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></span>
                                                <span class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ $piv->student->first_name }} {{ $piv->student->last_name }}</span>
                                            </div>
                                            <span class="px-2.5 py-1 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-400 text-xs font-bold">
                                                {{ $piv->grade->name }} - {{ $piv->division->name }}
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <!-- Action Button -->
                        <div class="pt-2">
                            @php
                                $unenrolledChildren = $this->children->filter(function($child) use ($enrolledPivots) {
                                    return !$enrolledPivots->pluck('student_id')->contains($child->id);
                                });
                            @endphp

                            @if ($unenrolledChildren->isEmpty() && !$this->children->isEmpty())
                                <button disabled class="w-full py-3 bg-gray-100 dark:bg-gray-900 text-gray-400 dark:text-gray-500 font-bold text-xs uppercase tracking-wider rounded-2xl cursor-not-allowed text-center">
                                    {{ __('All children enrolled') }}
                                </button>
                            @else
                                <button wire:click="openEnrollModal({{ $camp->id }})" class="w-full py-3.5 bg-indigo-600 hover:bg-indigo-700 text-white font-black text-xs uppercase tracking-wider rounded-2xl transition shadow-lg shadow-indigo-600/20 text-center cursor-pointer">
                                    {{ __('Enroll a Child') }}
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Verify ID Cards Section -->
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="text-base font-black uppercase text-gray-400 dark:text-gray-500 tracking-wider flex items-center gap-2">
                <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                {{ __('Verify ID Cards') }}
            </h3>
        </div>

        @php
            $allEnrolledPivots = \App\Models\CampaignStudent::whereIn('student_id', $this->children->pluck('id'))
                ->with(['student', 'grade', 'division', 'campaign.school'])
                ->get();
        @endphp

        @if ($allEnrolledPivots->isEmpty())
            <div class="bg-white dark:bg-gray-800 rounded-3xl p-8 border border-gray-100 dark:border-gray-700 text-center">
                <div class="w-12 h-12 bg-indigo-50 dark:bg-indigo-950/20 text-indigo-500 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" />
                    </svg>
                </div>
                <h4 class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ __('No enrolled student ID cards to verify yet') }}</h4>
                <p class="text-xs text-gray-400 mt-1 max-w-sm mx-auto">
                    {{ __('Enroll your children in an active school campaign above to generate and verify their official ID Cards.') }}
                </p>
            </div>
        @else
            <div class="space-y-6">
                @foreach ($allEnrolledPivots as $piv)
                    @php
                        $school = $piv->campaign->school ?? null;
                        $template = $school ? $this->getEffectiveTemplate($school->id, $piv->grade_id) : null;
                        $isPortrait = $template ? ($template->orientation ?? 'landscape') === 'portrait' : false;
                    @endphp

                    <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 sm:p-8 border border-gray-100 dark:border-gray-700 shadow-sm relative overflow-hidden space-y-6">
                        <!-- Top Header -->
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 dark:border-gray-700/80 pb-4">
                            <div class="flex items-center space-x-3">
                                <span class="w-3 h-3 bg-emerald-500 rounded-full animate-pulse"></span>
                                <h4 class="font-extrabold text-gray-900 dark:text-gray-100 text-xl">
                                    {{ $piv->student->first_name }} {{ $piv->student->last_name }}'s iCard
                                </h4>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="px-3 py-1 bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-400 rounded-xl text-xs font-bold uppercase tracking-wider">
                                    {{ $school->name ?? 'School' }}
                                </span>
                                <span class="px-3 py-1 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400 rounded-xl text-xs font-bold">
                                    {{ $piv->grade->name ?? '' }} - {{ $piv->division->name ?? '' }}
                                </span>
                            </div>
                        </div>

                        <!-- Center Area: Rendered iCard -->
                        <div class="flex flex-col items-center justify-center p-6 bg-gray-50 dark:bg-gray-900/80 rounded-2xl border border-gray-100 dark:border-gray-700/80 overflow-auto">
                            @if ($template)
                                <div class="relative overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700 shadow-xl flex items-center justify-center bg-white dark:bg-gray-950 p-3 my-2"
                                     style="width: 100%; max-width: {{ $isPortrait ? '340px' : '520px' }}; height: {{ $isPortrait ? '500px' : '340px' }};">
                                    <div class="transform origin-center transition-transform duration-300" style="transform: scale({{ $isPortrait ? 0.48 : 0.50 }}); width: {{ $isPortrait ? '638px' : '1011px' }}; height: {{ $isPortrait ? '1011px' : '638px' }};">
                                        <x-id-card-renderer
                                            :template="$template"
                                            :student="$piv->student"
                                            :school="$school"
                                            :previewMode="false"
                                        />
                                    </div>
                                </div>
                            @else
                                <div class="p-8 text-center text-gray-400">
                                    <span class="text-xs font-semibold">{{ __('No Template Assigned for this Grade/School') }}</span>
                                </div>
                            @endif
                        </div>

                        <!-- Footer Actions -->
                        <div class="flex items-center justify-between pt-2">
                            <span class="text-xs font-mono font-bold text-gray-400">
                                Student ID: #{{ $piv->student->id }} &bull; Roll No: {{ $piv->student->roll_no ?? 'N/A' }}
                            </span>
                            <button wire:click="openPreviewIdCard({{ $piv->student->id }})" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-xs uppercase tracking-wider rounded-xl transition shadow flex items-center gap-2 cursor-pointer">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                {{ __('Fullscreen Preview') }}
                            </button>
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
            <div class="flex items-center gap-2">
                <button wire:click="syncChildren" class="px-4 py-2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 text-indigo-600 dark:text-indigo-400 font-extrabold text-[11px] uppercase tracking-wider rounded-xl transition shadow flex items-center gap-1.5 cursor-pointer">
                    <svg class="w-3.5 h-3.5 text-indigo-500 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    {{ __('Sync') }}
                </button>
                <button wire:click="openCreateChildModal" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-[11px] uppercase tracking-wider rounded-xl transition shadow flex items-center gap-1.5 cursor-pointer">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                    </svg>
                    {{ __('Add Profile') }}
                </button>
            </div>
        </div>

        <div class="space-y-4">
            @forelse ($this->children as $child)
                @php
                    $childEnrollment = $child->campaignStudents()->with(['campaign.school', 'grade', 'division'])->first();
                    $childSchool = $childEnrollment ? $childEnrollment->campaign->school : null;
                    $childTemplate = $childSchool ? $this->getEffectiveTemplate($childSchool->id, $childEnrollment->grade_id) : null;
                @endphp
                <div class="bg-white dark:bg-gray-800 rounded-3xl overflow-hidden shadow-sm border border-gray-100 dark:border-gray-700 hover:border-indigo-500/30 dark:hover:border-indigo-400/20 transition-all duration-300 flex flex-col md:flex-row group">
                    <!-- Left Side Photo / Initials Avatar -->
                    <div class="relative w-full md:w-52 h-48 md:h-auto md:aspect-square bg-gray-100 dark:bg-gray-900 overflow-hidden shrink-0 border-r border-gray-100 dark:border-gray-700">
                        @if ($child->photo_path)
                            <img src="{{ asset('storage/' . $child->photo_path) }}" alt="{{ $child->first_name }}" class="object-cover w-full h-full group-hover:scale-105 transition-transform duration-500" />
                        @else
                            <div class="w-full h-full bg-gradient-to-br from-indigo-500 via-purple-500 to-pink-500 flex items-center justify-center text-white font-black text-4xl">
                                {{ strtoupper(substr($child->first_name, 0, 1) . substr($child->last_name, 0, 1)) }}
                            </div>
                        @endif
                    </div>

                    <!-- Right Side Details & Actions -->
                    <div class="p-6 flex-1 flex flex-col justify-between space-y-4">
                        <div>
                            <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                                <h4 class="text-xl font-extrabold text-gray-900 dark:text-gray-100">
                                    {{ $child->first_name }} {{ $child->middle_name ? $child->middle_name . ' ' : '' }}{{ $child->last_name }}
                                </h4>
                                @if ($childEnrollment)
                                    <span class="px-3 py-1 bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 rounded-xl text-xs font-bold">
                                        {{ $childEnrollment->grade->name ?? '' }} - {{ $childEnrollment->division->name ?? '' }} ({{ $childEnrollment->campaign->name ?? '' }})
                                    </span>
                                @endif
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 text-xs text-gray-600 dark:text-gray-400 mt-3">
                                <div>
                                    <span class="font-bold text-gray-400 uppercase text-[9px] block tracking-wider">Contact</span>
                                    <span class="font-semibold text-gray-800 dark:text-gray-200">{{ $child->contact_number }}</span>
                                </div>
                                @if ($child->dob)
                                    <div>
                                        <span class="font-bold text-gray-400 uppercase text-[9px] block tracking-wider">Date of Birth</span>
                                        <span class="font-semibold text-gray-800 dark:text-gray-200">{{ \Carbon\Carbon::parse($child->dob)->format('d M, Y') }}</span>
                                    </div>
                                @endif
                                @if ($child->blood_group)
                                    <div>
                                        <span class="font-bold text-gray-400 uppercase text-[9px] block tracking-wider">Blood Group</span>
                                        <span class="font-semibold text-gray-800 dark:text-gray-200">{{ $child->blood_group }}</span>
                                    </div>
                                @endif
                                @if ($child->address)
                                    <div class="sm:col-span-2 md:col-span-3">
                                        <span class="font-bold text-gray-400 uppercase text-[9px] block tracking-wider">Address</span>
                                        <span class="font-semibold text-gray-800 dark:text-gray-200">{{ $child->address }}, {{ $child->pincode }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Bottom Card Actions -->
                        <div class="pt-4 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between">
                            <span class="text-[10px] font-mono font-bold tracking-widest text-gray-500 dark:text-gray-400">
                                ST-ID: #{{ $child->id }}
                            </span>
                            <div class="flex items-center gap-2">
                                @if ($childTemplate)
                                    <button wire:click="openPreviewIdCard({{ $child->id }})" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/60 dark:hover:bg-indigo-900/80 text-indigo-600 dark:text-indigo-400 border border-indigo-200/80 dark:border-indigo-700/60 rounded-xl text-xs font-bold transition shadow-sm cursor-pointer" title="{{ __('View ID Card') }}">
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        <span>{{ __('View ID Card') }}</span>
                                    </button>
                                @endif
                                <button wire:click="openEditChildModal({{ $child->id }})" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/60 dark:hover:bg-indigo-900/80 text-indigo-600 dark:text-indigo-400 border border-indigo-200/80 dark:border-indigo-700/60 rounded-xl text-xs font-bold transition shadow-sm cursor-pointer" title="{{ __('Edit Profile') }}">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                    <span>{{ __('Edit') }}</span>
                                </button>
                                <button wire:click="confirmDeleteChild({{ $child->id }})" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/60 dark:hover:bg-rose-900/80 text-rose-600 dark:text-rose-400 border border-rose-200/80 dark:border-rose-700/60 rounded-xl text-xs font-bold transition shadow-sm cursor-pointer" title="{{ __('Delete Profile') }}">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                    <span>{{ __('Delete') }}</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="bg-white dark:bg-gray-800 rounded-3xl p-8 border border-gray-100 dark:border-gray-700 text-center">
                    <p class="text-xs text-gray-400 italic">{{ __('No child profiles added yet. Click "Add Profile" to create one.') }}</p>
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

                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                        <!-- DOB -->
                        <div>
                            <x-input-label for="dob" :value="__('Date of Birth')" />
                            <x-text-input wire:model="dob" id="dob" type="date" class="mt-1 block w-full" required />
                            <x-input-error :messages="$errors->get('dob')" class="mt-2" />
                        </div>
                        <!-- Gender -->
                        <div>
                            <x-input-label for="gender" :value="__('Gender')" />
                            <select wire:model="gender" id="gender" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-xl shadow-sm">
                                <option value="">Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                            <x-input-error :messages="$errors->get('gender')" class="mt-2" />
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

                    <!-- Roll No & Serial/Ref No (Optional) -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <x-input-label for="enroll_roll_no" :value="__('Roll No (Optional)')" />
                            <x-text-input wire:model="enrollRollNo" id="enroll_roll_no" type="text" class="mt-1 block w-full" placeholder="e.g. 15" />
                            <x-input-error :messages="$errors->get('enrollRollNo')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="enroll_serial_number" :value="__('Ref / Serial No (Optional)')" />
                            <x-text-input wire:model="enrollSerialNumber" id="enroll_serial_number" type="text" class="mt-1 block w-full" placeholder="e.g. REF-101" />
                            <x-input-error :messages="$errors->get('enrollSerialNumber')" class="mt-1" />
                        </div>
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

    <!-- ID Card Preview Modal -->
    @if ($isPreviewIdCardOpen && $previewStudentId)
        @php
            $targetStudent = \App\Models\Student::with('campaignStudents.grade', 'campaignStudents.division', 'campaignStudents.campaign.school')->find($previewStudentId);
            $targetEnrollment = $targetStudent ? $targetStudent->campaignStudents->first() : null;
            $targetSchool = $targetEnrollment ? $targetEnrollment->campaign->school : null;
            $targetTemplate = $targetSchool ? $this->getEffectiveTemplate($targetSchool->id, $targetEnrollment->grade_id) : null;
        @endphp
        @if ($targetStudent && $targetTemplate)
            <div class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
                <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-md transition-opacity" wire:click="closePreviewIdCard"></div>

                <div class="bg-slate-900 border border-slate-800 rounded-3xl max-w-4xl w-full p-6 sm:p-8 space-y-6 shadow-2xl relative z-10 text-white">
                    <div class="flex items-center justify-between border-b border-slate-800 pb-4">
                        <div class="space-y-1">
                            <h3 class="text-xl font-black text-white flex items-center gap-2">
                                <span>🪪 Student ID Card Preview</span>
                            </h3>
                            <p class="text-xs text-slate-400">
                                {{ $targetStudent->first_name }} {{ $targetStudent->last_name }} &bull; Template: <strong class="text-indigo-400">{{ $targetTemplate->name }}</strong>
                            </p>
                        </div>
                        <button wire:click="closePreviewIdCard" class="text-slate-400 hover:text-white p-2 transition">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <div class="flex items-center justify-center p-8 bg-slate-950/80 rounded-2xl border border-slate-800/80 shadow-inner overflow-auto">
                        <x-id-card-renderer 
                            :template="$targetTemplate" 
                            :student="$targetStudent" 
                            :school="$targetSchool" 
                            :scale="($targetTemplate->orientation ?? 'landscape') === 'portrait' ? 0.65 : 0.75" 
                        />
                    </div>

                    <div class="flex items-center justify-end border-t border-slate-800 pt-4">
                        <button type="button" wire:click="closePreviewIdCard" class="px-5 py-2.5 bg-slate-800 hover:bg-slate-700 text-white rounded-xl font-bold text-xs uppercase transition">
                            {{ __('Close') }}
                        </button>
                    </div>
                </div>
            </div>
        @endif
    @endif
</div>
