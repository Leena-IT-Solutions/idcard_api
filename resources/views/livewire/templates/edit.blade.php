<?php

use Livewire\Volt\Component;
use App\Models\School;
use App\Models\Grade;

new class extends Component {
    public string $templateId = 'premium-landscape';
    public $activeSchool = null;
    public $schoolGrades = [];

    // Editable template properties
    public string $schoolName = '';
    public string $subtitle = 'High School';
    public string $photoBorderColor = '#E05B35';
    public string $nameLabel = 'NAME';
    public string $idLabel = 'ID';
    public string $dobLabel = 'D.O.B';
    public string $addressLabel = 'ADDRES';
    public bool $showBarcode = true;

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
                    'grade' => (object)['name' => 'Grade 5'],
                    'division' => (object)['name' => 'Div A'],
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
                <p class="text-xs text-slate-400 mt-0.5">Customize template design elements, branding, and class assignments</p>
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
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        <!-- Left: Live Studio Canvas Preview (7 Cols) -->
        <div class="lg:col-span-7 bg-slate-900 border border-slate-800 rounded-3xl p-8 flex flex-col items-center justify-center min-h-[480px] shadow-xl relative overflow-hidden">
            <div class="absolute top-4 left-4 text-[10px] font-black uppercase tracking-widest text-slate-500 bg-slate-950/60 px-3 py-1 rounded-lg border border-slate-800">
                Live Studio Canvas (100% Scale)
            </div>

            <!-- ID Card Rendering Container -->
            <div class="my-auto py-8">
                @include('id-card-templates.premium-landscape', ['student' => $mockStudent, 'school' => $mockSchool])
            </div>

            <div class="text-[11px] text-slate-500 flex items-center space-x-2 mt-2">
                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>Dimensions: 85.6mm x 54mm (Standard CR-80 PVC Card)</span>
            </div>
        </div>

        <!-- Right: Design Customization Controls (5 Cols) -->
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
                    <div>
                        <label class="block text-xs font-bold text-slate-400 mb-1">School Subtitle</label>
                        <input type="text" wire:model.live="subtitle" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-xs font-bold text-white focus:outline-none focus:border-indigo-500 transition">
                    </div>
                </div>
            </div>

            <!-- Field Labels Panel -->
            <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-xl space-y-4">
                <div class="flex items-center space-x-2 border-b border-slate-800 pb-3">
                    <div class="p-2 rounded-xl bg-purple-500/10 text-purple-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                        </svg>
                    </div>
                    <h3 class="text-sm font-black text-white">Field Labels</h3>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-400 mb-1">Name Label</label>
                        <input type="text" wire:model.live="nameLabel" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs font-bold text-white focus:outline-none focus:border-indigo-500 transition">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-400 mb-1">ID Label</label>
                        <input type="text" wire:model.live="idLabel" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs font-bold text-white focus:outline-none focus:border-indigo-500 transition">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-400 mb-1">D.O.B Label</label>
                        <input type="text" wire:model.live="dobLabel" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs font-bold text-white focus:outline-none focus:border-indigo-500 transition">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-400 mb-1">Address Label</label>
                        <input type="text" wire:model.live="addressLabel" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs font-bold text-white focus:outline-none focus:border-indigo-500 transition">
                    </div>
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
