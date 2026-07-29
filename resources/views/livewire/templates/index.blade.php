<?php

use Livewire\Volt\Component;
use App\Models\School;
use App\Models\Grade;

new class extends Component {
    public string $search = '';
    public string $selectedCategory = 'all';

    // Modal properties
    public bool $isAssignModalOpen = false;
    public $selectedTemplateForAssign = null;
    public $schoolGrades = [];

    // Preview properties
    public bool $isPreviewModalOpen = false;
    public $selectedTemplateForPreview = null;

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

    public function with(): array
    {
        $activeSchoolId = session('active_school_id');
        $activeSchool = $activeSchoolId ? School::find($activeSchoolId) : null;

        // Create a mock student object for previewing
        $mockStudent = (object)[
            'first_name' => 'John',
            'middle_name' => 'A.',
            'last_name' => 'Doe',
            'dob' => '2015-05-15',
            'contact_number' => '9876543210',
            'blood_group' => 'A+',
            'address' => '123 Main Street',
            'pincode' => '400001',
            'photo_path' => '', // placeholder
            'campaignStudents' => collect([
                (object)[
                    'grade' => (object)['name' => 'Grade 5'],
                    'division' => (object)['name' => 'Div A'],
                    'roll_no' => '42',
                    'serial_number' => 'SR-2026-042',
                ]
            ])
        ];

        $mockSchool = $activeSchool ?? (object)[
            'name' => 'Sarvodaya Vidyalay',
            'logo_path' => '',
            'school_code' => 'SV-99',
        ];

        // Define static templates list
        $templates = collect([
            (object)[
                'id' => 'premium-landscape',
                'name' => 'Premium Landscape Student ID',
                'view_path' => 'id-card-templates.premium-landscape',
                'orientation' => 'Landscape (54 x 85.6 mm)',
                'category' => 'student',
                'thumbnail_color' => 'from-blue-900 to-indigo-950',
            ]
        ]);

        return [
            'templates' => $templates,
            'activeSchool' => $activeSchool,
            'mockStudent' => $mockStudent,
            'mockSchool' => $mockSchool,
        ];
    }

    public function assignToSchool($templateId)
    {
        $activeSchoolId = session('active_school_id');
        if (!$activeSchoolId) {
            $this->addError('general', 'No active school selected.');
            return;
        }

        $school = School::find($activeSchoolId);
        if ($school) {
            $school->update(['template_id' => $templateId]);
            session()->flash('message', 'Default school template updated successfully!');
        }
    }

    public function openAssignModal($templateId)
    {
        $this->selectedTemplateForAssign = (object)[
            'id' => 'premium-landscape',
            'name' => 'Premium Landscape Student ID',
        ];
        $this->loadGrades();
        $this->isAssignModalOpen = true;
    }

    public function closeAssignModal()
    {
        $this->isAssignModalOpen = false;
        $this->selectedTemplateForAssign = null;
    }

    public function openPreviewModal($templateId)
    {
        $this->selectedTemplateForPreview = (object)[
            'id' => 'premium-landscape',
            'name' => 'Premium Landscape Student ID',
            'view_path' => 'id-card-templates.premium-landscape',
        ];
        $this->isPreviewModalOpen = true;
    }

    public function closePreviewModal()
    {
        $this->isPreviewModalOpen = false;
        $this->selectedTemplateForPreview = null;
    }

    public function assignToGrade($gradeId, $templateId)
    {
        $grade = Grade::find($gradeId);
        if ($grade) {
            // Toggle assignment
            $newTemplateId = $grade->template_id == $templateId ? null : $templateId;
            $grade->update(['template_id' => $newTemplateId]);
            $this->loadGrades();
        }
    }
}; ?>

<div class="space-y-6">
    <!-- Top Header Banner & Stats -->
    <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-xl relative overflow-hidden">
        <div class="absolute top-0 right-0 -mt-10 -mr-10 w-64 h-64 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-xs font-semibold text-indigo-400 mb-3">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
                    </svg>
                    <span>ID Card Design Library</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-black text-white tracking-tight">Active ID Card Template</h1>
                <p class="text-slate-400 text-sm mt-1 max-w-2xl">
                    Assign the premium landscape student ID card template as the institution-wide default or configure class overrides.
                </p>
            </div>
        </div>
    </div>

    <!-- Feedback Flash Alerts -->
    @if (session()->has('message'))
        <div class="bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs font-bold rounded-2xl p-4 flex items-center space-x-2">
            <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            <span>{{ session('message') }}</span>
        </div>
    @endif



    <!-- Templates Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        @foreach($templates as $tpl)
            @if(($selectedCategory === 'all' || $tpl->category === $selectedCategory) && (empty($search) || stripos($tpl->name, $search) !== false))
                @php
                    $isSchoolDefault = $activeSchool && $activeSchool->template_id == $tpl->id;
                    $classOverridesCount = $activeSchool ? App\Models\Grade::where('template_id', $tpl->id)->where('school_id', $activeSchool->id)->count() : 0;
                @endphp
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-5 shadow-sm hover:shadow-md transition duration-200 flex flex-col justify-between group">
                    <div>
                        <!-- Card Visual Live Blade Preview Container -->
                        <div wire:click="openPreviewModal('{{ $tpl->id }}')" class="cursor-pointer h-56 w-full rounded-2xl bg-slate-950/30 overflow-hidden flex items-center justify-center p-2 relative shadow-inner mb-4 hover:bg-slate-950/40 transition duration-200">
                            <div class="scale-[0.4] sm:scale-[0.45] origin-center shrink-0 pointer-events-none">
                                @include($tpl->view_path, ['student' => $mockStudent, 'school' => $mockSchool])
                            </div>
                        </div>

                        <!-- Info -->
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/50 px-2 py-0.5 rounded-md">
                                    {{ $tpl->category }}
                                </span>
                                @if($isSchoolDefault)
                                    <span class="text-[10px] font-semibold text-emerald-600 dark:text-emerald-400 flex items-center bg-emerald-500/10 px-2 py-0.5 rounded-md">
                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                        School Default
                                    </span>
                                @endif
                            </div>
                            
                            <h3 class="text-sm font-bold text-slate-800 dark:text-slate-200 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition">
                                {{ $tpl->name }}
                            </h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                {{ $tpl->orientation }}
                            </p>

                            @if($classOverridesCount > 0)
                                <div class="mt-1">
                                    <span class="text-[9px] font-black tracking-wider uppercase text-purple-600 dark:text-purple-400 bg-purple-500/10 border border-purple-500/20 px-2 py-0.5 rounded">
                                        Overriding {{ $classOverridesCount }} Class{{ $classOverridesCount > 1 ? 'es' : '' }}
                                    </span>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Action buttons -->
                    <div class="pt-4 mt-4 border-t border-slate-100 dark:border-slate-800/80 flex flex-col gap-2">
                        @if(!$isSchoolDefault)
                            <button type="button" wire:click="assignToSchool({{ $tpl->id }})" class="w-full text-center py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition">
                                Make School Default
                            </button>
                        @else
                            <button type="button" disabled class="w-full text-center py-2 bg-slate-100 dark:bg-slate-800 text-slate-400 rounded-xl text-xs font-bold cursor-not-allowed">
                                Active Default
                            </button>
                        @endif

                        <button type="button" wire:click="openAssignModal({{ $tpl->id }})" class="w-full text-center py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-xl text-xs font-bold transition">
                            Assign to Class
                        </button>
                    </div>
                </div>
            @endif
        @endforeach
    </div>

    <!-- Assign to Class Modal -->
    @if($isAssignModalOpen && $selectedTemplateForAssign)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <!-- Backdrop -->
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm transition-opacity" aria-hidden="true" wire:click="closeAssignModal"></div>

                <!-- Center elements -->
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="inline-block align-bottom bg-slate-900 border border-slate-800 rounded-3xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="px-6 pt-6 pb-4">
                        <div class="flex items-center justify-between pb-4 border-b border-slate-800">
                            <div>
                                <h3 class="text-lg font-black text-white" id="modal-title">Assign to Class</h3>
                                <p class="text-xs text-slate-400 mt-1">Assign <span class="text-indigo-400 font-bold">{{ $selectedTemplateForAssign->name }}</span> to specific classes</p>
                            </div>
                            <button type="button" wire:click="closeAssignModal" class="text-slate-400 hover:text-white transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>

                        <!-- Grades List -->
                        <div class="mt-4 max-h-[300px] overflow-y-auto space-y-2 pr-1">
                            @forelse($schoolGrades as $grade)
                                <div class="bg-slate-950/40 border border-slate-800/80 rounded-2xl p-4 flex items-center justify-between">
                                    <div>
                                        <h4 class="text-xs font-bold text-white">{{ $grade->name }}</h4>
                                        <p class="text-[10px] text-slate-500 mt-0.5">
                                            @if($grade->template_id == $selectedTemplateForAssign->id)
                                                <span class="text-indigo-400 font-semibold">Active Class Override</span>
                                            @elseif($grade->template_id)
                                                <span>Overridden by: <span class="text-slate-300 font-semibold">{{ $grade->template_id == 'premium-landscape' ? 'Premium Landscape Student ID' : $grade->template_id }}</span></span>
                                            @else
                                                <span class="text-slate-600">Using School Default</span>
                                            @endif
                                        </p>
                                    </div>
                                    <button type="button" wire:click="assignToGrade({{ $grade->id }}, {{ $selectedTemplateForAssign->id }})" 
                                        class="px-3.5 py-1.5 rounded-xl text-[10px] font-bold tracking-wider uppercase transition 
                                        {{ $grade->template_id == $selectedTemplateForAssign->id 
                                            ? 'bg-red-500/10 hover:bg-red-500/20 text-red-400 border border-red-500/20' 
                                            : 'bg-indigo-600 hover:bg-indigo-700 text-white' }}">
                                        {{ $grade->template_id == $selectedTemplateForAssign->id ? 'Remove' : 'Assign' }}
                                    </button>
                                </div>
                            @empty
                                <div class="text-center py-6 text-slate-500 text-xs">
                                    No classes (grades) found for this school.
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <div class="bg-slate-950 px-6 py-4 flex justify-end">
                        <button type="button" wire:click="closeAssignModal" class="px-5 py-2 rounded-xl text-xs font-bold text-slate-400 hover:text-white transition">
                            Done
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Preview Modal -->
    @if($isPreviewModalOpen && $selectedTemplateForPreview)
        <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:p-0">
                <div class="fixed inset-0 bg-slate-950/75 backdrop-blur-md transition-opacity" wire:click="closePreviewModal"></div>

                <!-- Center elements -->
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="inline-block align-middle bg-slate-900 border border-slate-800 rounded-[2rem] p-8 text-center overflow-hidden shadow-2xl transform transition-all max-w-xl w-full relative">
                    <!-- Close Button -->
                    <button type="button" wire:click="closePreviewModal" class="absolute top-5 right-5 text-slate-400 hover:text-white transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>

                    <!-- Title/Header in Modal -->
                    <div class="mb-6 text-left">
                        <h3 class="text-lg font-black text-white">{{ $selectedTemplateForPreview->name }}</h3>
                        <p class="text-xs text-slate-400 mt-1">Full-scale design preview (CR-80 Landscape Layout)</p>
                    </div>

                    <!-- Card Preview at 100% scale -->
                    <div class="flex justify-center items-center py-6 bg-slate-950/30 rounded-[1.75rem] border border-slate-800/60 p-4">
                        @include($selectedTemplateForPreview->view_path, ['student' => $mockStudent, 'school' => $mockSchool])
                    </div>

                    <!-- Footer Actions inside Modal -->
                    <div class="mt-6 flex justify-end">
                        <button type="button" wire:click="closePreviewModal" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition">
                            Close Preview
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
