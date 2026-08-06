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
    public bool $importAsMaster = false;

    // Create Modal properties
    public bool $isCreateModalOpen = false;
    public string $createTarget = 'school'; // 'school' or 'master'

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

    public function createBlankMasterTemplate($orientation = 'landscape')
    {
        if (!auth()->user()->hasRole('saas_admin')) {
            $this->addError('general', 'Only SaaS Admin can create System Master Templates.');
            return;
        }

        $isPortrait = $orientation === 'portrait';
        $width = $isPortrait ? 54.00 : 85.60;
        $height = $isPortrait ? 85.60 : 54.00;

        $defaultMaster = Template::where('orientation', $orientation)->first() ?? Template::first();
        $defaultConfig = $defaultMaster ? $defaultMaster->layout_config : [
            [
                'id' => 'student_name',
                'type' => 'text',
                'label' => 'Student Name',
                'text' => '{First Name} {Middle Name} {Last Name}',
                'x' => 130,
                'y' => 82,
                'font_size' => 16,
                'font_weight' => 'bold',
                'font_family' => 'Inter',
                'color' => '#ffffff',
                'align' => 'left',
                'rotation' => 0,
            ]
        ];

        $name = 'System Master ' . ucfirst($orientation) . ' Preset ' . rand(100, 999);
        $id = \Illuminate\Support\Str::slug($name) . '-' . \Illuminate\Support\Str::random(6);

        $master = Template::create([
            'id' => $id,
            'name' => $name,
            'orientation' => $orientation,
            'width_mm' => $width,
            'height_mm' => $height,
            'background_image' => null,
            'layout_config' => $defaultConfig,
            'is_active' => true,
        ]);

        return redirect()->route('templates.edit', ['template' => $master->id, 'type' => 'master']);
    }

    public function deleteMasterTemplate($templateId)
    {
        if (!auth()->user()->hasRole('saas_admin')) {
            $this->addError('general', 'Only SaaS Admin can delete System Master Templates.');
            return;
        }

        $tpl = Template::find($templateId);
        if ($tpl) {
            $tpl->delete();
            session()->flash('message', 'System Master Template deleted successfully!');
        }
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

        if ($this->importAsMaster && auth()->user()->hasRole('saas_admin')) {
            $id = \Illuminate\Support\Str::slug($this->importName) . '-' . \Illuminate\Support\Str::random(6);
            $master = Template::create([
                'id' => $id,
                'name' => $this->importName,
                'orientation' => $orientation,
                'width_mm' => $width,
                'height_mm' => $height,
                'layout_config' => $layoutConfig,
                'is_active' => true,
            ]);

            $this->isImportModalOpen = false;
            $this->importFile = null;
            $this->importName = '';
            $this->importAsMaster = false;

            session()->flash('message', 'System Master Template imported successfully!');
            return redirect()->route('templates.edit', ['template' => $master->id, 'type' => 'master']);
        }

        $activeSchoolId = session('active_school_id');
        if (!$activeSchoolId) {
            $this->addError('general', 'No active school selected.');
            return;
        }

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
        $this->importAsMaster = false;

        session()->flash('message', 'Template imported successfully!');
        return redirect()->route('templates.edit', ['template' => $schoolTemplate->id, 'type' => 'school']);
    }

    public int $perPage = 12;

    public function loadMore()
    {
        $this->perPage += 12;
    }

    public function updatedSearch()
    {
        $this->perPage = 12;
    }

    public function updatedSelectedCategory()
    {
        $this->perPage = 12;
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

        $totalMaster = $masterQuery->count();
        $totalSchool = $schoolQuery->count();
        $totalCount = $totalMaster + $totalSchool;
        $hasMore = $totalCount > $this->perPage;

        $masterTemplates = $masterQuery->take($this->perPage)->get();
        $remainingLimit = max(0, $this->perPage - $masterTemplates->count());
        $schoolTemplates = $remainingLimit > 0 ? $schoolQuery->take($remainingLimit)->get() : collect();

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
            'hasMore' => $hasMore,
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

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 items-stretch">
                @foreach($schoolTemplates as $st)
                    @php
                        $isPortrait = $st->orientation === 'portrait';
                        $isDefault = ($activeSchool && $activeSchool->template_id == $st->id) || $st->is_default;
                        $scale = $isPortrait ? 0.25 : 0.31;
                    @endphp
                    <div class="bg-white border border-slate-200 hover:border-indigo-500/50 text-slate-900 shadow-md hover:shadow-xl dark:bg-slate-900 dark:border-slate-800 dark:hover:border-indigo-500/50 dark:text-white rounded-3xl p-5 transition-all duration-300 flex flex-col justify-between group relative overflow-hidden h-full">
                        <div class="flex flex-col flex-1 min-h-0">
                            <!-- Header Info -->
                            <div class="flex items-start justify-between mb-4 shrink-0">
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

                            <!-- Canvas Stage & Card Thumbnail - Expands 100% full height & floats ID card in dead center -->
                            <div class="relative w-full flex-1 min-h-[260px] bg-slate-100 border border-slate-200 dark:bg-slate-950 dark:border-slate-800/80 rounded-2xl p-4 mb-4 flex items-center justify-center overflow-hidden group-hover:border-indigo-500/40 transition-all duration-300 bg-[radial-gradient(#cbd5e1_1px,transparent_1px)] dark:bg-[radial-gradient(#334155_1px,transparent_1px)] [background-size:16px_16px]">
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
                        <div class="space-y-2 pt-3 border-t border-slate-100 dark:border-slate-800/80 shrink-0">
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

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 items-stretch">
            @foreach($masterTemplates as $tpl)
                @php
                    $isPortrait = $tpl->orientation === 'portrait';
                    $scale = $isPortrait ? 0.25 : 0.31;
                @endphp
                <div class="bg-white border border-slate-200 hover:border-indigo-500/50 text-slate-900 shadow-md hover:shadow-xl dark:bg-slate-900 dark:border-slate-800 dark:hover:border-indigo-500/50 dark:text-white rounded-3xl p-5 transition-all duration-300 flex flex-col justify-between group relative overflow-hidden h-full">
                    <div class="flex flex-col flex-1 min-h-0">
                        <!-- Header Info -->
                        <div class="flex items-start justify-between mb-4 shrink-0">
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

                        <!-- Canvas Stage & Card Thumbnail - Expands 100% full height & floats ID card in dead center -->
                        <div class="relative w-full flex-1 min-h-[260px] bg-slate-100 border border-slate-200 dark:bg-slate-950 dark:border-slate-800/80 rounded-2xl p-4 mb-4 flex items-center justify-center overflow-hidden group-hover:border-indigo-500/40 transition-all duration-300 bg-[radial-gradient(#cbd5e1_1px,transparent_1px)] dark:bg-[radial-gradient(#334155_1px,transparent_1px)] [background-size:16px_16px]">
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

                    <!-- Actions (Stays pinned to bottom) -->
                    <div class="space-y-2 pt-3 border-t border-slate-100 dark:border-slate-800/80 shrink-0">
                        @if(auth()->user()->hasRole('saas_admin'))
                            <div class="grid grid-cols-2 gap-2 pb-1">
                                <a href="{{ route('templates.edit', ['template' => $tpl->id, 'type' => 'master']) }}" class="py-2.5 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white rounded-xl text-xs font-black transition flex items-center justify-center shadow-md shadow-purple-600/20">
                                    <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                    Edit Master
                                </a>

                                <button type="button" wire:click="deleteMasterTemplate('{{ $tpl->id }}')" wire:confirm="Are you sure you want to delete this System Master Template?" class="py-2.5 bg-rose-500/10 hover:bg-rose-600 text-rose-600 hover:text-white border border-rose-200 dark:border-rose-800/60 rounded-xl text-xs font-bold transition flex items-center justify-center">
                                    <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                    Delete
                                </button>
                            </div>
                        @endif

                        <div class="grid grid-cols-2 gap-2">
                            <button type="button" wire:click="customizeMasterTemplate('{{ $tpl->id }}')" class="py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-black transition flex items-center justify-center shadow-md shadow-indigo-600/20">
                                <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                </svg>
                                Copy to School
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

    <!-- Infinite Scroll Loading Sentinel -->
    @if ($hasMore)
        <div 
            x-data="{
                observe() {
                    const observer = new IntersectionObserver((entries) => {
                        entries.forEach(entry => {
                            if (entry.isIntersecting) {
                                $wire.loadMore();
                            }
                        });
                    }, { rootMargin: '200px' });
                    observer.observe(this.$el);
                }
            }"
            x-init="observe()"
            class="flex flex-col items-center justify-center pt-8 pb-4"
        >
            <div wire:loading wire:target="loadMore" class="flex items-center gap-2 text-xs font-bold text-indigo-500 uppercase tracking-wider">
                <svg class="animate-spin h-5 w-5 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>Loading more templates...</span>
            </div>

            <button 
                wire:loading.remove 
                wire:target="loadMore" 
                wire:click="loadMore" 
                class="px-6 py-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 font-extrabold text-xs uppercase tracking-wider rounded-2xl transition shadow-sm flex items-center gap-2 cursor-pointer"
            >
                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 13l-7 7-7-7m14-6l-7 7-7-7"/>
                </svg>
                {{ __('Load More Templates') }}
            </button>
        </div>
    @endif

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

                    @if(auth()->user()->hasRole('saas_admin'))
                        <div class="flex items-center space-x-2.5 bg-purple-50 dark:bg-purple-950/40 border border-purple-200 dark:border-purple-800/60 p-3 rounded-xl">
                            <input type="checkbox" id="importAsMaster" wire:model="importAsMaster" class="w-4 h-4 rounded text-purple-600 focus:ring-purple-500 border-slate-300 dark:bg-slate-900 dark:border-slate-700">
                            <label for="importAsMaster" class="text-xs font-bold text-purple-900 dark:text-purple-300 cursor-pointer select-none">
                                Import as System Master Template (Global)
                            </label>
                        </div>
                    @endif

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
                        <h3 class="text-base font-extrabold text-slate-900 dark:text-white">Create New ID Template</h3>
                        <p class="text-xs text-slate-600 dark:text-slate-400 mt-0.5">Select target type and orientation layout for your new design</p>
                    </div>
                    <button type="button" wire:click="$set('isCreateModalOpen', false)" class="text-slate-400 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white text-xl font-bold">&times;</button>
                </div>

                @if(auth()->user()->hasRole('saas_admin'))
                    <div class="space-y-1.5">
                        <label class="block text-xs font-extrabold text-slate-700 dark:text-slate-300">Destination Template Type</label>
                        <div class="grid grid-cols-2 gap-2 bg-slate-100 dark:bg-slate-950 p-1.5 rounded-2xl border border-slate-200 dark:border-slate-800">
                            <button type="button" wire:click="$set('createTarget', 'school')" class="py-2 text-xs font-bold rounded-xl transition flex items-center justify-center space-x-1.5 {{ $createTarget === 'school' ? 'bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm' : 'text-slate-500 hover:text-slate-900 dark:hover:text-white' }}">
                                <span>School Template</span>
                            </button>
                            <button type="button" wire:click="$set('createTarget', 'master')" class="py-2 text-xs font-bold rounded-xl transition flex items-center justify-center space-x-1.5 {{ $createTarget === 'master' ? 'bg-purple-600 text-white shadow-md font-black' : 'text-slate-500 hover:text-slate-900 dark:hover:text-white' }}">
                                <span>⚡ System Master Preset (Global)</span>
                            </button>
                        </div>
                    </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Landscape Option -->
                    <button type="button" wire:click="{{ (auth()->user()->hasRole('saas_admin') && $createTarget === 'master') ? "createBlankMasterTemplate('landscape')" : "createBlankSchoolTemplate('landscape')" }}" class="group text-left bg-slate-50 hover:bg-indigo-50/50 border border-slate-200 hover:border-indigo-500/50 dark:bg-slate-950 dark:hover:bg-slate-900 dark:border-slate-800 dark:hover:border-indigo-500/50 rounded-2xl p-5 transition relative overflow-hidden flex flex-col items-center justify-center text-center space-y-3">
                        <div class="w-32 h-20 bg-indigo-50 border-2 border-indigo-300 dark:bg-indigo-950/40 dark:border-indigo-500/40 group-hover:border-indigo-500 rounded-xl flex items-center justify-center transition shadow-inner">
                            <span class="text-[10px] font-mono font-bold text-indigo-700 dark:text-indigo-300">85.6mm × 54mm</span>
                        </div>
                        <div>
                            <span class="text-sm font-extrabold text-slate-900 group-hover:text-indigo-600 dark:text-white dark:group-hover:text-indigo-400 transition block">Landscape (Horizontal)</span>
                            <span class="text-[11px] text-slate-600 dark:text-slate-400 mt-1 block">Standard horizontal layout for student & staff cards</span>
                        </div>
                    </button>

                    <!-- Portrait Option -->
                    <button type="button" wire:click="{{ (auth()->user()->hasRole('saas_admin') && $createTarget === 'master') ? "createBlankMasterTemplate('portrait')" : "createBlankSchoolTemplate('portrait')" }}" class="group text-left bg-slate-50 hover:bg-indigo-50/50 border border-slate-200 hover:border-indigo-500/50 dark:bg-slate-950 dark:hover:bg-slate-900 dark:border-slate-800 dark:hover:border-indigo-500/50 rounded-2xl p-5 transition relative overflow-hidden flex flex-col items-center justify-center text-center space-y-3">
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
