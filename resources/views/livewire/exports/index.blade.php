<?php

use Livewire\Volt\Component;
use App\Models\School;
use App\Models\Campaign;
use App\Models\Grade;
use App\Models\Division;
use App\Models\Student;
use App\Models\CampaignStudent;
use App\Models\Export;

new class extends Component {
    public ?int $schoolId = null;
    public ?int $selectedCampaignId = null;
    public array $selectedGradeIds = [];
    public array $selectedDivisionIds = [];

    public bool $exportOnlyVerified = true;
    public string $exportType = 'imposition_pdf'; // 'imposition_pdf', 'jpg_zip', 'single_card_pdf', 'excel_photo_zip'
    public string $exportPageSize = '297x210';
    public string $exportCardSize = 'punch'; // 'punch' (86x54mm CR80) or 'bleed' (90x57mm canvas)
    public float $exportBleedMm = 0.0;
    public float $exportMarginMm = 0.0;
    public float $exportHorizontalGutterMm = 4.0;
    public float $exportVerticalGutterMm = 4.0;
    public float $exportGutterMm = 4.0;
    public float $exportCustomWidthMm = 297.0;
    public float $exportCustomHeightMm = 210.0;
    public bool $exportMirrorPrint = false;
    public bool $exportSendForPrinting = false;

    public array $preSelectedStudentIds = [];

    public function mount()
    {
        $this->schoolId = session('active_school_id');
        if (!$this->schoolId) {
            $user = auth()->user();
            $school = $user->schools()->first();
            if ($school) {
                session(['active_school_id' => $school->id]);
                $this->schoolId = $school->id;
            }
        }

        // Check if specific student IDs were passed in URL query or session
        $queryStudents = request()->query('students');
        if ($queryStudents) {
            $this->preSelectedStudentIds = array_filter(array_map('intval', explode(',', $queryStudents)));
        } elseif (session()->has('export_preselected_students')) {
            $this->preSelectedStudentIds = session()->pull('export_preselected_students', []);
        }

        // Set default active campaign
        if ($this->schoolId) {
            $activeCamp = Campaign::where('school_id', $this->schoolId)->orderBy('id', 'desc')->first();
            if ($activeCamp) {
                $this->selectedCampaignId = $activeCamp->id;
            }
        }
    }

    public function toggleGrade(int $gradeId)
    {
        if (in_array($gradeId, $this->selectedGradeIds)) {
            $this->selectedGradeIds = array_values(array_diff($this->selectedGradeIds, [$gradeId]));
            // Remove divisions belonging to this grade
            $gradeDivIds = Division::where('grade_id', $gradeId)->pluck('id')->all();
            $this->selectedDivisionIds = array_values(array_diff($this->selectedDivisionIds, $gradeDivIds));
        } else {
            $this->selectedGradeIds[] = $gradeId;
        }
    }

    public function selectAllGrades()
    {
        if (!$this->schoolId) return;
        $this->selectedGradeIds = Grade::where('school_id', $this->schoolId)->pluck('id')->map(fn($id) => (int)$id)->all();
    }

    public function clearAllGrades()
    {
        $this->selectedGradeIds = [];
        $this->selectedDivisionIds = [];
    }

    public function toggleDivision(int $divisionId)
    {
        if (in_array($divisionId, $this->selectedDivisionIds)) {
            $this->selectedDivisionIds = array_values(array_diff($this->selectedDivisionIds, [$divisionId]));
        } else {
            $this->selectedDivisionIds[] = $divisionId;
        }
    }

    public function selectAllDivisions()
    {
        if (!$this->schoolId) return;
        $query = Division::query();
        if (!empty($this->selectedGradeIds)) {
            $query->whereIn('grade_id', $this->selectedGradeIds);
        } else {
            $query->whereHas('grade', fn($q) => $q->where('school_id', $this->schoolId));
        }
        $this->selectedDivisionIds = $query->pluck('id')->map(fn($id) => (int)$id)->all();
    }

    public function clearAllDivisions()
    {
        $this->selectedDivisionIds = [];
    }

    public function clearPreselected()
    {
        $this->preSelectedStudentIds = [];
    }

    public function triggerExport()
    {
        $user = auth()->user();
        if (!$this->schoolId) {
            session()->flash('error', 'No active school selected.');
            return;
        }

        $school = School::findOrFail($this->schoolId);

        // Resolve target students
        $query = CampaignStudent::whereHas('campaign', fn($q) => $q->where('school_id', $this->schoolId));

        if (!empty($this->preSelectedStudentIds)) {
            $query->whereIn('student_id', $this->preSelectedStudentIds);
        } else {
            if ($this->selectedCampaignId) {
                $query->where('campaign_id', $this->selectedCampaignId);
            }
            if (!empty($this->selectedGradeIds)) {
                $query->whereIn('grade_id', $this->selectedGradeIds);
            }
            if (!empty($this->selectedDivisionIds)) {
                $query->whereIn('division_id', $this->selectedDivisionIds);
            }
            if ($this->exportOnlyVerified) {
                $query->where('status', CampaignStudent::STATUS_VERIFIED);
            }
        }

        $targetStudentIds = $query->pluck('student_id')->unique()->values()->all();

        if (empty($targetStudentIds)) {
            session()->flash('error', 'No eligible students found for the selected export criteria.');
            return;
        }

        $neededCredits = count($targetStudentIds);

        // Verify credit balance (1 Credit = 1 Student ID Card)
        if (!$school->hasCredits($neededCredits)) {
            session()->flash('error', "Insufficient wallet balance. This export requires {$neededCredits} credits, but your current balance is {$school->credits_balance} credits. Please recharge your wallet.");
            return;
        }

        $params = [
            'campaign_id' => $this->selectedCampaignId,
            'grade_ids' => $this->selectedGradeIds,
            'division_ids' => $this->selectedDivisionIds,
            'student_ids' => $targetStudentIds,
            'page_size' => $this->exportPageSize,
            'card_size' => $this->exportCardSize,
            'custom_width_mm' => $this->exportCustomWidthMm,
            'custom_height_mm' => $this->exportCustomHeightMm,
            'bleed_mm' => 0.0,
            'margin_mm' => 0.0,
            'horizontal_gutter_mm' => $this->exportHorizontalGutterMm,
            'vertical_gutter_mm' => $this->exportVerticalGutterMm,
            'gutter_mm' => $this->exportHorizontalGutterMm,
            'mirror_print' => $this->exportMirrorPrint,
            'send_for_printing' => $this->exportSendForPrinting,
        ];

        $export = Export::create([
            'user_id' => $user->id,
            'school_id' => $this->schoolId,
            'type' => $this->exportType,
            'status' => 'pending',
            'params' => $params,
            'total_items' => count($targetStudentIds),
            'processed_items' => 0,
        ]);

        try {
            match ($this->exportType) {
                'excel_photo_zip' => \App\Jobs\ExportExcelPhotoZipJob::dispatch($export->id),
                'jpg_zip' => \App\Jobs\ExportJpgZipJob::dispatch($export->id),
                'png_zip' => \App\Jobs\ExportPngZipJob::dispatch($export->id),
                'single_card_pdf' => \App\Jobs\ExportSingleCardPdfJob::dispatch($export->id),
                'imposition_pdf' => \App\Jobs\ExportImpositionPdfJob::dispatch($export->id),
            };

            session()->flash('message', "🚀 Export task #{$export->id} queued successfully! Processing {$neededCredits} cards in background — you can track live progress below.");
        } catch (\Throwable $e) {
            $export->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
            session()->flash('error', 'Export failed: ' . $e->getMessage());
        }
    }

    public function deleteExport($exportId)
    {
        $export = Export::where('school_id', $this->schoolId)
            ->where('user_id', auth()->id())
            ->findOrFail($exportId);

        if ($export->file_path && \Storage::disk('private')->exists($export->file_path)) {
            \Storage::disk('private')->delete($export->file_path);
        }

        $tmpDir = storage_path('app/private/tmp/exports/' . $export->id);
        if (file_exists($tmpDir) && is_dir($tmpDir)) {
            $files = array_diff(scandir($tmpDir), ['.', '..']);
            foreach ($files as $file) {
                @unlink("$tmpDir/$file");
            }
            @rmdir($tmpDir);
        }

        $export->delete();
        session()->flash('message', 'Export history deleted.');
    }

    public function clearAllExports()
    {
        $exports = Export::where('school_id', $this->schoolId)
            ->where('user_id', auth()->id())
            ->get();

        foreach ($exports as $exp) {
            if ($exp->file_path && \Storage::disk('private')->exists($exp->file_path)) {
                \Storage::disk('private')->delete($exp->file_path);
            }
            $tmpDir = storage_path('app/private/tmp/exports/' . $exp->id);
            if (file_exists($tmpDir) && is_dir($tmpDir)) {
                $files = array_diff(scandir($tmpDir), ['.', '..']);
                foreach ($files as $file) {
                    @unlink("$tmpDir/$file");
                }
                @rmdir($tmpDir);
            }
            $exp->delete();
        }

        session()->flash('message', 'All export history cleared.');
    }

    // Print Preview Modal State
    public bool $isPreviewModalOpen = false;
    public string $previewViewMode = 'sheet'; // 'sheet' or 'card'
    public string $previewSheetSide = 'front'; // 'front' or 'back'
    public int $previewPageIndex = 0;
    public int $previewStudentIndex = 0;
    public bool $previewShowPunchGuide = true;

    public function openPrintPreview()
    {
        $this->isPreviewModalOpen = true;
        $this->previewPageIndex = 0;
        $this->previewStudentIndex = 0;
    }

    public function closePrintPreview()
    {
        $this->isPreviewModalOpen = false;
    }

    public function setPreviewSheetSide(string $side)
    {
        $this->previewSheetSide = $side;
    }

    public function setPreviewViewMode(string $mode)
    {
        $this->previewViewMode = $mode;
    }

    public function nextPreviewPage(int $totalPages)
    {
        if ($this->previewPageIndex < $totalPages - 1) {
            $this->previewPageIndex++;
        }
    }

    public function prevPreviewPage()
    {
        if ($this->previewPageIndex > 0) {
            $this->previewPageIndex--;
        }
    }

    public function nextPreviewStudent(int $totalStudents)
    {
        if ($this->previewStudentIndex < $totalStudents - 1) {
            $this->previewStudentIndex++;
        }
    }

    public function prevPreviewStudent()
    {
        if ($this->previewStudentIndex > 0) {
            $this->previewStudentIndex--;
        }
    }

    public function with(): array
    {
        $activeSchool = $this->schoolId ? School::find($this->schoolId) : null;
        $campaigns = $this->schoolId ? Campaign::where('school_id', $this->schoolId)->orderBy('id', 'desc')->get() : collect();
        $grades = $this->schoolId ? Grade::where('school_id', $this->schoolId)->withCount('divisions')->orderBy('name')->get() : collect();

        $divisionsQuery = Division::query();
        if (!empty($this->selectedGradeIds)) {
            $divisionsQuery->whereIn('grade_id', $this->selectedGradeIds);
        } else {
            $divisionsQuery->whereHas('grade', fn($q) => $q->where('school_id', $this->schoolId));
        }
        $divisions = $this->schoolId ? $divisionsQuery->with('grade')->orderBy('name')->get() : collect();

        // Calculate student counts
        $baseQuery = CampaignStudent::whereHas('campaign', fn($q) => $q->where('school_id', $this->schoolId));

        if (!empty($this->preSelectedStudentIds)) {
            $baseQuery->whereIn('student_id', $this->preSelectedStudentIds);
        } else {
            if ($this->selectedCampaignId) {
                $baseQuery->where('campaign_id', $this->selectedCampaignId);
            }
            if (!empty($this->selectedGradeIds)) {
                $baseQuery->whereIn('grade_id', $this->selectedGradeIds);
            }
            if (!empty($this->selectedDivisionIds)) {
                $baseQuery->whereIn('division_id', $this->selectedDivisionIds);
            }
        }

        $allMatchingStudentIds = (clone $baseQuery)->pluck('student_id')->unique();
        $totalCount = $allMatchingStudentIds->count();
        $verifiedCount = (clone $baseQuery)->where('status', CampaignStudent::STATUS_VERIFIED)->pluck('student_id')->unique()->count();
        $unverifiedCount = max(0, $totalCount - $verifiedCount);

        $effectiveTargetCount = (!empty($this->preSelectedStudentIds) || !$this->exportOnlyVerified) ? $totalCount : $verifiedCount;

        $userExports = $this->schoolId ? Export::where('school_id', $this->schoolId)
            ->where('user_id', auth()->id())
            ->orderBy('id', 'desc')
            ->take(15)
            ->get() : collect();

        $hasActiveExport = $userExports->contains(fn($e) => in_array($e->status, ['pending', 'processing']));

        // Print Preview Data Resolution
        $layoutService = app(\App\Services\ImpositionLayoutService::class);
        $templateResolver = app(\App\Services\TemplateResolverService::class);

        $sampleGradeId = !empty($this->selectedGradeIds) ? $this->selectedGradeIds[0] : null;
        $sampleTemplate = $templateResolver->getEffectiveTemplate($this->schoolId, $sampleGradeId);

        $isPortrait = ($sampleTemplate->orientation ?? 'landscape') === 'portrait';
        $isPunch = ($this->exportCardSize === 'punch');
        if ($isPunch) {
            $cardWidthMm = $isPortrait ? 54.0 : 86.0;
            $cardHeightMm = $isPortrait ? 86.0 : 54.0;
        } else {
            $cardWidthMm = $isPortrait ? 57.0 : 90.0;
            $cardHeightMm = $isPortrait ? 90.0 : 57.0;
        }

        $impositionParams = [
            'page_size' => $this->exportPageSize,
            'card_size' => $this->exportCardSize,
            'custom_width_mm' => $this->exportCustomWidthMm,
            'custom_height_mm' => $this->exportCustomHeightMm,
            'horizontal_gutter_mm' => $this->exportHorizontalGutterMm,
            'vertical_gutter_mm' => $this->exportVerticalGutterMm,
            'gutter_mm' => $this->exportHorizontalGutterMm,
            'bleed_mm' => 0.0,
            'margin_mm' => 0.0,
        ];
        $sheetLayout = $layoutService->calculateLayout($impositionParams, $cardWidthMm, $cardHeightMm);
        $cardsPerPage = max(1, $sheetLayout['cards_per_page']);

        $previewStudentsList = collect();
        $pageCards = [];
        $previewTotalSheets = 1;

        if ($this->isPreviewModalOpen) {
            $previewQuery = Student::whereIn('id', $allMatchingStudentIds)
                ->with(['campaignStudents' => function($q) {
                    $q->whereHas('campaign', fn($cq) => $cq->where('school_id', $this->schoolId))
                      ->with(['grade', 'division', 'verifier', 'campaign']);
                }]);

            if ($this->exportOnlyVerified && empty($this->preSelectedStudentIds)) {
                $previewQuery->whereHas('campaignStudents', function($csQ) {
                    $csQ->where('status', CampaignStudent::STATUS_VERIFIED)
                       ->whereHas('campaign', fn($cq) => $cq->where('school_id', $this->schoolId));
                });
            }

            $previewStudentsList = $previewQuery->take(100)->get();
            $totalPreviewStudents = $previewStudentsList->count();
            $previewTotalSheets = max(1, (int) ceil(max(1, $totalPreviewStudents) / $cardsPerPage));

            $clampedPageIndex = min($this->previewPageIndex, $previewTotalSheets - 1);
            $slice = $previewStudentsList->slice($clampedPageIndex * $cardsPerPage, $cardsPerPage)->values();

            $cols = $sheetLayout['cols'];
            $rows = $sheetLayout['rows'];
            $isMirrored = $this->exportMirrorPrint;

            for ($slotIdx = 0; $slotIdx < $cardsPerPage; $slotIdx++) {
                $row = (int) floor($slotIdx / $cols);
                $rawCol = $slotIdx % $cols;
                $col = ($isMirrored || $this->previewSheetSide === 'back') ? ($cols - 1 - $rawCol) : $rawCol;
                $std = $slice->get($slotIdx);

                $stdEnrollment = $std?->campaignStudents?->first();
                $stdTemplate = $std ? $templateResolver->getEffectiveTemplate($this->schoolId, $stdEnrollment?->grade_id) : $sampleTemplate;

                $pageCards[] = [
                    'slot_index' => $slotIdx,
                    'row' => $row,
                    'col' => $col,
                    'student' => $std,
                    'template' => $stdTemplate,
                ];
            }
        }

        return [
            'activeSchool' => $activeSchool,
            'campaigns' => $campaigns,
            'grades' => $grades,
            'divisions' => $divisions,
            'totalCount' => $totalCount,
            'verifiedCount' => $verifiedCount,
            'unverifiedCount' => $unverifiedCount,
            'effectiveTargetCount' => $effectiveTargetCount,
            'userExports' => $userExports,
            'hasActiveExport' => $hasActiveExport,
            'sheetLayout' => $sheetLayout,
            'previewStudentsList' => $previewStudentsList,
            'pageCards' => $pageCards,
            'previewTotalSheets' => $previewTotalSheets,
            'sampleTemplate' => $sampleTemplate,
        ];
    }
}; ?>

<div class="space-y-8" @if($hasActiveExport) wire:poll.2s @endif>
    <!-- Flash Messages -->
    @if (session()->has('message'))
        <div class="p-4 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 rounded-2xl flex items-center justify-between shadow-sm">
            <div class="flex items-center space-x-2">
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span class="text-sm font-semibold">{{ session('message') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700">✕</button>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="p-4 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-300 rounded-2xl flex items-center justify-between shadow-sm">
            <div class="flex items-center space-x-2">
                <svg class="w-5 h-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                <span class="text-sm font-semibold">{{ session('error') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-rose-500 hover:text-rose-700">✕</button>
        </div>
    @endif

    <!-- TOP HERO / WALLET SUMMARY CARD -->
    <div class="bg-gradient-to-br from-indigo-900 via-indigo-800 to-indigo-950 rounded-3xl p-6 sm:p-8 text-white shadow-xl relative overflow-hidden">
        <div class="absolute -right-12 -bottom-12 w-64 h-64 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
            <div class="space-y-2">
                <div class="inline-flex items-center gap-2 px-3 py-1 bg-white/10 backdrop-blur-md rounded-full text-xs font-bold text-indigo-200 border border-white/10">
                    <span>💳 Production Print Engine</span>
                    <span>•</span>
                    <span>1 Card = 1 Credit</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">
                    {{ $activeSchool->name ?? 'Active School' }}
                </h1>
                <p class="text-xs sm:text-sm text-indigo-200/90 max-w-xl">
                    Configure your high-resolution commercial imposition sheets, single-card printer batches, or JPG photo packs with automatic bleed trimming.
                </p>
            </div>

            <!-- Wallet Balance Pill -->
            <div class="bg-white/10 backdrop-blur-xl border border-white/20 rounded-2xl p-4 sm:p-5 flex items-center gap-5 shrink-0">
                <div>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-indigo-200 block">Available Credits</span>
                    <span class="text-2xl sm:text-3xl font-black font-mono {{ ($activeSchool->credits_balance ?? 0) < $effectiveTargetCount ? 'text-rose-300' : 'text-emerald-300' }}">
                        {{ number_format($activeSchool->credits_balance ?? 0) }}
                    </span>
                </div>
                <a href="{{ route('billing') }}" wire:navigate class="px-4 py-2.5 bg-white text-indigo-900 hover:bg-indigo-50 rounded-xl text-xs font-black uppercase tracking-wider shadow-lg transition-all transform hover:-translate-y-0.5 flex items-center gap-1.5">
                    <span>Recharge</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                </a>
            </div>
        </div>
    </div>

    <!-- MAIN CONFIGURATION GRID (2 COLUMNS) -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
        
        <!-- LEFT 2 COLUMNS: CONFIGURATION FORM -->
        <div class="lg:col-span-2 space-y-6">

            <!-- STEP 1: SELECT TARGET STUDENTS / SCOPE -->
            <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 shadow-sm border border-gray-100 dark:border-gray-700 space-y-5">
                <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700/80 pb-4">
                    <div class="flex items-center space-x-3">
                        <span class="w-8 h-8 rounded-full bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 font-black text-sm flex items-center justify-center border border-indigo-200 dark:border-indigo-800">
                            1
                        </span>
                        <div>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-gray-100">Select Export Scope</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Filter students by Campaign, Grade, or pre-selected batch.</p>
                        </div>
                    </div>

                    @if(!empty($preSelectedStudentIds))
                        <div class="flex items-center gap-2">
                            <span class="px-3 py-1 bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 rounded-xl text-xs font-bold border border-amber-200 dark:border-amber-800">
                                Pre-Selected: {{ count($preSelectedStudentIds) }} Students
                            </span>
                            <button wire:click="clearPreselected" type="button" class="text-xs text-rose-500 hover:underline font-bold">
                                Clear
                            </button>
                        </div>
                    @endif
                </div>

                @if(empty($preSelectedStudentIds))
                    <div class="space-y-5">
                        <!-- Campaign Filter -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">{{ __('Active Campaign') }}</label>
                            <select wire:model.live="selectedCampaignId" class="w-full text-xs rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 font-semibold focus:ring-indigo-500 py-2.5">
                                <option value="">{{ __('All Campaigns') }}</option>
                                @foreach($campaigns as $camp)
                                    <option value="{{ $camp->id }}">{{ $camp->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Multi-Select Grades -->
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-bold text-gray-800 dark:text-gray-200">{{ __('Grades / Classes') }}</span>
                                    @if(empty($selectedGradeIds))
                                        <span class="px-2 py-0.5 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 rounded-md text-[10px] font-bold">All Grades (Default)</span>
                                    @else
                                        <span class="px-2 py-0.5 bg-indigo-100 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 rounded-md text-[10px] font-extrabold">
                                            {{ count($selectedGradeIds) }} / {{ count($grades) }} Selected
                                        </span>
                                    @endif
                                </div>
                                <div class="flex items-center gap-2 text-xs">
                                    <button type="button" wire:click="selectAllGrades" class="text-indigo-600 dark:text-indigo-400 hover:underline font-bold text-[11px] cursor-pointer">
                                        {{ __('Select All') }}
                                    </button>
                                    <span class="text-gray-300 dark:text-gray-600">•</span>
                                    <button type="button" wire:click="clearAllGrades" class="text-gray-500 hover:text-rose-500 dark:text-gray-400 font-medium text-[11px] cursor-pointer">
                                        {{ __('Clear') }}
                                    </button>
                                </div>
                            </div>

                            @if($grades->count() > 0)
                                <div class="flex flex-wrap gap-2 p-3.5 bg-gray-50/80 dark:bg-gray-900/40 border border-gray-100 dark:border-gray-700/60 rounded-2xl">
                                    @foreach($grades as $gr)
                                        @php $isGradeSelected = in_array($gr->id, $selectedGradeIds); @endphp
                                        <button type="button" wire:click="toggleGrade({{ $gr->id }})" class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 cursor-pointer shadow-sm {{ $isGradeSelected ? 'bg-indigo-600 text-white shadow-indigo-500/20 scale-100 ring-2 ring-indigo-500/30' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700 hover:border-indigo-400 dark:hover:border-indigo-500 hover:bg-indigo-50/20' }}">
                                            <span class="w-4 h-4 rounded flex items-center justify-center text-[10px] {{ $isGradeSelected ? 'bg-white/20 text-white font-black' : 'border border-gray-300 dark:border-gray-600' }}">
                                                @if($isGradeSelected) ✓ @endif
                                            </span>
                                            <span>{{ $gr->name }}</span>
                                            @if($gr->divisions_count > 0)
                                                <span class="px-1.5 py-0.5 rounded text-[9px] {{ $isGradeSelected ? 'bg-indigo-700/80 text-indigo-100' : 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400' }}">
                                                    {{ $gr->divisions_count }} div
                                                </span>
                                            @endif
                                        </button>
                                    @endforeach
                                </div>
                            @else
                                <div class="p-3 text-xs text-gray-400 italic bg-gray-50 dark:bg-gray-900/30 rounded-xl border border-gray-100 dark:border-gray-800">
                                    {{ __('No grades found for this school.') }}
                                </div>
                            @endif
                        </div>

                        <!-- Multi-Select Divisions -->
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-bold text-gray-800 dark:text-gray-200">{{ __('Divisions / Sections') }}</span>
                                    @if(empty($selectedDivisionIds))
                                        <span class="px-2 py-0.5 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 rounded-md text-[10px] font-bold">All Divisions in Scope (Default)</span>
                                    @else
                                        <span class="px-2 py-0.5 bg-purple-100 dark:bg-purple-950/60 text-purple-700 dark:text-purple-300 rounded-md text-[10px] font-extrabold">
                                            {{ count($selectedDivisionIds) }} / {{ count($divisions) }} Selected
                                        </span>
                                    @endif
                                </div>
                                <div class="flex items-center gap-2 text-xs">
                                    <button type="button" wire:click="selectAllDivisions" class="text-purple-600 dark:text-purple-400 hover:underline font-bold text-[11px] cursor-pointer">
                                        {{ __('Select All') }}
                                    </button>
                                    <span class="text-gray-300 dark:text-gray-600">•</span>
                                    <button type="button" wire:click="clearAllDivisions" class="text-gray-500 hover:text-rose-500 dark:text-gray-400 font-medium text-[11px] cursor-pointer">
                                        {{ __('Clear') }}
                                    </button>
                                </div>
                            </div>

                            @if($divisions->count() > 0)
                                <div class="flex flex-wrap gap-2 p-3.5 bg-gray-50/80 dark:bg-gray-900/40 border border-gray-100 dark:border-gray-700/60 rounded-2xl max-h-56 overflow-y-auto">
                                    @foreach($divisions as $div)
                                        @php $isDivSelected = in_array($div->id, $selectedDivisionIds); @endphp
                                        <button type="button" wire:click="toggleDivision({{ $div->id }})" class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 cursor-pointer shadow-sm {{ $isDivSelected ? 'bg-purple-600 text-white shadow-purple-500/20 ring-2 ring-purple-500/30' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700 hover:border-purple-400 dark:hover:border-purple-500 hover:bg-purple-50/20' }}">
                                            <span class="w-3.5 h-3.5 rounded flex items-center justify-center text-[9px] {{ $isDivSelected ? 'bg-white/20 text-white font-black' : 'border border-gray-300 dark:border-gray-600' }}">
                                                @if($isDivSelected) ✓ @endif
                                            </span>
                                            <span>{{ $div->name }}</span>
                                            @if($div->grade)
                                                <span class="text-[10px] {{ $isDivSelected ? 'text-purple-200' : 'text-gray-400' }}">({{ $div->grade->name }})</span>
                                            @endif
                                        </button>
                                    @endforeach
                                </div>
                            @else
                                <div class="p-3 text-xs text-gray-400 italic bg-gray-50 dark:bg-gray-900/30 rounded-xl border border-gray-100 dark:border-gray-800">
                                    {{ __('No divisions found.') }}
                                </div>
                            @endif
                        </div>

                        <!-- Verified Students Only Toggle -->
                        <div class="p-4 border rounded-2xl flex items-start justify-between gap-4 transition {{ $exportOnlyVerified ? 'border-emerald-500 bg-emerald-50/50 dark:bg-emerald-950/20' : 'border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/40' }}">
                            <div>
                                <span class="font-bold text-xs text-gray-900 dark:text-gray-100 flex items-center gap-2">
                                    <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    {{ __('Export Only Verified Students') }}
                                    @if ($exportOnlyVerified)
                                        <span class="px-2 py-0.5 bg-emerald-600 text-white rounded-md text-[9px] font-extrabold uppercase tracking-wider">{{ __('Active (:count)', ['count' => $verifiedCount]) }}</span>
                                    @endif
                                </span>
                                <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">
                                    {{ __('If enabled, exports only students with "Verified" status (:verifiedCount). Excludes :unverifiedCount drafting and pending profiles to save wallet credits.', ['verifiedCount' => $verifiedCount, 'unverifiedCount' => $unverifiedCount]) }}
                                </p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer shrink-0 mt-1">
                                <input type="checkbox" wire:model.live="exportOnlyVerified" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-300 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-emerald-600"></div>
                            </label>
                        </div>
                    </div>
                @else
                    <div class="p-4 bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 rounded-2xl flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <span class="text-2xl">🎯</span>
                            <div>
                                <h4 class="text-xs font-bold text-amber-900 dark:text-amber-200">Custom Batch Selected</h4>
                                <p class="text-[11px] text-amber-700 dark:text-amber-400">Exporting {{ count($preSelectedStudentIds) }} explicitly chosen students from the student table.</p>
                            </div>
                        </div>
                        <button wire:click="clearPreselected" type="button" class="px-3 py-1.5 bg-white dark:bg-gray-800 text-amber-900 dark:text-amber-200 border border-amber-200 dark:border-amber-700 rounded-xl text-xs font-bold hover:bg-amber-100 transition">
                            Switch to Filters
                        </button>
                    </div>
                @endif
            </div>

            <!-- STEP 2: SELECT EXPORT FORMAT -->
            <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 shadow-sm border border-gray-100 dark:border-gray-700 space-y-5">
                <div class="flex items-center space-x-3 border-b border-gray-100 dark:border-gray-700/80 pb-4">
                    <span class="w-8 h-8 rounded-full bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 font-black text-sm flex items-center justify-center border border-indigo-200 dark:border-indigo-800">
                        2
                    </span>
                    <div>
                        <h3 class="text-sm font-bold text-gray-900 dark:text-gray-100">Choose Export Format</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Select the output file type and rendering mode.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Option 1: Imposition PDF -->
                    <label class="p-4 border rounded-2xl flex flex-col justify-between cursor-pointer transition relative {{ $exportType === 'imposition_pdf' ? 'border-indigo-600 bg-indigo-50/50 dark:bg-indigo-950/20 shadow-md ring-2 ring-indigo-500/20' : 'border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/30' }}">
                        <input type="radio" wire:model.live="exportType" value="imposition_pdf" class="hidden" />
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="p-2 bg-indigo-100 dark:bg-indigo-900/60 text-indigo-700 dark:text-indigo-300 rounded-xl">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                </span>
                                <span class="px-2 py-0.5 bg-indigo-600 text-white rounded text-[9px] font-black uppercase">Recommended</span>
                            </div>
                            <span class="font-bold text-sm text-gray-900 dark:text-gray-100 block">{{ __('Print Imposition PDF') }}</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400 mt-1 block">
                                Multi-card imposition sheets (A4, 12x18, custom) with precise crop trim marks and exact 4mm gutter spacing.
                            </span>
                        </div>
                        <div class="mt-4 pt-3 border-t border-gray-200/60 dark:border-gray-700/60 flex items-center justify-between text-[11px] font-bold text-indigo-600 dark:text-indigo-400">
                            <span>Ready for Commercial Printing</span>
                            <span>→</span>
                        </div>
                    </label>

                    <!-- Option 2: Rendered Cards JPG (ZIP) -->
                    <label class="p-4 border rounded-2xl flex flex-col justify-between cursor-pointer transition relative {{ $exportType === 'jpg_zip' ? 'border-indigo-600 bg-indigo-50/50 dark:bg-indigo-950/20 shadow-md ring-2 ring-indigo-500/20' : 'border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/30' }}">
                        <input type="radio" wire:model.live="exportType" value="jpg_zip" class="hidden" />
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="p-2 bg-emerald-100 dark:bg-emerald-900/60 text-emerald-700 dark:text-emerald-300 rounded-xl">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </span>
                                <span class="px-2 py-0.5 bg-emerald-600 text-white rounded text-[9px] font-black uppercase">Fast & Light</span>
                            </div>
                            <span class="font-bold text-sm text-gray-900 dark:text-gray-100 block">{{ __('Rendered Cards JPG (ZIP)') }}</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400 mt-1 block">
                                High-resolution individual JPG image per student. Ultra lightweight, 90% smaller ZIP package.
                            </span>
                        </div>
                        <div class="mt-4 pt-3 border-t border-gray-200/60 dark:border-gray-700/60 flex items-center justify-between text-[11px] font-bold text-emerald-600 dark:text-emerald-400">
                            <span>1 Card per JPG Image</span>
                            <span>→</span>
                        </div>
                    </label>

                    <!-- Option 3: Single Card PDF -->
                    <label class="p-4 border rounded-2xl flex flex-col justify-between cursor-pointer transition relative {{ $exportType === 'single_card_pdf' ? 'border-indigo-600 bg-indigo-50/50 dark:bg-indigo-950/20 shadow-md ring-2 ring-indigo-500/20' : 'border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/30' }}">
                        <input type="radio" wire:model.live="exportType" value="single_card_pdf" class="hidden" />
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="p-2 bg-blue-100 dark:bg-blue-900/60 text-blue-700 dark:text-blue-300 rounded-xl">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                </span>
                            </div>
                            <span class="font-bold text-sm text-gray-900 dark:text-gray-100 block">{{ __('Single Card PDF (ID Printer)') }}</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400 mt-1 block">
                                1 Card per Page (exact CR80 size) for direct desktop thermal PVC card printers (Evolis, Zebra, Fargo).
                            </span>
                        </div>
                        <div class="mt-4 pt-3 border-t border-gray-200/60 dark:border-gray-700/60 flex items-center justify-between text-[11px] font-bold text-blue-600 dark:text-blue-400">
                            <span>Direct Thermal CR80</span>
                            <span>→</span>
                        </div>
                    </label>

                    <!-- Option 4: Excel Roster + Photos ZIP -->
                    <label class="p-4 border rounded-2xl flex flex-col justify-between cursor-pointer transition relative {{ $exportType === 'excel_photo_zip' ? 'border-indigo-600 bg-indigo-50/50 dark:bg-indigo-950/20 shadow-md ring-2 ring-indigo-500/20' : 'border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/30' }}">
                        <input type="radio" wire:model.live="exportType" value="excel_photo_zip" class="hidden" />
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="p-2 bg-amber-100 dark:bg-amber-900/60 text-amber-700 dark:text-amber-300 rounded-xl">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </span>
                            </div>
                            <span class="font-bold text-sm text-gray-900 dark:text-gray-100 block">{{ __('Excel Roster + Photos ZIP') }}</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400 mt-1 block">
                                Clean formatted spreadsheet with student data linked to a organized directory of original photos.
                            </span>
                        </div>
                        <div class="mt-4 pt-3 border-t border-gray-200/60 dark:border-gray-700/60 flex items-center justify-between text-[11px] font-bold text-amber-600 dark:text-amber-400">
                            <span>Data Roster + Photos</span>
                            <span>→</span>
                        </div>
                    </label>
                </div>

                <!-- Card Cut / Bleed Dimension Selector -->
                <div class="pt-4 border-t border-gray-100 dark:border-gray-700/80 space-y-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-xs font-bold text-gray-900 dark:text-gray-100 block">{{ __('Card Cut & Output Dimensions') }}</span>
                            <span class="text-[11px] text-gray-500 dark:text-gray-400">Choose between trimmed CR80 finished size or full 2mm artwork bleed allowance.</span>
                        </div>
                        <span class="px-2.5 py-1 bg-indigo-100 text-indigo-800 dark:bg-indigo-950/60 dark:text-indigo-300 rounded-lg text-[10px] font-black uppercase">
                            {{ $exportCardSize === 'punch' ? '✂️ Punch Size Active (86×54mm)' : '📐 Bleed Canvas Active (90×57mm)' }}
                        </span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <label class="p-3.5 border rounded-2xl flex items-start gap-3 cursor-pointer transition {{ $exportCardSize === 'punch' ? 'border-indigo-600 bg-indigo-50/50 dark:bg-indigo-950/20 ring-2 ring-indigo-500/20 shadow-xs' : 'border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/30' }}">
                            <input type="radio" wire:model.live="exportCardSize" value="punch" class="mt-0.5 text-indigo-600 focus:ring-indigo-500" />
                            <div>
                                <span class="font-bold text-xs text-gray-900 dark:text-gray-100 block flex items-center gap-1.5">
                                    <span>✂️ {{ __('Punch Size (86 × 54 mm)') }}</span>
                                    <span class="px-1.5 py-0.5 bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200 rounded text-[9px] font-extrabold uppercase">Standard CR80</span>
                                </span>
                                <span class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5 block">
                                    Trims the 2mm outer bleed so the card displays and prints at exact finished CR80 standard card dimensions without extra margins.
                                </span>
                            </div>
                        </label>

                        <label class="p-3.5 border rounded-2xl flex items-start gap-3 cursor-pointer transition {{ $exportCardSize === 'bleed' ? 'border-indigo-600 bg-indigo-50/50 dark:bg-indigo-950/20 ring-2 ring-indigo-500/20 shadow-xs' : 'border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/30' }}">
                            <input type="radio" wire:model.live="exportCardSize" value="bleed" class="mt-0.5 text-indigo-600 focus:ring-indigo-500" />
                            <div>
                                <span class="font-bold text-xs text-gray-900 dark:text-gray-100 block flex items-center gap-1.5">
                                    <span>📐 {{ __('Bleed Canvas (90 × 57 mm)') }}</span>
                                    <span class="px-1.5 py-0.5 bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200 rounded text-[9px] font-extrabold uppercase">+2mm Bleed</span>
                                </span>
                                <span class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5 block">
                                    Includes 2mm extra bleed border on all 4 edges. Recommended for commercial offset printing & mechanical die-cut machines.
                                </span>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- STEP 3: IMPOSITION PRINT SETTINGS (Shown if Imposition PDF selected) -->
            @if ($exportType === 'imposition_pdf')
                <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 shadow-sm border border-gray-100 dark:border-gray-700 space-y-5">
                    <div class="flex items-center space-x-3 border-b border-gray-100 dark:border-gray-700/80 pb-4">
                        <span class="w-8 h-8 rounded-full bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 font-black text-sm flex items-center justify-center border border-indigo-200 dark:border-indigo-800">
                            3
                        </span>
                        <div>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-gray-100">Imposition Sheet Layout Settings</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Configure page dimensions, spacing, and print marks.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">{{ __('Sheet Size Preset') }}</label>
                            <select wire:model.live="exportPageSize" class="w-full text-xs rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 font-medium focus:ring-indigo-500 py-2.5">
                                <option value="297x210">A4 Landscape (297 × 210 mm) [4–8 Cards/Sheet]</option>
                                <option value="210x297">A4 Portrait (210 × 297 mm)</option>
                                <option value="304.8x457.2">12 × 18 Inches (304.8 × 457.2 mm) [Commercial Press]</option>
                                <option value="330.2x482.6">13 × 19 Inches (Super A3)</option>
                                <option value="custom">Custom Dimensions (mm)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5 flex items-center justify-between">
                                <span>{{ __('Horizontal Gutter (X-Gap)') }}</span>
                                <span class="text-[10px] text-gray-400 font-normal">Columns</span>
                            </label>
                            <div class="relative">
                                <input type="number" step="0.5" min="0" wire:model.live="exportHorizontalGutterMm" class="w-full text-xs rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 font-medium focus:ring-indigo-500 pr-12 py-2.5" placeholder="4.0" />
                                <span class="absolute right-3 top-2.5 text-xs font-bold text-gray-400">mm</span>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5 flex items-center justify-between">
                                <span>{{ __('Vertical Gutter (Y-Gap)') }}</span>
                                <span class="text-[10px] text-gray-400 font-normal">Rows</span>
                            </label>
                            <div class="relative">
                                <input type="number" step="0.5" min="0" wire:model.live="exportVerticalGutterMm" class="w-full text-xs rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 font-medium focus:ring-indigo-500 pr-12 py-2.5" placeholder="4.0" />
                                <span class="absolute right-3 top-2.5 text-xs font-bold text-gray-400">mm</span>
                            </div>
                        </div>
                    </div>

                    @if ($exportPageSize === 'custom')
                        <div class="grid grid-cols-2 gap-4 p-4 bg-gray-50 dark:bg-gray-900/40 rounded-2xl border border-gray-100 dark:border-gray-700/60">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">{{ __('Width (mm)') }}</label>
                                <input type="number" step="1" wire:model.live="exportCustomWidthMm" class="w-full text-xs rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 font-medium py-2.5" />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">{{ __('Height (mm)') }}</label>
                                <input type="number" step="1" wire:model.live="exportCustomHeightMm" class="w-full text-xs rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 font-medium py-2.5" />
                            </div>
                        </div>
                    @endif

                    <div class="p-3 bg-indigo-50/50 dark:bg-indigo-950/20 rounded-2xl border border-indigo-100 dark:border-indigo-900/40 flex items-center justify-between text-xs text-indigo-900 dark:text-indigo-300">
                        <div class="flex items-center gap-2">
                            <span class="text-base">📐</span>
                            <span class="font-bold">Cards are automatically positioned with hairline crop marks, {{ $exportHorizontalGutterMm }}mm horizontal gap, and {{ $exportVerticalGutterMm }}mm vertical gap.</span>
                        </div>
                    </div>
                </div>
            @endif

            <!-- ADVANCED TOGGLES -->
            <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 shadow-sm border border-gray-100 dark:border-gray-700 space-y-4">
                <h4 class="text-xs font-extrabold uppercase tracking-wider text-gray-400">Automation & Advanced Options</h4>

                <!-- Card Cut / Bleed Dimension Selector -->
                <div class="p-4 border border-gray-100 dark:border-gray-700 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-4 transition hover:bg-gray-50 dark:hover:bg-gray-700/30">
                    <div>
                        <span class="font-bold text-xs text-gray-900 dark:text-gray-100 flex items-center gap-2">
                            <span>{{ __('Card Output Dimensions') }}</span>
                            <span class="px-2 py-0.5 bg-indigo-100 text-indigo-800 dark:bg-indigo-950/60 dark:text-indigo-300 rounded text-[9px] font-black uppercase">
                                {{ $exportCardSize === 'punch' ? 'Punch Size (86×54mm)' : 'Bleed Canvas (90×57mm)' }}
                            </span>
                        </span>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">
                            {{ __('Choose standard finished punch size (86×54mm trimmed) or full artwork with 2mm bleed allowance (90×57mm).') }}
                        </p>
                    </div>
                    <div class="inline-flex p-1 bg-gray-200 dark:bg-gray-700/80 rounded-xl shrink-0 text-xs font-bold">
                        <button type="button" wire:click="$set('exportCardSize', 'punch')" class="px-3.5 py-1.5 rounded-lg transition cursor-pointer flex items-center gap-1.5 {{ $exportCardSize === 'punch' ? 'bg-white dark:bg-gray-900 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200' }}">
                            <span>✂️ {{ __('Punch Size (86×54mm)') }}</span>
                        </button>
                        <button type="button" wire:click="$set('exportCardSize', 'bleed')" class="px-3.5 py-1.5 rounded-lg transition cursor-pointer flex items-center gap-1.5 {{ $exportCardSize === 'bleed' ? 'bg-white dark:bg-gray-900 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200' }}">
                            <span>📐 {{ __('Bleed Canvas (90×57mm)') }}</span>
                        </button>
                    </div>
                </div>
                
                <!-- Mirror Print Toggle -->
                <div class="p-4 border border-gray-100 dark:border-gray-700 rounded-2xl flex items-start justify-between gap-4 transition hover:bg-gray-50 dark:hover:bg-gray-700/30">
                    <div>
                        <span class="font-bold text-xs text-gray-900 dark:text-gray-100 flex items-center gap-2">
                            {{ __('Mirror Print (Horizontal Flip)') }}
                            @if ($exportMirrorPrint)
                                <span class="px-2 py-0.5 bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200 rounded text-[9px] font-black uppercase">Active</span>
                            @endif
                        </span>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">
                            {{ __('Horizontally flips cards for printing on transparent PVC, acrylic sheets, or reverse thermal film.') }}
                        </p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer shrink-0 mt-1">
                        <input type="checkbox" wire:model.live="exportMirrorPrint" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-300 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600"></div>
                    </label>
                </div>

                <!-- Mark as Sent for Printing Toggle -->
                <div class="p-4 border border-gray-100 dark:border-gray-700 rounded-2xl flex items-start justify-between gap-4 transition hover:bg-gray-50 dark:hover:bg-gray-700/30">
                    <div>
                        <span class="font-bold text-xs text-gray-900 dark:text-gray-100 flex items-center gap-2">
                            {{ __('Mark Exported Students as "Sent for Printing"') }}
                        </span>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">
                            {{ __('Automatically advances student profile status from "Verified" to "Sent for Printing" to prevent accidental duplicate production runs.') }}
                        </p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer shrink-0 mt-1">
                        <input type="checkbox" wire:model.live="exportSendForPrinting" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-300 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600"></div>
                    </label>
                </div>
            </div>
        </div>

        <!-- RIGHT 1 COLUMN: ORDER SUMMARY & ACTION CTA -->
        <div class="space-y-6 lg:sticky lg:top-6">
            
            <!-- ORDER SUMMARY CARD -->
            <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 shadow-sm border border-gray-100 dark:border-gray-700 space-y-5">
                <h3 class="text-sm font-bold text-gray-900 dark:text-gray-100 pb-3 border-b border-gray-100 dark:border-gray-700">
                    Export Order Summary
                </h3>

                <div class="space-y-3 text-xs">
                    <div class="flex items-center justify-between text-gray-600 dark:text-gray-400">
                        <span>Export Format</span>
                        <span class="font-bold text-gray-900 dark:text-gray-100 uppercase text-[11px]">
                            {{ str_replace('_', ' ', $exportType) }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between text-gray-600 dark:text-gray-400">
                        <span>Card Cut Dimensions</span>
                        <span class="font-bold text-[11px] {{ $exportCardSize === 'punch' ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400' }}">
                            {{ $exportCardSize === 'punch' ? '✂️ Punch Size (86×54mm)' : '📐 Bleed Canvas (90×57mm)' }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between text-gray-600 dark:text-gray-400">
                        <span>Total Target Cards</span>
                        <span class="font-extrabold text-sm text-indigo-600 dark:text-indigo-400 font-mono">
                            {{ $effectiveTargetCount }} cards
                        </span>
                    </div>

                    <div class="flex items-center justify-between text-gray-600 dark:text-gray-400">
                        <span>Credits Required</span>
                        <span class="font-extrabold text-sm font-mono text-gray-900 dark:text-gray-100">
                            {{ $effectiveTargetCount }} credits
                        </span>
                    </div>

                    <div class="flex items-center justify-between text-gray-600 dark:text-gray-400">
                        <span>Current Balance</span>
                        <span class="font-extrabold font-mono text-gray-700 dark:text-gray-300">
                            {{ number_format($activeSchool->credits_balance ?? 0) }}
                        </span>
                    </div>

                    <div class="pt-3 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between font-bold">
                        <span class="text-gray-700 dark:text-gray-300">Balance After Export</span>
                        @php
                            $remaining = ($activeSchool->credits_balance ?? 0) - $effectiveTargetCount;
                        @endphp
                        <span class="font-mono text-base {{ $remaining < 0 ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                            {{ number_format($remaining) }}
                        </span>
                    </div>
                </div>

                <div class="space-y-3 pt-2">
                    <!-- Live Print Preview Trigger Button -->
                    <button wire:click="openPrintPreview" type="button" class="w-full py-3.5 bg-indigo-50 dark:bg-indigo-950/50 hover:bg-indigo-100 dark:hover:bg-indigo-900/60 border border-indigo-200 dark:border-indigo-800 text-indigo-700 dark:text-indigo-300 rounded-2xl font-black text-xs uppercase tracking-wider transition flex items-center justify-center gap-2 cursor-pointer shadow-sm">
                        <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        <span>{{ __('Live Print Preview') }}</span>
                    </button>

                    @if (($activeSchool->credits_balance ?? 0) < $effectiveTargetCount)
                        <div class="p-3.5 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 rounded-2xl text-xs text-rose-800 dark:text-rose-300 space-y-2">
                            <div class="flex items-center gap-1.5 font-bold">
                                <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                <span>Insufficient Wallet Balance</span>
                            </div>
                            <p class="text-[11px]">
                                You need {{ $effectiveTargetCount - ($activeSchool->credits_balance ?? 0) }} more credits to start this export.
                            </p>
                            <a href="{{ route('billing') }}" wire:navigate class="block w-full text-center py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl font-bold uppercase tracking-wider text-[11px] transition shadow">
                                Recharge Credits Now →
                            </a>
                        </div>
                    @else
                        <button wire:click="triggerExport" wire:loading.attr="disabled" type="button" class="w-full py-4 bg-gradient-to-r from-indigo-600 to-indigo-800 hover:from-indigo-700 hover:to-indigo-900 text-white rounded-2xl font-black text-sm uppercase tracking-wider shadow-lg shadow-indigo-500/25 transition-all transform hover:-translate-y-0.5 active:translate-y-0 disabled:opacity-50 cursor-pointer flex items-center justify-center gap-2">
                            <span wire:loading.remove>🚀 START EXPORT ({{ $effectiveTargetCount }} CARDS)</span>
                            <span wire:loading class="flex items-center gap-2">
                                <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                QUEUING EXPORT...
                            </span>
                        </button>
                    @endif
                </div>
            </div>

            <!-- QUICK HELP / PRINT TIPS CARD -->
            <div class="bg-gray-50 dark:bg-gray-900/60 rounded-3xl p-6 border border-gray-100 dark:border-gray-800 space-y-3 text-xs text-gray-500 dark:text-gray-400">
                <h4 class="font-extrabold text-gray-800 dark:text-gray-200 flex items-center gap-1.5">
                    <span>💡 Commercial Printing Tips</span>
                </h4>
                <ul class="space-y-2 list-disc pl-4 text-[11px]">
                    <li><strong>Print Imposition PDF:</strong> Ideal for offset/digital presses. Uses exact 4mm gutter spacing.</li>
                    <li><strong>Rendered JPGs (ZIP):</strong> Great for fast digital proofs, web portals, or custom archiving.</li>
                    <li><strong>Single Card PDF:</strong> Configured for direct CR80 PVC thermal card desktop printers.</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- RECENT EXPORTS SECTION (FULL WIDTH) -->
    <div class="bg-white dark:bg-gray-800 rounded-3xl p-6 sm:p-8 shadow-sm border border-gray-100 dark:border-gray-700 space-y-5">
        <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700/80 pb-4">
            <div class="flex items-center space-x-3">
                <span class="p-2 bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 rounded-xl">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
                <div>
                    <h3 class="text-sm font-bold text-gray-900 dark:text-gray-100">Export & Print History</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Live progress tracking and instant download archives.</p>
                </div>
            </div>

            @if ($userExports->count() > 0)
                <button wire:click="clearAllExports" wire:confirm="Are you sure you want to delete all export history and files?" type="button" class="text-xs font-semibold text-rose-500 hover:text-rose-600 dark:text-rose-400 hover:underline transition cursor-pointer">
                    {{ __('Clear All History') }}
                </button>
            @endif
        </div>

        <div class="space-y-3">
            @forelse ($userExports as $exp)
                @php
                    $totalItems = max(1, (int)($exp->total_items ?? 1));
                    $processedItems = min($totalItems, (int)($exp->processed_items ?? 0));
                    $pct = $exp->status === 'completed' ? 100 : (int)round(($processedItems / $totalItems) * 100);
                @endphp
                <div class="p-4 bg-gray-50 dark:bg-gray-900/60 border border-gray-100 dark:border-gray-700/60 rounded-2xl text-xs space-y-3 transition hover:border-gray-300 dark:hover:border-gray-600">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="flex items-center space-x-3">
                            <div class="p-2.5 bg-white dark:bg-gray-800 rounded-xl shadow-xs border border-gray-100 dark:border-gray-700 text-indigo-600 dark:text-indigo-400">
                                @if($exp->type === 'imposition_pdf')
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                @elseif($exp->type === 'jpg_zip' || $exp->type === 'png_zip')
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                @else
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                @endif
                            </div>
                            <div>
                                <span class="font-extrabold text-gray-900 dark:text-gray-100 uppercase text-xs flex items-center gap-2">
                                    {{ str_replace('_', ' ', $exp->type) }}
                                    @if (is_array($exp->params) && !empty($exp->params['mirror_print']))
                                        <span class="px-1.5 py-0.5 bg-amber-100 dark:bg-amber-950/40 text-amber-700 dark:text-amber-400 rounded text-[9px] font-black">MIRRORED</span>
                                    @endif
                                </span>
                                <span class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5 block">
                                    {{ $exp->created_at->format('M d, Y • H:i:s') }} • Total {{ $exp->total_items ?? 0 }} cards
                                </span>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 self-end sm:self-center">
                            @if ($exp->status === 'completed')
                                <span class="px-3 py-1 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 rounded-xl text-xs font-bold flex items-center gap-1 border border-emerald-200 dark:border-emerald-800">
                                    ✓ COMPLETED
                                </span>
                                <a href="{{ route('exports.download', $exp) }}" target="_blank" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow transition flex items-center gap-1.5 transform hover:-translate-y-0.5">
                                    <span>Download</span>
                                    <span>↓</span>
                                </a>
                            @elseif ($exp->status === 'processing')
                                <span class="px-3 py-1 bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 rounded-xl text-xs font-bold flex items-center gap-1.5 border border-blue-200 dark:border-blue-800 animate-pulse">
                                    <svg class="animate-spin h-3.5 w-3.5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    PROCESSING ({{ $pct }}%)
                                </span>
                            @elseif ($exp->status === 'failed')
                                <span class="px-3 py-1 bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 rounded-xl text-xs font-bold border border-rose-200 dark:border-rose-800">
                                    FAILED
                                </span>
                            @else
                                <span class="px-3 py-1 bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 rounded-xl text-xs font-bold flex items-center gap-1 border border-amber-200 dark:border-amber-800">
                                    QUEUED IN BACKGROUND
                                </span>
                            @endif

                            <button wire:click="deleteExport({{ $exp->id }})" wire:confirm="Are you sure you want to delete this export record?" type="button" class="p-2 text-gray-400 hover:text-rose-600 transition" title="Delete export">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                    </div>

                    @if ($exp->status === 'processing')
                        <div class="w-full bg-gray-200 dark:bg-gray-700 h-2 rounded-full overflow-hidden">
                            <div class="bg-indigo-600 h-full transition-all duration-300 rounded-full" style="width: {{ $pct }}%"></div>
                        </div>
                        <div class="flex justify-between text-[10px] text-gray-400">
                            <span>Processed {{ $processedItems }} of {{ $totalItems }} cards</span>
                            <span>{{ $pct }}%</span>
                        </div>
                    @endif

                    @if ($exp->status === 'failed' && $exp->error_message)
                        <div class="p-3 bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-800/60 rounded-xl text-rose-800 dark:text-rose-300 text-[11px] font-mono whitespace-pre-wrap">
                            {{ Str::limit($exp->error_message, 250) }}
                        </div>
                    @endif
                </div>
            @empty
                <div class="text-center py-12 border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-2xl space-y-2">
                    <span class="text-3xl">📭</span>
                    <h4 class="text-xs font-bold text-gray-700 dark:text-gray-300">No export jobs yet</h4>
                    <p class="text-[11px] text-gray-400">Select your export criteria above and start generating production-ready card packages.</p>
                </div>
            @endforelse
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- LIVE PRINT & IMPOSITION PREVIEW MODAL                                     -->
    <!-- ========================================================================= -->
    @if ($isPreviewModalOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-3 py-6 text-center sm:p-6">
                <div class="fixed inset-0 transition-opacity bg-gray-950/80 backdrop-blur-md" wire:click="closePrintPreview"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

                <div class="inline-block w-full max-w-7xl max-h-[92vh] flex flex-col text-left align-middle transition-all transform bg-white dark:bg-gray-900 rounded-3xl shadow-2xl border border-gray-200 dark:border-gray-700 overflow-hidden sm:my-4">
                    
                    <!-- Modal Top Header -->
                    <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-gray-50/50 dark:bg-gray-950/40">
                        <div class="flex items-center gap-3">
                            <div class="p-2.5 bg-indigo-600 text-white rounded-2xl shadow-sm">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-base font-extrabold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                                    <span>{{ __('Live Print & Sheet Preview') }}</span>
                                    @if ($exportMirrorPrint)
                                        <span class="px-2 py-0.5 bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 rounded-md text-[9px] font-black uppercase">Mirrored</span>
                                    @endif
                                </h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ __('Commercial sheet simulation with exact card slots, gutter spacing, and punch alignment.') }}
                                </p>
                            </div>
                        </div>

                        <!-- Top Mode Selector + Close Button -->
                        <div class="flex items-center gap-3 self-end sm:self-auto">
                            <!-- View Mode Tabs -->
                            <div class="bg-gray-200 dark:bg-gray-800 p-1 rounded-2xl flex items-center gap-1 text-xs font-bold">
                                <button type="button" wire:click="setPreviewViewMode('sheet')" class="px-3 py-1.5 rounded-xl transition cursor-pointer {{ $previewViewMode === 'sheet' ? 'bg-white dark:bg-gray-900 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900' }}">
                                    📑 {{ __('Imposition Sheet') }}
                                </button>
                                <button type="button" wire:click="setPreviewViewMode('card')" class="px-3 py-1.5 rounded-xl transition cursor-pointer {{ $previewViewMode === 'card' ? 'bg-white dark:bg-gray-900 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900' }}">
                                    📇 {{ __('Single ID Card') }}
                                </button>
                            </div>

                            <button type="button" wire:click="closePrintPreview" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-xl transition cursor-pointer">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </div>

                    <!-- Modal Body (Scrollable) -->
                    <div class="p-6 overflow-y-auto max-h-[calc(92vh-140px)] space-y-6 bg-gray-100/60 dark:bg-gray-950/60">

                        @if ($previewViewMode === 'sheet')
                            <!-- IMPOSITION SHEET PREVIEW -->
                            <div class="space-y-4">
                                <!-- Sheet Controls Bar -->
                                <div class="bg-white dark:bg-gray-900 p-4 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
                                    <!-- Front / Back Sheet Toggle -->
                                    <div class="flex items-center gap-2">
                                        <button type="button" wire:click="setPreviewSheetSide('front')" class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer flex items-center gap-1.5 {{ $previewSheetSide === 'front' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200' }}">
                                            <span>🔵 {{ __('Front Sheet') }}</span>
                                        </button>
                                        <button type="button" wire:click="setPreviewSheetSide('back')" class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer flex items-center gap-1.5 {{ $previewSheetSide === 'back' ? 'bg-purple-600 text-white shadow-sm' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200' }}">
                                            <span>🟣 {{ __('Back Sheet (Aligned & Mirrored)') }}</span>
                                        </button>
                                    </div>

                                    <!-- Sheet Metrics Badges -->
                                    <div class="flex items-center gap-2 flex-wrap text-[11px] font-mono font-bold text-gray-600 dark:text-gray-300">
                                        <span class="px-2.5 py-1 bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 rounded-lg border border-amber-200 dark:border-amber-800">
                                            🪪 {{ $exportCardSize === 'punch' ? 'Punch: 86×54mm' : 'Bleed: 90×57mm' }}
                                        </span>
                                        <span class="px-2.5 py-1 bg-gray-100 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                                            📐 {{ $sheetLayout['page_width_mm'] }} × {{ $sheetLayout['page_height_mm'] }} mm
                                        </span>
                                        <span class="px-2.5 py-1 bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 rounded-lg border border-indigo-100 dark:border-indigo-800">
                                            🔲 {{ $sheetLayout['cols'] }} × {{ $sheetLayout['rows'] }} = {{ $sheetLayout['cards_per_page'] }} cards/sheet
                                        </span>
                                        <span class="px-2.5 py-1 bg-purple-50 dark:bg-purple-950/60 text-purple-700 dark:text-purple-300 rounded-lg border border-purple-100 dark:border-purple-800">
                                            ↔ H-Gap: {{ $sheetLayout['horizontal_gutter_mm'] }} mm • ↕ V-Gap: {{ $sheetLayout['vertical_gutter_mm'] }} mm
                                        </span>
                                    </div>

                                    <!-- Sheet Page Navigator -->
                                    <div class="flex items-center gap-2 text-xs font-bold">
                                        <button type="button" wire:click="prevPreviewPage" @if($previewPageIndex <= 0) disabled @endif class="p-1.5 bg-gray-100 dark:bg-gray-800 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-700 disabled:opacity-30 disabled:cursor-not-allowed cursor-pointer transition">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                        </button>
                                        <span class="text-gray-700 dark:text-gray-300 font-mono">
                                            Sheet {{ $previewPageIndex + 1 }} / {{ $previewTotalSheets }}
                                        </span>
                                        <button type="button" wire:click="nextPreviewPage({{ $previewTotalSheets }})" @if($previewPageIndex >= $previewTotalSheets - 1) disabled @endif class="p-1.5 bg-gray-100 dark:bg-gray-800 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-700 disabled:opacity-30 disabled:cursor-not-allowed cursor-pointer transition">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                        </button>
                                    </div>
                                </div>

                                <!-- Sheet Rendering Container -->
                                <div class="bg-gray-900 p-4 sm:p-8 rounded-3xl overflow-x-auto flex justify-center shadow-inner min-h-[500px]">
                                    <!-- Scaled Paper Canvas -->
                                    <div class="bg-white text-gray-900 border-2 border-gray-400/40 shadow-2xl p-6 sm:p-8 rounded-sm relative transition-all"
                                         style="width: min(100%, 960px); aspect-ratio: {{ $sheetLayout['page_width_mm'] }} / {{ $sheetLayout['page_height_mm'] }}; min-height: 480px;">
                                        
                                        <!-- Paper Header Label -->
                                        <div class="absolute top-2 left-3 text-[9px] font-mono font-bold text-gray-400 uppercase tracking-wider pointer-events-none">
                                            {{ strtoupper($exportPageSize) }} SHEET ({{ $previewSheetSide === 'front' ? 'FRONT SIDE' : 'BACK SIDE' }}) • SHEET {{ $previewPageIndex + 1 }}
                                        </div>

                                        <!-- Card Grid -->
                                        <div class="w-full h-full"
                                             style="display: grid; grid-template-columns: repeat({{ $sheetLayout['cols'] }}, 1fr); grid-template-rows: repeat({{ $sheetLayout['rows'] }}, 1fr); column-gap: {{ max(4, round($sheetLayout['horizontal_gutter_mm'] * 2)) }}px; row-gap: {{ max(4, round($sheetLayout['vertical_gutter_mm'] * 2)) }}px;">
                                            @foreach ($pageCards as $cardSlot)
                                                @php
                                                    $slotStd = $cardSlot['student'];
                                                    $slotTpl = $cardSlot['template'];
                                                @endphp
                                                <div class="border border-dashed border-gray-300 rounded-lg p-1.5 flex flex-col justify-between bg-gray-50/70 relative overflow-hidden group shadow-sm transition hover:border-indigo-400">
                                                    @if ($slotStd && $slotTpl)
                                                        <!-- Card Content Simulation -->
                                                        <div class="w-full h-full flex flex-col justify-between p-2 bg-gradient-to-br from-white to-gray-50 rounded border border-gray-200 text-[10px] relative {{ $exportMirrorPrint ? 'scale-x-[-1]' : '' }}">
                                                            <div class="flex items-center justify-between border-b border-gray-100 pb-1">
                                                                <span class="font-extrabold text-indigo-700 truncate max-w-[120px]">{{ $activeSchool->name ?? 'School' }}</span>
                                                                <span class="text-[8px] font-mono text-gray-400">#{{ $cardSlot['slot_index'] + 1 }}</span>
                                                            </div>
                                                            <div class="flex items-center gap-2 my-1">
                                                                @if ($slotStd->photo_path)
                                                                    <img src="{{ asset('storage/' . $slotStd->photo_path) }}" class="w-8 h-10 object-cover rounded border border-gray-300 shadow-xs shrink-0" />
                                                                @else
                                                                    <div class="w-8 h-10 bg-indigo-100 text-indigo-700 font-extrabold rounded flex items-center justify-center text-[10px] shrink-0">
                                                                        {{ substr($slotStd->first_name, 0, 1) }}
                                                                    </div>
                                                                @endif
                                                                <div class="truncate text-[9px]">
                                                                    <p class="font-black text-gray-900 truncate leading-tight">{{ $slotStd->full_name }}</p>
                                                                    <p class="text-gray-500 mt-0.5">Gr: {{ $slotStd->campaignStudents->first()?->grade?->name ?? 'N/A' }}</p>
                                                                    <p class="text-gray-500">Roll: {{ $slotStd->campaignStudents->first()?->roll_no ?? '-' }}</p>
                                                                </div>
                                                            </div>
                                                            <div class="flex items-center justify-between pt-1 border-t border-gray-100 text-[8px] text-gray-400 font-mono">
                                                                <span>CR80 PVC</span>
                                                                <span>{{ $previewSheetSide === 'front' ? 'FRONT' : 'BACK' }}</span>
                                                            </div>
                                                        </div>
                                                    @else
                                                        <!-- Empty Grid Slot -->
                                                        <div class="w-full h-full flex flex-col items-center justify-center text-gray-300 text-[9px] font-mono">
                                                            <span>[ EMPTY SLOT ]</span>
                                                            <span>#{{ $cardSlot['slot_index'] + 1 }}</span>
                                                        </div>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @else
                            <!-- SINGLE FINISHED ID CARD VIEW -->
                            @php
                                $totalSampleStudents = $previewStudentsList->count();
                                $currentPreviewStudent = $totalSampleStudents > 0 ? $previewStudentsList->get(min($previewStudentIndex, $totalSampleStudents - 1)) : null;
                                $studentEnrollment = $currentPreviewStudent?->campaignStudents?->first();
                                $currentStudentTemplate = $currentPreviewStudent ? app(\App\Services\TemplateResolverService::class)->getEffectiveTemplate($schoolId, $studentEnrollment?->grade_id) : $sampleTemplate;
                            @endphp

                            <div class="space-y-6">
                                <!-- Student Navigation & Info Bar -->
                                <div class="bg-white dark:bg-gray-900 p-4 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                    <div class="flex items-center gap-3">
                                        @if ($currentPreviewStudent)
                                            <div>
                                                <h4 class="text-sm font-extrabold text-gray-900 dark:text-gray-100">
                                                    {{ $currentPreviewStudent->full_name }}
                                                </h4>
                                                <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">
                                                    Grade: {{ $studentEnrollment?->grade?->name ?? 'N/A' }} • Div: {{ $studentEnrollment?->division?->name ?? 'N/A' }} • Roll: {{ $studentEnrollment?->roll_no ?? '-' }}
                                                </p>
                                            </div>
                                        @else
                                            <p class="text-xs text-gray-400">Sample Template Preview Mode</p>
                                        @endif
                                    </div>

                                    @if ($totalSampleStudents > 0)
                                        <div class="flex items-center gap-2 text-xs font-bold">
                                            <button type="button" wire:click="prevPreviewStudent" @if($previewStudentIndex <= 0) disabled @endif class="p-1.5 bg-gray-100 dark:bg-gray-800 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-700 disabled:opacity-30 disabled:cursor-not-allowed cursor-pointer transition">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                            </button>
                                            <span class="text-gray-700 dark:text-gray-300 font-mono">
                                                Student {{ $previewStudentIndex + 1 }} / {{ $totalSampleStudents }}
                                            </span>
                                            <button type="button" wire:click="nextPreviewStudent({{ $totalSampleStudents }})" @if($previewStudentIndex >= $totalSampleStudents - 1) disabled @endif class="p-1.5 bg-gray-100 dark:bg-gray-800 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-700 disabled:opacity-30 disabled:cursor-not-allowed cursor-pointer transition">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                            </button>
                                        </div>
                                    @endif
                                </div>

                                <!-- Cards Display Container -->
                                <div class="bg-gray-900 p-8 rounded-3xl flex flex-col md:flex-row items-center justify-center gap-8 shadow-inner overflow-x-auto">
                                    <!-- Front Card Display -->
                                    <div class="space-y-2 flex flex-col items-center">
                                        <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Front Card</span>
                                        <div class="relative p-2 bg-gray-800/80 rounded-2xl border border-gray-700 shadow-2xl flex items-center justify-center">
                                            @if ($currentStudentTemplate)
                                                <div class="relative overflow-hidden rounded-xl">
                                                    <x-id-card-renderer 
                                                        :template="$currentStudentTemplate" 
                                                        :student="$currentPreviewStudent" 
                                                        :school="$activeSchool" 
                                                        :scale="0.5" 
                                                        :previewMode="!$currentPreviewStudent" 
                                                        :forExport="true" 
                                                        :isMirrored="$exportMirrorPrint" 
                                                        :cardSize="$exportCardSize" />
                                                </div>
                                            @else
                                                <div class="w-64 h-40 bg-gray-800 text-gray-400 rounded-xl flex items-center justify-center text-xs">
                                                    No Template Assigned
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                    </div>

                    <!-- Modal Footer -->
                    <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-800 flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-gray-50/50 dark:bg-gray-950/40">
                        <div class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-2">
                            <span>💳 Required Credits: <strong class="text-indigo-600 dark:text-indigo-400">{{ $effectiveTargetCount }}</strong></span>
                            <span>•</span>
                            <span>School Balance: <strong class="text-gray-800 dark:text-gray-200">{{ number_format($activeSchool->credits_balance ?? 0) }}</strong></span>
                        </div>

                        <div class="flex items-center gap-3">
                            <button type="button" wire:click="closePrintPreview" class="px-4 py-2 bg-gray-200 dark:bg-gray-800 hover:bg-gray-300 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl text-xs font-bold transition cursor-pointer">
                                {{ __('Close Preview') }}
                            </button>

                            <button wire:click="triggerExport" wire:loading.attr="disabled" type="button" class="px-6 py-2 bg-gradient-to-r from-indigo-600 to-indigo-800 hover:from-indigo-700 hover:to-indigo-900 text-white rounded-xl font-bold text-xs uppercase tracking-wider shadow-md transition cursor-pointer flex items-center gap-2">
                                <span>🚀 {{ __('Start Export Now') }}</span>
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    @endif
</div>
