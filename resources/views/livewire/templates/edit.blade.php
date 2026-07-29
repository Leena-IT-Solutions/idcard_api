<?php

use Livewire\Volt\Component;
use App\Models\School;
use App\Models\Grade;

new class extends Component {
    public string $templateId = 'premium-landscape';
    public $activeSchool = null;
    public $schoolGrades = [];

    // Editable branding
    public string $schoolName = '';
    public string $subtitle = 'High School';

    // Dynamic Custom Field Builder
    // Each item has 'label' and 'value_format' (which can use placeholders like {first_name}, {last_name}, {serial_number})
    public array $customFields = [
        ['label' => 'NAME', 'value_format' => '{full_name}'],
        ['label' => 'ID', 'value_format' => '#{serial_number}'],
        ['label' => 'D.O.B', 'value_format' => '{dob}'],
        ['label' => 'ADDRES', 'value_format' => '{address} {pincode}'],
    ];

    public bool $showBarcode = true;

    // Available variables list
    public array $availableVariables = [
        '{full_name}' => 'Full Name',
        '{first_name}' => 'First Name',
        '{middle_name}' => 'Middle Name',
        '{last_name}' => 'Last Name',
        '{serial_number}' => 'ID / Serial No',
        '{dob}' => 'Date of Birth',
        '{blood_group}' => 'Blood Group',
        '{contact_number}' => 'Contact Phone',
        '{address}' => 'Address',
        '{pincode}' => 'Pincode',
        '{grade}' => 'Class / Grade',
        '{division}' => 'Division',
        '{roll_no}' => 'Roll Number',
    ];

    public function mount($templateId = 'premium-landscape')
    {
        $this->templateId = $templateId;
        $activeSchoolId = session('active_school_id');
        if ($activeSchoolId) {
            $this->activeSchool = School::find($activeSchoolId);
        }

        if ($this->activeSchool) {
            $this->schoolName = $this->activeSchool->name ?? 'Sarvodaya Vidyalay';
        } else {
            $this->schoolName = 'Sarvodaya Vidyalay';
        }

        $this->loadGrades();
    }

    public function loadGrades()
    {
        $activeSchoolId = session('active_school_id');
        if ($activeSchoolId) {
            $this->schoolGrades = Grade::where('school_id', $activeSchoolId)
                ->orderBy('name', 'asc')
                ->get();
        } else {
            $this->schoolGrades = [];
        }
    }

    public function addField()
    {
        $this->customFields[] = [
            'label' => 'NEW FIELD',
            'value_format' => '{first_name}',
        ];
    }

    public function removeField($index)
    {
        if (isset($this->customFields[$index])) {
            array_splice($this->customFields, $index, 1);
        }
    }

    public function insertTag($index, $tag)
    {
        if (isset($this->customFields[$index])) {
            $this->customFields[$index]['value_format'] .= ' ' . $tag;
            $this->customFields[$index]['value_format'] = trim($this->customFields[$index]['value_format']);
        }
    }

    public function makeSchoolDefault()
    {
        $activeSchoolId = session('active_school_id');
        if (!$activeSchoolId) {
            $this->addError('general', 'No active school selected.');
            return;
        }

        $school = School::find($activeSchoolId);
        if ($school) {
            $school->update(['template_id' => $this->templateId]);
            $this->activeSchool = $school->fresh();
            session()->flash('message', 'Template set as School Default successfully!');
        }
    }

    public function assignToGrade($gradeId)
    {
        $grade = Grade::find($gradeId);
        if ($grade) {
            $newTemplateId = $grade->template_id == $this->templateId ? null : $this->templateId;
            $grade->update(['template_id' => $newTemplateId]);
            $this->loadGrades();
        }
    }

    public function saveCustomization()
    {
        session()->flash('message', 'Design customization saved successfully!');
    }

    public function with(): array
    {
        $isSchoolDefault = $this->activeSchool && $this->activeSchool->template_id == $this->templateId;

        $mockStudent = (object)[
            'first_name' => 'John',
            'middle_name' => 'A.',
            'last_name' => 'Doe',
            'dob' => '2015-05-15',
            'contact_number' => '9876543210',
            'blood_group' => 'A+',
            'address' => '123 Main Street',
            'pincode' => '400001',
            'photo_path' => '',
            'campaignStudents' => collect([
                (object)[
                    'grade' => (object)['name' => 'V'],
                    'division' => (object)['name' => 'A'],
                    'roll_no' => '42',
                    'serial_number' => 'SR-2026-042',
                ]
            ])
        ];

        $mockSchool = $this->activeSchool ?? (object)[
            'name' => $this->schoolName,
            'logo_path' => '',
            'school_code' => 'SV-99',
        ];

        return [
            'isSchoolDefault' => $isSchoolDefault,
            'mockStudent' => $mockStudent,
            'mockSchool' => $mockSchool,
        ];
    }
}; ?>

<div class="space-y-6">
    <!-- Session Message Alert -->
    @if (session()->has('message'))
        <div class="bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 p-4 rounded-2xl flex items-center justify-between text-sm font-semibold">
            <span>{{ session('message') }}</span>
            <button type="button" onclick="this.parentElement.remove()" class="text-emerald-400 hover:text-white">&times;</button>
        </div>
    @endif

    <!-- Top Action Bar -->
    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-5 flex flex-wrap items-center justify-between gap-4 shadow-xl">
        <div class="flex items-center space-x-4">
            <a href="{{ route('templates') }}" class="p-2.5 rounded-2xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white transition flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <div>
                <div class="flex items-center space-x-2">
                    <h1 class="text-lg font-black text-white">Premium Landscape Student ID</h1>
                    <span class="text-[10px] font-extrabold text-indigo-400 bg-indigo-500/10 border border-indigo-500/20 px-2.5 py-0.5 rounded-full uppercase tracking-wider">CR-80 Landscape</span>
                </div>
                <p class="text-xs text-slate-400 mt-0.5">Add custom field labels and map student/campaign variables dynamically</p>
            </div>
        </div>

        <div class="flex items-center space-x-3">
            @if(!$isSchoolDefault)
                <button type="button" wire:click="makeSchoolDefault" class="px-4 py-2.5 bg-slate-800 hover:bg-slate-700 text-white rounded-xl text-xs font-bold transition flex items-center">
                    <svg class="w-4 h-4 mr-1.5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Make School Default
                </button>
            @else
                <span class="px-3.5 py-2 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 rounded-xl text-xs font-bold flex items-center">
                    <svg class="w-4 h-4 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    Active School Default
                </span>
            @endif

            <button type="button" wire:click="saveCustomization" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition shadow-lg shadow-indigo-600/25">
                Save Design
            </button>
        </div>
    </div>

    <!-- Main Workspace Split -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start w-full">
        <!-- Left: Live Studio Canvas Preview (7 Cols) -->
        <div class="lg:col-span-7 bg-slate-900 border border-slate-800 rounded-3xl p-8 flex flex-col items-center justify-center min-h-[580px] shadow-2xl relative overflow-hidden">
            <div class="absolute top-5 left-5 flex items-center space-x-2">
                <span class="text-[10px] font-black uppercase tracking-widest text-indigo-400 bg-indigo-500/10 border border-indigo-500/20 px-3 py-1 rounded-lg">
                    Live Studio Canvas (100% Scale)
                </span>
            </div>

            <!-- ID Card Rendering Container -->
            <div class="my-auto py-10 transition duration-300 transform hover:scale-[1.02]">
                @include('id-card-templates.premium-landscape', [
                    'student' => $mockStudent, 
                    'school' => $mockSchool,
                    'customFields' => $customFields,
                    'showBarcode' => $showBarcode
                ])
            </div>

            <div class="absolute bottom-5 left-5 text-[11px] text-slate-500 flex items-center space-x-2">
                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>Dimensions: 85.6mm x 54mm (Standard CR-80 PVC Card)</span>
            </div>
        </div>

        <!-- Right: Dynamic Custom Field Builder (5 Cols) -->
        <div class="lg:col-span-5 space-y-5">
            <!-- Branding Panel -->
            <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-xl space-y-4">
                <div class="flex items-center space-x-2 border-b border-slate-800 pb-3">
                    <div class="p-2 rounded-xl bg-indigo-500/10 text-indigo-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </div>
                    <h3 class="text-sm font-black text-white">School Branding</h3>
                </div>

                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-400 mb-1">School Name</label>
                        <input type="text" wire:model.live="schoolName" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-xs font-bold text-white focus:outline-none focus:border-indigo-500 transition">
                    </div>
                </div>
            </div>

            <!-- Dynamic Custom Fields Builder Panel -->
            <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-xl space-y-4">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <div class="flex items-center space-x-2">
                        <div class="p-2 rounded-xl bg-purple-500/10 text-purple-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-black text-white">Dynamic Field Builder</h3>
                            <p class="text-[10px] text-slate-400 mt-0.5">Add as many custom fields as you need</p>
                        </div>
                    </div>

                    <button type="button" wire:click="addField" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition flex items-center shadow">
                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Add Field
                    </button>
                </div>

                <!-- Dynamic Field List -->
                <div class="space-y-4 max-h-[380px] overflow-y-auto pr-1">
                    @foreach($customFields as $index => $field)
                        <div class="bg-slate-950/80 border border-slate-800 rounded-2xl p-4 space-y-3 relative group">
                            <!-- Field Row Top Bar -->
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] font-black uppercase tracking-wider text-purple-400 bg-purple-500/10 px-2 py-0.5 rounded">
                                    Field #{{ $index + 1 }}
                                </span>
                                @if(count($customFields) > 1)
                                    <button type="button" wire:click="removeField({{ $index }})" class="text-slate-500 hover:text-red-400 transition p-1" title="Delete Field">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                @endif
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-400 mb-1">Label Text</label>
                                    <input type="text" wire:model.live="customFields.{{ $index }}.label" class="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-xs font-bold text-white focus:outline-none focus:border-indigo-500 transition" placeholder="e.g. NAME">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-400 mb-1">Value Pattern</label>
                                    <input type="text" wire:model.live="customFields.{{ $index }}.value_format" class="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-xs font-bold text-white focus:outline-none focus:border-indigo-500 transition" placeholder="e.g. {first_name} {last_name}">
                                </div>
                            </div>

                            <!-- Click to Insert Variables Pill Bar -->
                            <div>
                                <span class="block text-[9.5px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Insert Dynamic Tag:</span>
                                <div class="flex flex-wrap gap-1">
                                    @foreach($availableVariables as $tag => $name)
                                        <button type="button" wire:click="insertTag({{ $index }}, '{{ $tag }}')" 
                                            class="text-[9px] font-semibold text-slate-300 hover:text-white bg-slate-900 hover:bg-indigo-600/80 border border-slate-800 hover:border-indigo-500 px-2 py-0.5 rounded-lg transition">
                                            + {{ $tag }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Class Assignment Overrides Panel -->
            <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-xl space-y-4">
                <div class="flex items-center space-x-2 border-b border-slate-800 pb-3">
                    <div class="p-2 rounded-xl bg-emerald-500/10 text-emerald-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                    </div>
                    <h3 class="text-sm font-black text-white">Class Assignment Overrides</h3>
                </div>

                <div class="max-h-[220px] overflow-y-auto space-y-2 pr-1">
                    @forelse($schoolGrades as $grade)
                        <div class="bg-slate-950/60 border border-slate-800/80 rounded-2xl p-3 flex items-center justify-between">
                            <div>
                                <h4 class="text-xs font-bold text-white">{{ $grade->name }}</h4>
                                <p class="text-[10px] text-slate-500 mt-0.5">
                                    @if($grade->template_id == $templateId)
                                        <span class="text-indigo-400 font-semibold">Active Class Template</span>
                                    @else
                                        <span>Default / Inherited</span>
                                    @endif
                                </p>
                            </div>
                            <button type="button" wire:click="assignToGrade({{ $grade->id }})" 
                                class="px-3 py-1.5 rounded-xl text-[10px] font-bold tracking-wider uppercase transition 
                                {{ $grade->template_id == $templateId 
                                    ? 'bg-red-500/10 hover:bg-red-500/20 text-red-400 border border-red-500/20' 
                                    : 'bg-indigo-600 hover:bg-indigo-700 text-white' }}">
                                {{ $grade->template_id == $templateId ? 'Remove' : 'Assign' }}
                            </button>
                        </div>
                    @empty
                        <div class="text-center py-4 text-slate-500 text-xs">
                            No classes (grades) registered for this school.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
