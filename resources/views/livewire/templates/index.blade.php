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
            }
            session()->flash('message', 'Default school ID card template updated successfully!');
        }
    }

    public function openAssignModal($templateId, $isSchoolTpl = false)
    {
        $this->isSchoolTemplate = $isSchoolTpl;
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
        $template = $isSchoolTpl ? SchoolTemplate::find($templateId) : Template::find($templateId);
        if (!$template) return;

        $exportData = [
            'name' => $template->name,
            'orientation' => $template->orientation,
            'width_mm' => $template->width_mm,
            'height_mm' => $template->height_mm,
            'layout_config' => $template->layout_config,
        ];

        $jsonContent = json_encode($exportData, JSON_PRETTY_PRINT);
        $filename = \Illuminate\Support\Str::slug($template->name) . '-template.json';

        return response()->streamDownload(function() use ($jsonContent) {
            echo $jsonContent;
        }, $filename, ['Content-Type' => 'application/json']);
    }

    public function importJsonTemplate()
    {
        $this->validate([
            'importFile' => 'required|file|mimes:json,txt|max:2048',
            'importName' => 'required|string|max:100',
        ]);

        $activeSchoolId = session('active_school_id');
        if (!$activeSchoolId) {
            $this->addError('importFile', 'Please select an active school first.');
            return;
        }

        $jsonStr = file_get_contents($this->importFile->getRealPath());
        $data = json_decode($jsonStr, true);

        if (!$data || !isset($data['layout_config'])) {
            $this->addError('importFile', 'Invalid JSON template format. Must contain layout_config.');
            return;
        }

        $schoolTemplate = SchoolTemplate::create([
            'school_id' => $activeSchoolId,
            'name' => $this->importName,
            'orientation' => $data['orientation'] ?? 'landscape',
            'width_mm' => $data['width_mm'] ?? 85.60,
            'height_mm' => $data['height_mm'] ?? 54.00,
            'background_image' => null,
            'layout_config' => $data['layout_config'],
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

        $masterTemplates = Template::where('is_active', true)->get();
        $schoolTemplates = $activeSchoolId ? SchoolTemplate::where('school_id', $activeSchoolId)->get() : collect();

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
        <div class="bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 p-4 rounded-2xl flex items-center justify-between text-sm font-semibold">
            <span>{{ session('message') }}</span>
            <button type="button" onclick="this.parentElement.remove()" class="text-emerald-400 hover:text-white">&times;</button>
        </div>
    @endif

    <!-- Header & Action Bar -->
    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 flex flex-wrap items-center justify-between gap-4 shadow-xl">
        <div>
            <div class="flex items-center space-x-3">
                <h1 class="text-xl font-black text-white">ID Card Template Studio</h1>
                <span class="text-xs font-bold text-indigo-400 bg-indigo-500/10 border border-indigo-500/20 px-3 py-1 rounded-full">
                    Canva-Style Visual Editor
                </span>
            </div>
            <p class="text-xs text-slate-400 mt-1">Select a master template preset or customize your school's unique ID card designs</p>
        </div>

        <div class="flex items-center space-x-3">
            <button type="button" wire:click="$set('isImportModalOpen', true)" class="px-4 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-xl text-xs font-bold transition flex items-center shadow">
                <svg class="w-4 h-4 mr-2 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                </svg>
                Import JSON Template
            </button>

            <button type="button" wire:click="$set('isCreateModalOpen', true)" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition flex items-center shadow-lg shadow-indigo-600/20">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Create Custom Design
            </button>
        </div>
    </div>

    <!-- School Specific Custom Templates Section -->
    @if($schoolTemplates->isNotEmpty())
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-base font-black text-white flex items-center space-x-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span>
                    <span>Your School Custom Templates</span>
                </h2>
                <span class="text-xs text-slate-400 font-semibold">{{ $schoolTemplates->count() }} Custom Template(s)</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($schoolTemplates as $st)
                    <div class="bg-slate-900 border border-slate-800 hover:border-indigo-500/50 rounded-3xl p-5 shadow-xl transition-all duration-300 flex flex-col justify-between group relative">
                        <div>
                            <!-- Header Info -->
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <h3 class="text-sm font-extrabold text-white group-hover:text-indigo-400 transition">{{ $st->name }}</h3>
                                    <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">{{ $st->orientation }} • {{ $st->width_mm }}x{{ $st->height_mm }}mm</span>
                                </div>
                                @if($activeSchool && $activeSchool->template_id == $st->id || $st->is_default)
                                    <span class="text-[10px] font-black text-emerald-400 bg-emerald-500/10 border border-emerald-500/20 px-2.5 py-1 rounded-full uppercase tracking-wider">Default</span>
                                @endif
                            </div>

                            <!-- Render Card Thumbnail -->
                            <div class="w-full bg-slate-950/50 rounded-2xl p-3 mb-4 flex items-center justify-center border border-slate-800/80 min-h-[160px]">
                                <div class="transform scale-[0.3] origin-center">
                                    <x-id-card-renderer :template="$st" :student="$mockStudent" :school="$activeSchool" />
                                </div>
                            </div>
                        </div>

                        <!-- Card Action Buttons -->
                        <div class="space-y-2 pt-2 border-t border-slate-800/60">
                            <div class="grid grid-cols-2 gap-2">
                                <a href="{{ route('templates.edit', ['template' => $st->id, 'type' => 'school']) }}" class="text-center py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition flex items-center justify-center">
                                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                    Canva Studio
                                </a>

                                <button type="button" wire:click="openAssignModal({{ $st->id }}, true)" class="py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-xl text-xs font-bold transition flex items-center justify-center">
                                    <svg class="w-3.5 h-3.5 mr-1 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                                    </svg>
                                    Assign
                                </button>
                            </div>

                            <button type="button" wire:click="exportJson({{ $st->id }}, true)" class="w-full text-center py-1.5 bg-slate-950 hover:bg-slate-800 text-slate-400 hover:text-white rounded-lg text-[11px] font-semibold transition flex items-center justify-center">
                                <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                </svg>
                                Export JSON Layout
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- System Master Presets Section -->
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-base font-black text-white flex items-center space-x-2">
                <span class="w-2.5 h-2.5 rounded-full bg-indigo-500"></span>
                <span>System Master ID Templates</span>
            </h2>
            <span class="text-xs text-slate-400 font-semibold">Standard Presets</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($masterTemplates as $tpl)
                <div class="bg-slate-900 border border-slate-800 hover:border-indigo-500/50 rounded-3xl p-5 shadow-xl transition-all duration-300 flex flex-col justify-between group">
                    <div>
                        <!-- Header Info -->
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h3 class="text-sm font-extrabold text-white group-hover:text-indigo-400 transition">{{ $tpl->name }}</h3>
                                <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">{{ $tpl->orientation }} • {{ $tpl->width_mm }}x{{ $tpl->height_mm }}mm</span>
                            </div>
                            <span class="text-[10px] font-black text-indigo-400 bg-indigo-500/10 border border-indigo-500/20 px-2.5 py-1 rounded-full uppercase tracking-wider">System Master</span>
                        </div>

                        <!-- Render Card Thumbnail -->
                        <div class="w-full bg-slate-950/50 rounded-2xl p-3 mb-4 flex items-center justify-center border border-slate-800/80 min-h-[160px]">
                            <div class="transform scale-[0.3] origin-center">
                                <x-id-card-renderer :template="$tpl" :student="$mockStudent" :school="$activeSchool" />
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="space-y-2 pt-2 border-t border-slate-800/60">
                        <div class="grid grid-cols-2 gap-2">
                            <button type="button" wire:click="customizeMasterTemplate('{{ $tpl->id }}')" class="py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition flex items-center justify-center shadow">
                                <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                                Customize
                            </button>

                            <button type="button" wire:click="openAssignModal('{{ $tpl->id }}', false)" class="py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-xl text-xs font-bold transition flex items-center justify-center">
                                <svg class="w-3.5 h-3.5 mr-1 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                                </svg>
                                Assign
                            </button>
                        </div>

                        <button type="button" wire:click="exportJson('{{ $tpl->id }}', false)" class="w-full text-center py-1.5 bg-slate-950 hover:bg-slate-800 text-slate-400 hover:text-white rounded-lg text-[11px] font-semibold transition flex items-center justify-center">
                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            Export JSON Layout
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Modal: Assign Template -->
    @if($isAssignModalOpen && $selectedTemplateForAssign)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-black/80 backdrop-blur-md flex items-center justify-center p-4">
            <div class="bg-slate-900 border border-slate-800 rounded-3xl max-w-lg w-full p-6 shadow-2xl space-y-6">
                <div class="flex items-center justify-between border-b border-slate-800 pb-4">
                    <div>
                        <h3 class="text-base font-extrabold text-white">Assign Template</h3>
                        <p class="text-xs text-slate-400 mt-0.5">{{ $selectedTemplateForAssign->name }}</p>
                    </div>
                    <button type="button" wire:click="closeAssignModal" class="text-slate-400 hover:text-white text-lg font-bold">&times;</button>
                </div>

                <!-- Option 1: School Default -->
                <div class="bg-slate-950 border border-slate-800 rounded-2xl p-4 flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold text-white block">Make Default School Template</span>
                        <span class="text-[11px] text-slate-400">Applies to all grades unless overridden</span>
                    </div>
                    <button type="button" wire:click="assignToSchool('{{ $selectedTemplateForAssign->id }}', {{ $isSchoolTemplate ? 'true' : 'false' }})" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition">
                        Set School Default
                    </button>
                </div>

                <!-- Option 2: Grade Specific -->
                <div class="space-y-3">
                    <span class="text-xs font-bold text-slate-300 block">Or Assign to Specific Grade:</span>
                    <div class="max-h-60 overflow-y-auto space-y-2 pr-1">
                        @foreach($schoolGrades as $g)
                            @php
                                $isAssigned = $isSchoolTemplate 
                                    ? ($g->school_template_id == $selectedTemplateForAssign->id)
                                    : ($g->template_id == $selectedTemplateForAssign->id);
                            @endphp
                            <div class="bg-slate-950/70 border border-slate-800/80 rounded-xl p-3 flex items-center justify-between">
                                <div>
                                    <span class="text-xs font-bold text-white">Grade {{ $g->name }}</span>
                                    @if($isAssigned)
                                        <span class="text-[10px] text-emerald-400 font-bold ml-2">● Currently Assigned</span>
                                    @endif
                                </div>
                                <button type="button" wire:click="assignToGrade({{ $g->id }}, '{{ $selectedTemplateForAssign->id }}', {{ $isSchoolTemplate ? 'true' : 'false' }})" class="px-3 py-1.5 {{ $isAssigned ? 'bg-slate-800 text-slate-400' : 'bg-slate-800 hover:bg-slate-700 text-slate-200' }} rounded-lg text-xs font-semibold transition">
                                    {{ $isAssigned ? 'Re-assign' : 'Assign to Grade' }}
                                </button>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="flex justify-end pt-2">
                    <button type="button" wire:click="closeAssignModal" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-bold transition">
                        Close
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal: Import JSON Template -->
    @if($isImportModalOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-black/80 backdrop-blur-md flex items-center justify-center p-4">
            <div class="bg-slate-900 border border-slate-800 rounded-3xl max-w-md w-full p-6 shadow-2xl space-y-5">
                <div class="flex items-center justify-between border-b border-slate-800 pb-4">
                    <h3 class="text-base font-extrabold text-white">Import JSON Template</h3>
                    <button type="button" wire:click="$set('isImportModalOpen', false)" class="text-slate-400 hover:text-white text-lg font-bold">&times;</button>
                </div>

                <form wire:submit.prevent="importJsonTemplate" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-400 mb-1">Template Name</label>
                        <input type="text" wire:model="importName" placeholder="e.g. Custom Science ID Card" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-xs font-bold text-white focus:outline-none focus:border-indigo-500 transition">
                        @error('importName') <span class="text-xs text-red-400 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-400 mb-1">JSON Template File (.json)</label>
                        <input type="file" wire:model="importFile" accept=".json" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-xs font-bold text-white focus:outline-none focus:border-indigo-500 transition">
                        @error('importFile') <span class="text-xs text-red-400 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex items-center justify-end space-x-3 pt-2">
                        <button type="button" wire:click="$set('isImportModalOpen', false)" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-bold transition">
                            Cancel
                        </button>
                        <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition shadow-lg shadow-indigo-600/20">
                            Import & Open Studio
                        </button>
                    </div>
                </form>
    @endif

    <!-- Create Custom Design Orientation Selection Modal -->
    @if($isCreateModalOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-black/80 backdrop-blur-md flex items-center justify-center p-4">
            <div class="bg-slate-900 border border-slate-800 rounded-3xl max-w-xl w-full p-6 shadow-2xl space-y-6">
                <div class="flex items-center justify-between border-b border-slate-800 pb-4">
                    <div>
                        <h3 class="text-base font-extrabold text-white">Select Template Orientation</h3>
                        <p class="text-xs text-slate-400 mt-0.5">Choose layout dimensions for your new custom ID card design</p>
                    </div>
                    <button type="button" wire:click="$set('isCreateModalOpen', false)" class="text-slate-400 hover:text-white text-xl font-bold">&times;</button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Landscape Option -->
                    <button type="button" wire:click="createBlankSchoolTemplate('landscape')" class="group text-left bg-slate-950 hover:bg-slate-900 border border-slate-800 hover:border-indigo-500/50 rounded-2xl p-5 transition relative overflow-hidden flex flex-col items-center justify-center text-center space-y-3">
                        <div class="w-32 h-20 bg-indigo-950/40 border-2 border-indigo-500/40 group-hover:border-indigo-400 rounded-xl flex items-center justify-center transition shadow-inner">
                            <span class="text-[10px] font-mono font-bold text-indigo-300">85.6mm × 54mm</span>
                        </div>
                        <div>
                            <span class="text-sm font-extrabold text-white group-hover:text-indigo-400 transition block">Landscape (Horizontal)</span>
                            <span class="text-[11px] text-slate-400 mt-1 block">Standard horizontal layout for student & staff cards</span>
                        </div>
                    </button>

                    <!-- Portrait Option -->
                    <button type="button" wire:click="createBlankSchoolTemplate('portrait')" class="group text-left bg-slate-950 hover:bg-slate-900 border border-slate-800 hover:border-indigo-500/50 rounded-2xl p-5 transition relative overflow-hidden flex flex-col items-center justify-center text-center space-y-3">
                        <div class="w-20 h-32 bg-indigo-950/40 border-2 border-indigo-500/40 group-hover:border-indigo-400 rounded-xl flex items-center justify-center transition shadow-inner">
                            <span class="text-[10px] font-mono font-bold text-indigo-300">54mm × 85.6mm</span>
                        </div>
                        <div>
                            <span class="text-sm font-extrabold text-white group-hover:text-indigo-400 transition block">Portrait (Vertical)</span>
                            <span class="text-[11px] text-slate-400 mt-1 block">Vertical orientation for lanyard clip student cards</span>
                        </div>
                    </button>
                </div>

                <div class="flex items-center justify-end border-t border-slate-800 pt-4">
                    <button type="button" wire:click="$set('isCreateModalOpen', false)" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-bold transition">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
