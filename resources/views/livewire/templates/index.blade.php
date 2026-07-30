<?php

use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use App\Models\Template;
use App\Models\SchoolTemplate;
use App\Models\School;
use App\Models\Grade;

new class extends Component {
    use WithFileUploads;

    public string $search = '';
    public string $selectedCategory = 'all';

    // Modal properties
    public bool $isAssignModalOpen = false;
    public $selectedTemplateForAssign = null; // Can be Template or SchoolTemplate
    public bool $isSchoolTemplate = false;
    public $schoolGrades = [];
    public ?string $assignSuccessMessage = null;

    // Preview properties
    public bool $isPreviewModalOpen = false;
    public $selectedTemplateForPreview = null;

    // JSON Import properties
    public bool $isImportModalOpen = false;
    public $importFile = null;
    public string $importName = '';

    // Create Modal properties
    public bool $isCreateModalOpen = false;

    public function mount()
    {
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

    public function customizeMasterTemplate($templateId)
    {
        $activeSchoolId = session('active_school_id');
        if (!$activeSchoolId) {
            $this->addError('general', 'Please select an active school first.');
            return;
        }

        $master = Template::find($templateId);
        if (!$master) {
            $this->addError('general', 'Template not found.');
            return;
        }

        // Create a new customized school template
        $schoolTemplate = SchoolTemplate::create([
            'school_id' => $activeSchoolId,
            'template_id' => $master->id,
            'name' => $master->name . ' (' . session('active_school_name', 'Custom') . ')',
            'orientation' => $master->orientation,
            'width_mm' => $master->width_mm,
            'height_mm' => $master->height_mm,
            'background_image' => $master->background_image,
            'background_back_image' => $master->background_back_image,
            'layout_config' => $master->layout_config,
            'is_default' => false,
        ]);

        return redirect()->route('templates.edit', ['template' => $schoolTemplate->id, 'type' => 'school']);
    }

    public function createBlankSchoolTemplate($orientation = 'landscape')
    {
        $activeSchoolId = session('active_school_id');
        if (!$activeSchoolId) {
            $this->addError('general', 'Please select an active school first.');
            return;
        }

        $isPortrait = $orientation === 'portrait';
        $width = $isPortrait ? 54.00 : 85.60;
        $height = $isPortrait ? 85.60 : 54.00;

        $defaultMaster = Template::where('orientation', $orientation)->first() ?? Template::first();
        $defaultConfig = $defaultMaster ? $defaultMaster->layout_config : [];

        $schoolTemplate = SchoolTemplate::create([
            'school_id' => $activeSchoolId,
            'template_id' => $defaultMaster ? $defaultMaster->id : null,
            'name' => 'Custom ' . ucfirst($orientation) . ' ID Card',
            'orientation' => $orientation,
            'width_mm' => $width,
            'height_mm' => $height,
            'background_image' => null,
            'layout_config' => $defaultConfig,
            'is_default' => false,
        ]);

        return redirect()->route('templates.edit', ['template' => $schoolTemplate->id, 'type' => 'school']);
    }

    public function assignToSchool($templateId, $isSchoolTpl = false)
    {
        $activeSchoolId = session('active_school_id');
        if (!$activeSchoolId) {
            $this->addError('general', 'No active school selected.');
            return;
        }

        $school = School::find($activeSchoolId);
        if ($school) {
            $school->update(['template_id' => $templateId]);
            if ($isSchoolTpl) {
                SchoolTemplate::where('school_id', $activeSchoolId)->update(['is_default' => false]);
                SchoolTemplate::where('id', $templateId)->update(['is_default' => true]);
                $this->selectedTemplateForAssign = SchoolTemplate::find($templateId);
            } else {
                $this->selectedTemplateForAssign = Template::find($templateId);
            }

            $templateName = $this->selectedTemplateForAssign ? $this->selectedTemplateForAssign->name : 'Template';
            $this->assignSuccessMessage = '✓ Assigned "' . $templateName . '" as Default School Template for ' . $school->name . '!';
            session()->flash('message', 'Default school ID card template updated successfully!');
        }
    }

    public function deleteSchoolTemplate($templateId)
    {
        $activeSchoolId = session('active_school_id');
        if (!$activeSchoolId) return;

        $st = SchoolTemplate::where('school_id', $activeSchoolId)->where('id', $templateId)->first();
        if ($st) {
            $st->delete();
            session()->flash('message', 'School template deleted successfully!');
        }
    }

    public function openAssignModal($templateId, $isSchoolTpl = false)
    {
        $this->isSchoolTemplate = $isSchoolTpl;
        $this->assignSuccessMessage = null;
        if ($isSchoolTpl) {
            $this->selectedTemplateForAssign = SchoolTemplate::find($templateId);
        } else {
            $this->selectedTemplateForAssign = Template::find($templateId);
        }
        $this->loadGrades();
        $this->isAssignModalOpen = true;
    }

    public function closeAssignModal()
    {
        $this->isAssignModalOpen = false;
        $this->selectedTemplateForAssign = null;
        $this->assignSuccessMessage = null;
    }

    public function assignToGrade($gradeId, $templateId, $isSchoolTpl = false)
    {
        $grade = Grade::find($gradeId);
        if ($grade) {
            if ($isSchoolTpl) {
                $grade->update(['school_template_id' => $templateId]);
            } else {
                $grade->update(['template_id' => $templateId]);
            }
            $this->loadGrades();

            $templateName = $this->selectedTemplateForAssign ? $this->selectedTemplateForAssign->name : 'Template';
            $this->assignSuccessMessage = '✓ Assigned "' . $templateName . '" to Grade ' . $grade->name . ' successfully!';
            session()->flash('message', 'Template assigned to grade successfully!');
        }
    }

    public function openPreviewModal($templateId, $isSchoolTpl = false)
    {
        if ($isSchoolTpl) {
            $this->selectedTemplateForPreview = SchoolTemplate::find($templateId);
        } else {
            $this->selectedTemplateForPreview = Template::find($templateId);
        }
        $this->isPreviewModalOpen = true;
    }

    public function closePreviewModal()
    {
        $this->isPreviewModalOpen = false;
        $this->selectedTemplateForPreview = null;
    }

    public function exportJson($templateId, $isSchoolTpl = false)
    {
        $tpl = $isSchoolTpl ? SchoolTemplate::find($templateId) : Template::find($templateId);
        if (!$tpl) return;

        $data = [
            'name' => $tpl->name,
            'orientation' => $tpl->orientation,
            'width_mm' => $tpl->width_mm,
            'height_mm' => $tpl->height_mm,
            'layout_config' => $tpl->layout_config,
            'exported_at' => now()->toIso8601String(),
        ];

        $filename = \Illuminate\Support\Str::slug($tpl->name) . '-template.json';
        return response()->streamDownload(function() use ($data) {
            echo json_encode($data, JSON_PRETTY_PRINT);
        }, $filename, ['Content-Type' => 'application/json']);
    }

    public function importJsonTemplate()
    {
        $this->validate([
            'importName' => 'required|string|max:255',
            'importFile' => 'required|file|mimes:json,txt',
        ]);

        $activeSchoolId = session('active_school_id');
        if (!$activeSchoolId) {
            $this->addError('general', 'No active school selected.');
            return;
        }

        $jsonContent = file_get_contents($this->importFile->getRealPath());
        $parsed = json_decode($jsonContent, true);

        if (!$parsed || !is_array($parsed)) {
            $this->addError('importFile', 'Invalid JSON file structure.');
            return;
        }

        $orientation = $parsed['orientation'] ?? 'landscape';
        $width = $parsed['width_mm'] ?? ($orientation === 'portrait' ? 54.00 : 85.60);
        $height = $parsed['height_mm'] ?? ($orientation === 'portrait' ? 85.60 : 54.00);
        $layoutConfig = $parsed['layout_config'] ?? [];

        $schoolTemplate = SchoolTemplate::create([
            'school_id' => $activeSchoolId,
            'name' => $this->importName,
            'orientation' => $orientation,
            'width_mm' => $width,
            'height_mm' => $height,
            'layout_config' => $layoutConfig,
            'is_default' => false,
        ]);

        $this->isImportModalOpen = false;
        $this->importFile = null;
        $this->importName = '';

        session()->flash('message', 'Template imported successfully!');
        return redirect()->route('templates.edit', ['template' => $schoolTemplate->id, 'type' => 'school']);
    }

    public function with(): array
    {
        $activeSchoolId = session('active_school_id');
        $activeSchool = $activeSchoolId ? School::find($activeSchoolId) : null;

        $masterQuery = Template::where('is_active', true);
        $schoolQuery = $activeSchoolId ? SchoolTemplate::where('school_id', $activeSchoolId) : SchoolTemplate::whereRaw('1 = 0');

        if ($this->search) {
            $s = '%' . $this->search . '%';
            $masterQuery->where('name', 'like', $s);
            $schoolQuery->where('name', 'like', $s);
        }

        if ($this->selectedCategory === 'landscape') {
            $masterQuery->where('orientation', 'landscape');
            $schoolQuery->where('orientation', 'landscape');
        } elseif ($this->selectedCategory === 'portrait') {
            $masterQuery->where('orientation', 'portrait');
            $schoolQuery->where('orientation', 'portrait');
        }

        $masterTemplates = $masterQuery->get();
        $schoolTemplates = $schoolQuery->get();

        // Sample student mock data for previews
        $mockStudent = (object)[
            'first_name' => 'Aaditya',
            'middle_name' => 'Sonu',
            'last_name' => 'Thakur',
            'dob' => '2017-10-27',
            'contact_number' => '9730777244',
            'blood_group' => 'AB+',
            'gender' => 'Male',
            'address' => 'Sarvodhya Nagar Phase 3 Flat No 704',
            'pincode' => '400001',
            'photo_path' => '',
            'campaignStudents' => collect([
                (object)[
                    'grade' => (object)['name' => 'V'],
                    'division' => (object)['name' => 'B'],
                    'roll_no' => '202',
                    'serial_number' => '202',
                ]
            ])
        ];

        return [
            'masterTemplates' => $masterTemplates,
            'schoolTemplates' => $schoolTemplates,
            'activeSchool' => $activeSchool,
            'mockStudent' => $mockStudent,
        ];
    }
}; ?>

<div class="space-y-8">
    @if(session()->has('message'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 dark:bg-emerald-500/10 dark:border-emerald-500/20 dark:text-emerald-400 p-4 rounded-2xl flex items-center justify-between text-sm font-semibold shadow-sm">
            <div class="flex items-center space-x-2">
                <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span>{{ session('message') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-emerald-600 dark:text-emerald-400 hover:text-slate-900 dark:hover:text-white transition">&times;</button>
        </div>
    @endif

    <!-- Header & Action Bar -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-900 dark:text-white rounded-3xl p-6 md:p-8 flex flex-wrap items-center justify-between gap-6 shadow-md dark:shadow-2xl relative overflow-hidden backdrop-blur-xl">
        <div class="absolute -right-16 -top-16 w-64 h-64 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="relative z-10 space-y-2">
            <div class="flex items-center space-x-3">
                <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">ID Card Template Studio</h1>
                <span class="text-xs font-black text-indigo-700 dark:text-indigo-300 bg-indigo-50 dark:bg-indigo-500/20 border border-indigo-200 dark:border-indigo-500/30 px-3 py-1 rounded-full uppercase tracking-wider shadow-sm">
                    Canva Studio ⚡
                </span>
            </div>
            <p class="text-xs md:text-sm text-slate-600 dark:text-slate-400 max-w-xl">Design pixel-perfect ID card templates for your school, customize preset layouts, or import custom JSON configurations.</p>
        </div>

        <div class="relative z-10 flex items-center space-x-3">
            <button type="button" wire:click="$set('isImportModalOpen', true)" class="px-4 py-3 bg-slate-100 hover:bg-slate-200 text-slate-800 border border-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-200 dark:border-slate-700/60 rounded-2xl text-xs font-extrabold transition-all duration-200 flex items-center shadow-sm">
                <svg class="w-4 h-4 mr-2 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                </svg>
                Import JSON Template
            </button>

            <button type="button" wire:click="$set('isCreateModalOpen', true)" class="px-5 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-2xl text-xs font-black transition-all duration-200 flex items-center shadow-lg shadow-indigo-600/20 hover:scale-105 active:scale-95">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                </svg>
                + Create Custom Design
            </button>
        </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="flex flex-wrap items-center justify-between gap-4 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm rounded-2xl p-4">
        <div class="flex items-center space-x-2 overflow-x-auto pb-1 md:pb-0">
            <button type="button" wire:click="$set('selectedCategory', 'all')" class="px-4 py-2 rounded-xl text-xs font-bold transition {{ $selectedCategory === 'all' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/20' : 'bg-slate-100 text-slate-700 hover:bg-slate-200 hover:text-slate-900 dark:bg-slate-950 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-white' }}">
                All Templates
            </button>
            <button type="button" wire:click="$set('selectedCategory', 'landscape')" class="px-4 py-2 rounded-xl text-xs font-bold transition {{ $selectedCategory === 'landscape' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/20' : 'bg-slate-100 text-slate-700 hover:bg-slate-200 hover:text-slate-900 dark:bg-slate-950 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-white' }}">
                Landscape 📐
            </button>
            <button type="button" wire:click="$set('selectedCategory', 'portrait')" class="px-4 py-2 rounded-xl text-xs font-bold transition {{ $selectedCategory === 'portrait' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/20' : 'bg-slate-100 text-slate-700 hover:bg-slate-200 hover:text-slate-900 dark:bg-slate-950 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-white' }}">
                Portrait 📱
            </button>
        </div>

        <div class="relative min-w-[240px]">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search templates by name..." class="w-full bg-slate-50 border border-slate-200 text-slate-900 placeholder-slate-400 dark:bg-slate-950 dark:border-slate-800 dark:text-white dark:placeholder-slate-500 rounded-xl pl-9 pr-4 py-2 text-xs font-bold focus:outline-none focus:border-indigo-500 transition">
            <svg class="w-4 h-4 text-slate-400 dark:text-slate-500 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        </div>
    </div>

    <!-- School Specific Custom Templates Section -->
    @if($schoolTemplates->isNotEmpty())
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-base font-black text-slate-900 dark:text-white flex items-center space-x-2">
                    <span class="w-3 h-3 rounded-full bg-amber-400 shadow-sm shadow-amber-400/50"></span>
                    <span>Your School Custom Templates</span>
                </h2>
                <span class="text-xs text-slate-600 dark:text-slate-400 font-bold bg-slate-100 border border-slate-200 dark:bg-slate-900 dark:border-slate-800 px-3 py-1 rounded-full">{{ $schoolTemplates->count() }} Custom Template(s)</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($schoolTemplates as $st)
                    @php
                        $isPortrait = $st->orientation === 'portrait';
                        $isDefault = ($activeSchool && $activeSchool->template_id == $st->id) || $st->is_default;
                        $scale = $isPortrait ? 0.25 : 0.31;
                    @endphp
                    <div class="bg-white border border-slate-200 hover:border-indigo-500/50 text-slate-900 shadow-md hover:shadow-xl dark:bg-slate-900 dark:border-slate-800 dark:hover:border-indigo-500/50 dark:text-white rounded-3xl p-5 transition-all duration-300 flex flex-col justify-between group relative overflow-hidden">
                        <div>
                            <!-- Header Info -->
                            <div class="flex items-start justify-between mb-4">
                                <div class="space-y-1">
                                    <h3 class="text-sm font-black text-slate-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition leading-snug">{{ $st->name }}</h3>
                                    <div class="flex items-center space-x-2">
                                        <span class="text-[10px] text-slate-600 dark:text-slate-400 font-extrabold uppercase tracking-wider bg-slate-100 border border-slate-200 dark:bg-slate-950 dark:border-slate-800 px-2 py-0.5 rounded-md">
                                            {{ $st->orientation }} • {{ $st->width_mm }}×{{ $st->height_mm }}mm
                                        </span>
                                    </div>
                                </div>
                                @if($isDefault)
                                    <span class="text-[10px] font-black text-emerald-700 dark:text-emerald-400 bg-emerald-50 border border-emerald-200 dark:bg-emerald-500/15 dark:border-emerald-500/30 px-2.5 py-1 rounded-full uppercase tracking-wider shrink-0 shadow-sm">
                                        Default ⚡
                                    </span>
                                @endif
                            </div>

                            <!-- Canvas Stage & Card Thumbnail -->
                            <div class="relative w-full bg-slate-100 border border-slate-200 dark:bg-slate-950 dark:border-slate-800/80 rounded-2xl p-4 mb-4 flex items-center justify-center min-h-[220px] overflow-hidden group-hover:border-indigo-500/40 transition-all duration-300 bg-[radial-gradient(#cbd5e1_1px,transparent_1px)] dark:bg-[radial-gradient(#334155_1px,transparent_1px)] [background-size:16px_16px]">
                                <x-id-card-renderer :template="$st" :student="$mockStudent" :school="$activeSchool" :scale="$scale" />

                                <!-- Hover Quick Overlay -->
                                <div class="absolute inset-0 bg-slate-900/80 dark:bg-slate-950/80 backdrop-blur-sm opacity-0 group-hover:opacity-100 transition-all duration-300 flex flex-col items-center justify-center space-y-2.5 p-4 z-10">
                                    <a href="{{ route('templates.edit', ['template' => $st->id, 'type' => 'school']) }}" class="w-36 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-black transition flex items-center justify-center shadow-lg shadow-indigo-600/30">
                                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        Edit Studio
                                    </a>
                                    <button type="button" wire:click="openPreviewModal({{ $st->id }}, true)" class="w-36 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-xl text-xs font-bold transition flex items-center justify-center border border-slate-700">
                                        <svg class="w-3.5 h-3.5 mr-1.5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        Full Preview
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Card Action Buttons -->
                        <div class="space-y-2 pt-3 border-t border-slate-100 dark:border-slate-800/80">
                            <div class="grid grid-cols-2 gap-2">
                                <a href="{{ route('templates.edit', ['template' => $st->id, 'type' => 'school']) }}" class="py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-black transition flex items-center justify-center shadow-md shadow-indigo-600/20">
                                    <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                    Edit Studio
                                </a>

                                <button type="button" wire:click="openAssignModal({{ $st->id }}, true)" class="py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-800 border border-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-200 dark:border-slate-700/60 rounded-xl text-xs font-extrabold transition flex items-center justify-center">
                                    <svg class="w-3.5 h-3.5 mr-1.5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                                    </svg>
                                    <span class="text-slate-800 dark:text-slate-200 font-extrabold">Assign Grade</span>
                                </button>
                            </div>

                            <div class="flex items-center justify-between space-x-2 pt-1">
                                <button type="button" wire:click="exportJson({{ $st->id }}, true)" class="flex-1 py-1.5 bg-slate-50 hover:bg-slate-100 text-slate-700 hover:text-slate-900 border border-slate-200 dark:bg-slate-950 dark:hover:bg-slate-800 dark:text-slate-300 dark:hover:text-white dark:border-slate-800 rounded-xl text-[11px] font-semibold transition flex items-center justify-center">
                                    <svg class="w-3.5 h-3.5 mr-1 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                    </svg>
                                    Export JSON
                                </button>

                                <button type="button" wire:click="deleteSchoolTemplate({{ $st->id }})" wire:confirm="Are you sure you want to delete this template?" class="px-3 py-1.5 bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 dark:bg-red-500/10 dark:hover:bg-red-500/20 dark:text-red-400 dark:border-red-500/20 rounded-xl text-[11px] font-bold transition flex items-center" title="Delete Template">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- System Master Presets Section -->
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-base font-black text-slate-900 dark:text-white flex items-center space-x-2">
                <span class="w-3 h-3 rounded-full bg-indigo-500 shadow-sm shadow-indigo-500/50"></span>
                <span>System Master ID Presets</span>
            </h2>
            <span class="text-xs text-slate-600 dark:text-slate-400 font-bold bg-slate-100 border border-slate-200 dark:bg-slate-900 dark:border-slate-800 px-3 py-1 rounded-full">Standard Presets</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($masterTemplates as $tpl)
                @php
                    $isPortrait = $tpl->orientation === 'portrait';
                    $scale = $isPortrait ? 0.25 : 0.31;
                @endphp
                <div class="bg-white border border-slate-200 hover:border-indigo-500/50 text-slate-900 shadow-md hover:shadow-xl dark:bg-slate-900 dark:border-slate-800 dark:hover:border-indigo-500/50 dark:text-white rounded-3xl p-5 transition-all duration-300 flex flex-col justify-between group relative overflow-hidden">
                    <div>
                        <!-- Header Info -->
                        <div class="flex items-start justify-between mb-4">
                            <div class="space-y-1">
                                <h3 class="text-sm font-black text-slate-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition leading-snug">{{ $tpl->name }}</h3>
                                <span class="text-[10px] text-slate-600 dark:text-slate-400 font-extrabold uppercase tracking-wider bg-slate-100 border border-slate-200 dark:bg-slate-950 dark:border-slate-800 px-2 py-0.5 rounded-md">
                                    {{ $tpl->orientation }} • {{ $tpl->width_mm }}×{{ $tpl->height_mm }}mm
                                </span>
                            </div>
                            <span class="text-[10px] font-black text-indigo-700 dark:text-indigo-400 bg-indigo-50 border border-indigo-200 dark:bg-indigo-500/10 dark:border-indigo-500/20 px-2.5 py-1 rounded-full uppercase tracking-wider shrink-0 shadow-sm">
                                Master
                            </span>
                        </div>

                        <!-- Canvas Stage & Card Thumbnail -->
                        <div class="relative w-full bg-slate-100 border border-slate-200 dark:bg-slate-950 dark:border-slate-800/80 rounded-2xl p-4 mb-4 flex items-center justify-center min-h-[220px] overflow-hidden group-hover:border-indigo-500/40 transition-all duration-300 bg-[radial-gradient(#cbd5e1_1px,transparent_1px)] dark:bg-[radial-gradient(#334155_1px,transparent_1px)] [background-size:16px_16px]">
                            <x-id-card-renderer :template="$tpl" :student="$mockStudent" :school="$activeSchool" :scale="$scale" />

                            <!-- Hover Quick Overlay -->
                            <div class="absolute inset-0 bg-slate-900/80 dark:bg-slate-950/80 backdrop-blur-sm opacity-0 group-hover:opacity-100 transition-all duration-300 flex flex-col items-center justify-center space-y-2.5 p-4 z-10">
                                <button type="button" wire:click="customizeMasterTemplate('{{ $tpl->id }}')" class="w-36 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-black transition flex items-center justify-center shadow-lg shadow-indigo-600/30">
                                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    Customize
                                </button>
                                <button type="button" wire:click="openPreviewModal('{{ $tpl->id }}', false)" class="w-36 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-xl text-xs font-bold transition flex items-center justify-center border border-slate-700">
                                    <svg class="w-3.5 h-3.5 mr-1.5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    Full Preview
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="space-y-2 pt-3 border-t border-slate-100 dark:border-slate-800/80">
                        <div class="grid grid-cols-2 gap-2">
                            <button type="button" wire:click="customizeMasterTemplate('{{ $tpl->id }}')" class="py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-black transition flex items-center justify-center shadow-md shadow-indigo-600/20">
                                <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                                Customize
                            </button>

                            <button type="button" wire:click="openAssignModal('{{ $tpl->id }}', false)" class="py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-800 border border-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-200 dark:border-slate-700/60 rounded-xl text-xs font-extrabold transition flex items-center justify-center">
                                <svg class="w-3.5 h-3.5 mr-1.5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                                </svg>
                                <span class="text-slate-800 dark:text-slate-200 font-extrabold">Assign Grade</span>
                            </button>
                        </div>

                        <button type="button" wire:click="exportJson('{{ $tpl->id }}', false)" class="w-full text-center py-1.5 bg-slate-50 hover:bg-slate-100 text-slate-700 hover:text-slate-900 border border-slate-200 dark:bg-slate-950 dark:hover:bg-slate-800 dark:text-slate-300 dark:hover:text-white dark:border-slate-800 rounded-xl text-[11px] font-semibold transition flex items-center justify-center">
                            <svg class="w-3.5 h-3.5 mr-1 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            Export JSON Layout
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Modal: Preview Template -->
    @if($isPreviewModalOpen && $selectedTemplateForPreview)
        @php
            $isPortrait = $selectedTemplateForPreview->orientation === 'portrait';
            $previewScale = $isPortrait ? 0.45 : 0.65;
        @endphp
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/40 dark:bg-black/80 backdrop-blur-md flex items-center justify-center p-4">
            <div class="bg-white border border-slate-200 text-slate-900 shadow-2xl dark:bg-slate-900 dark:border-slate-800 dark:text-white rounded-3xl max-w-2xl w-full p-6 space-y-6">
                <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-4">
                    <div>
                        <h3 class="text-base font-extrabold text-slate-900 dark:text-white">{{ $selectedTemplateForPreview->name }}</h3>
                        <p class="text-xs text-slate-600 dark:text-slate-400 mt-0.5">{{ $selectedTemplateForPreview->orientation }} • {{ $selectedTemplateForPreview->width_mm }}×{{ $selectedTemplateForPreview->height_mm }}mm</p>
                    </div>
                    <button type="button" wire:click="closePreviewModal" class="text-slate-400 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white text-xl font-bold">&times;</button>
                </div>

                <div class="bg-slate-100 border border-slate-200 dark:bg-slate-950 dark:border-slate-800 rounded-2xl p-6 flex items-center justify-center min-h-[320px] overflow-hidden bg-[radial-gradient(#cbd5e1_1px,transparent_1px)] dark:bg-[radial-gradient(#334155_1px,transparent_1px)] [background-size:16px_16px]">
                    <x-id-card-renderer :template="$selectedTemplateForPreview" :student="$mockStudent" :school="$activeSchool" :scale="$previewScale" />
                </div>

                <div class="flex items-center justify-between border-t border-slate-200 dark:border-slate-800 pt-4">
                    <button type="button" wire:click="exportJson({{ $selectedTemplateForPreview->id }}, {{ $isSchoolTemplate ? 'true' : 'false' }})" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 dark:border-slate-700/60 rounded-xl text-xs font-bold transition flex items-center">
                        <svg class="w-4 h-4 mr-1.5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        Export JSON
                    </button>
                    <button type="button" wire:click="closePreviewModal" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition">
                        Close Preview
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal: Assign Template -->
    @if($isAssignModalOpen && $selectedTemplateForAssign)
        @php
            $isSchoolDefault = $activeSchool && (
                $isSchoolTemplate 
                    ? ($activeSchool->template_id == $selectedTemplateForAssign->id || $selectedTemplateForAssign->is_default)
                    : ($activeSchool->template_id == $selectedTemplateForAssign->id)
            );
        @endphp
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/40 dark:bg-black/80 backdrop-blur-md flex items-center justify-center p-4">
            <div class="bg-white border border-slate-200 text-slate-900 shadow-2xl dark:bg-slate-900 dark:border-slate-800 dark:text-white rounded-3xl max-w-lg w-full p-6 space-y-5">
                <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-4">
                    <div>
                        <h3 class="text-base font-extrabold text-slate-900 dark:text-white">Assign Template</h3>
                        <p class="text-xs text-slate-600 dark:text-slate-400 mt-0.5">{{ $selectedTemplateForAssign->name }}</p>
                    </div>
                    <button type="button" wire:click="closeAssignModal" class="text-slate-400 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white text-xl font-bold">&times;</button>
                </div>

                @if($assignSuccessMessage)
                    <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 dark:bg-emerald-950/90 dark:border-emerald-500/60 dark:text-emerald-100 p-3.5 rounded-2xl flex items-center justify-between text-xs font-bold shadow-sm">
                        <div class="flex items-center space-x-2">
                            <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            <span class="font-extrabold">{{ $assignSuccessMessage }}</span>
                        </div>
                        <button type="button" wire:click="$set('assignSuccessMessage', null)" class="text-emerald-600 dark:text-emerald-300 hover:text-slate-900 dark:hover:text-white transition">&times;</button>
                    </div>
                @endif

                <!-- Option 1: School Default -->
                <div class="bg-slate-50 border border-slate-200 dark:bg-slate-950 dark:border-slate-800 rounded-2xl p-4 flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold text-slate-900 dark:text-white block">Make Default School Template</span>
                        <span class="text-[11px] text-slate-600 dark:text-slate-400">Applies to all grades unless overridden</span>
                    </div>
                    @if($isSchoolDefault)
                        <span class="px-4 py-2 bg-emerald-50 border border-emerald-200 text-emerald-700 dark:bg-emerald-900/80 dark:border-emerald-500/60 dark:text-emerald-300 rounded-xl text-xs font-black flex items-center space-x-1.5 shadow-sm">
                            <svg class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                            <span class="font-black">✓ Active Default</span>
                        </span>
                    @else
                        <button type="button" wire:click="assignToSchool('{{ $selectedTemplateForAssign->id }}', {{ $isSchoolTemplate ? 'true' : 'false' }})" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-extrabold transition shadow-md shadow-indigo-600/20 active:scale-95">
                            Set School Default
                        </button>
                    @endif
                </div>

                <!-- Option 2: Grade Specific -->
                <div class="space-y-3">
                    <span class="text-xs font-bold text-slate-800 dark:text-slate-300 block">Or Assign to Specific Grade:</span>
                    <div class="max-h-60 overflow-y-auto space-y-2 pr-1">
                        @foreach($schoolGrades as $g)
                            @php
                                $isAssigned = $isSchoolTemplate 
                                    ? ($g->school_template_id == $selectedTemplateForAssign->id)
                                    : ($g->template_id == $selectedTemplateForAssign->id);
                            @endphp
                            <div class="bg-slate-50 border border-slate-200 dark:bg-slate-950/70 dark:border-slate-800/80 rounded-2xl p-3 flex items-center justify-between">
                                <div>
                                    <span class="text-xs font-bold text-slate-900 dark:text-white">Grade {{ $g->name }}</span>
                                    @if($isAssigned)
                                        <span class="text-[10px] text-emerald-600 dark:text-emerald-400 font-bold ml-2">● Currently Assigned</span>
                                    @endif
                                </div>
                                @if($isAssigned)
                                    <button type="button" wire:click="assignToGrade({{ $g->id }}, '{{ $selectedTemplateForAssign->id }}', {{ $isSchoolTemplate ? 'true' : 'false' }})" class="px-3.5 py-1.5 bg-emerald-50 border border-emerald-200 text-emerald-700 dark:bg-emerald-900/80 dark:border-emerald-500/60 dark:text-emerald-300 rounded-xl text-xs font-bold transition flex items-center space-x-1">
                                        <svg class="w-3 h-3 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                        <span class="font-bold">✓ Assigned</span>
                                    </button>
                                @else
                                    <button type="button" wire:click="assignToGrade({{ $g->id }}, '{{ $selectedTemplateForAssign->id }}', {{ $isSchoolTemplate ? 'true' : 'false' }})" class="px-3.5 py-1.5 bg-slate-200 hover:bg-slate-300 text-slate-800 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-200 rounded-xl text-xs font-semibold transition active:scale-95">
                                        Assign to Grade {{ $g->name }}
                                    </button>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="flex justify-end pt-2">
                    <button type="button" wire:click="closeAssignModal" class="px-5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 rounded-xl text-xs font-bold transition">
                        Done / Close
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal: Import JSON Template -->
    @if($isImportModalOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/40 dark:bg-black/80 backdrop-blur-md flex items-center justify-center p-4">
            <div class="bg-white border border-slate-200 text-slate-900 shadow-2xl dark:bg-slate-900 dark:border-slate-800 dark:text-white rounded-3xl max-w-md w-full p-6 space-y-5">
                <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-4">
                    <h3 class="text-base font-extrabold text-slate-900 dark:text-white">Import JSON Template</h3>
                    <button type="button" wire:click="$set('isImportModalOpen', false)" class="text-slate-400 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white text-xl font-bold">&times;</button>
                </div>

                <form wire:submit.prevent="importJsonTemplate" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-400 mb-1">Template Name</label>
                        <input type="text" wire:model="importName" placeholder="e.g. Custom Science ID Card" class="w-full bg-slate-50 border border-slate-200 text-slate-900 dark:bg-slate-950 dark:border-slate-800 dark:text-white rounded-xl px-3.5 py-2.5 text-xs font-bold focus:outline-none focus:border-indigo-500 transition">
                        @error('importName') <span class="text-xs text-red-500 dark:text-red-400 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-400 mb-1">JSON Template File (.json)</label>
                        <input type="file" wire:model="importFile" accept=".json" class="w-full bg-slate-50 border border-slate-200 text-slate-900 dark:bg-slate-950 dark:border-slate-800 dark:text-white rounded-xl px-3.5 py-2.5 text-xs font-bold focus:outline-none focus:border-indigo-500 transition">
                        @error('importFile') <span class="text-xs text-red-500 dark:text-red-400 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex items-center justify-end space-x-3 pt-2">
                        <button type="button" wire:click="$set('isImportModalOpen', false)" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 rounded-xl text-xs font-bold transition">
                            Cancel
                        </button>
                        <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-black transition shadow-lg shadow-indigo-600/20">
                            Import & Open Studio
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Create Custom Design Orientation Selection Modal -->
    @if($isCreateModalOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/40 dark:bg-black/80 backdrop-blur-md flex items-center justify-center p-4">
            <div class="bg-white border border-slate-200 text-slate-900 shadow-2xl dark:bg-slate-900 dark:border-slate-800 dark:text-white rounded-3xl max-w-xl w-full p-6 space-y-6">
                <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-4">
                    <div>
                        <h3 class="text-base font-extrabold text-slate-900 dark:text-white">Select Template Orientation</h3>
                        <p class="text-xs text-slate-600 dark:text-slate-400 mt-0.5">Choose layout dimensions for your new custom ID card design</p>
                    </div>
                    <button type="button" wire:click="$set('isCreateModalOpen', false)" class="text-slate-400 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white text-xl font-bold">&times;</button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Landscape Option -->
                    <button type="button" wire:click="createBlankSchoolTemplate('landscape')" class="group text-left bg-slate-50 hover:bg-indigo-50/50 border border-slate-200 hover:border-indigo-500/50 dark:bg-slate-950 dark:hover:bg-slate-900 dark:border-slate-800 dark:hover:border-indigo-500/50 rounded-2xl p-5 transition relative overflow-hidden flex flex-col items-center justify-center text-center space-y-3">
                        <div class="w-32 h-20 bg-indigo-50 border-2 border-indigo-300 dark:bg-indigo-950/40 dark:border-indigo-500/40 group-hover:border-indigo-500 rounded-xl flex items-center justify-center transition shadow-inner">
                            <span class="text-[10px] font-mono font-bold text-indigo-700 dark:text-indigo-300">85.6mm × 54mm</span>
                        </div>
                        <div>
                            <span class="text-sm font-extrabold text-slate-900 group-hover:text-indigo-600 dark:text-white dark:group-hover:text-indigo-400 transition block">Landscape (Horizontal)</span>
                            <span class="text-[11px] text-slate-600 dark:text-slate-400 mt-1 block">Standard horizontal layout for student & staff cards</span>
                        </div>
                    </button>

                    <!-- Portrait Option -->
                    <button type="button" wire:click="createBlankSchoolTemplate('portrait')" class="group text-left bg-slate-50 hover:bg-indigo-50/50 border border-slate-200 hover:border-indigo-500/50 dark:bg-slate-950 dark:hover:bg-slate-900 dark:border-slate-800 dark:hover:border-indigo-500/50 rounded-2xl p-5 transition relative overflow-hidden flex flex-col items-center justify-center text-center space-y-3">
                        <div class="w-20 h-32 bg-indigo-50 border-2 border-indigo-300 dark:bg-indigo-950/40 dark:border-indigo-500/40 group-hover:border-indigo-500 rounded-xl flex items-center justify-center transition shadow-inner">
                            <span class="text-[10px] font-mono font-bold text-indigo-700 dark:text-indigo-300">54mm × 85.6mm</span>
                        </div>
                        <div>
                            <span class="text-sm font-extrabold text-slate-900 group-hover:text-indigo-600 dark:text-white dark:group-hover:text-indigo-400 transition block">Portrait (Vertical)</span>
                            <span class="text-[11px] text-slate-600 dark:text-slate-400 mt-1 block">Vertical orientation for lanyard clip student cards</span>
                        </div>
                    </button>
                </div>

                <div class="flex items-center justify-end border-t border-slate-200 dark:border-slate-800 pt-4">
                    <button type="button" wire:click="$set('isCreateModalOpen', false)" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 rounded-xl text-xs font-bold transition">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
