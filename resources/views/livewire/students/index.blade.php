<?php

use App\Models\Student;
use App\Models\Campaign;
use App\Models\Grade;
use App\Models\Division;
use App\Models\CampaignStudent;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;

new class extends Component
{
    use WithFileUploads;

    // Bulk upload fields
    public bool $isBulkModalOpen = false;
    public $bulkCsvs = [];
    public $bulkZip = null;

    // Filter fields
    public string $search = '';
    public $filterCampaign = '';
    public $filterGrade = '';
    public $filterDivision = '';

    public function mount()
    {
        $this->search = session('students_filter_search', '');
        $this->filterCampaign = session('students_filter_campaign', '');
        $this->filterGrade = session('students_filter_grade', '');
        $this->filterDivision = session('students_filter_division', '');
        $this->viewMode = session('students_view_mode', 'auto');
    }

    // Form fields
    public $studentId = null;
    public string $first_name = '';
    public string $middle_name = '';
    public string $last_name = '';
    public string $roll_no = '';
    public string $serial_number = '';
    public $campaignId = '';
    public $gradeId = '';
    public $divisionId = '';
    public string $blood_group = '';
    public string $gender = '';
    public ?string $dob = null;
    public string $address = '';
    public string $pincode = '';
    public string $contact_number = '';
    public $photo = null;
    public ?string $currentPhotoPath = null;

    // Modal state
    public bool $isModalOpen = false;
    public bool $isConfirmDeleteOpen = false;
    public $studentToDeleteId = null;

    // View Mode & Preview ID Card State
    public string $viewMode = 'auto'; // 'auto', 'list', 'template'
    public bool $isPreviewIdCardOpen = false;
    public $previewStudentId = null;


    // Export Modal State
    public bool $isExportModalOpen = false;

    public string $exportType = 'excel_photo_zip'; // 'excel_photo_zip', 'png_zip', 'imposition_pdf'
    public string $exportPageSize = 'A4';
    public float $exportBleedMm = 3.0;
    public float $exportMarginMm = 3.0;
    public float $exportGutterMm = 6.0;
    public float $exportCustomWidthMm = 210.0;
    public float $exportCustomHeightMm = 297.0;

    public function openExportModal()
    {
        $this->isExportModalOpen = true;

        $lastPdfExport = \App\Models\Export::where('school_id', session('active_school_id'))
            ->where('user_id', auth()->id())
            ->where('type', 'imposition_pdf')
            ->orderBy('id', 'desc')
            ->first();

        if ($lastPdfExport && is_array($lastPdfExport->params)) {
            $p = $lastPdfExport->params;
            if (!empty($p['page_size'])) $this->exportPageSize = $p['page_size'];
            if (isset($p['custom_width_mm'])) $this->exportCustomWidthMm = (float)$p['custom_width_mm'];
            if (isset($p['custom_height_mm'])) $this->exportCustomHeightMm = (float)$p['custom_height_mm'];
            if (isset($p['bleed_mm'])) $this->exportBleedMm = (float)$p['bleed_mm'];
            if (isset($p['margin_mm'])) $this->exportMarginMm = (float)$p['margin_mm'];
            if (isset($p['gutter_mm'])) $this->exportGutterMm = (float)$p['gutter_mm'];
        }
    }

    public function closeExportModal()
    {
        $this->isExportModalOpen = false;
    }

    public function triggerExport()
    {
        $activeSchoolId = session('active_school_id');
        if (!$activeSchoolId) return;

        $user = auth()->user();

        $query = Student::query();
        $scopes = $this->getPermittedScopes();
        if ($scopes['restricted']) {
            $query->whereHas('campaignStudents', function($q) use ($scopes) {
                $q->whereIn('grade_id', $scopes['grades'])
                  ->whereIn('division_id', $scopes['divisions']);
            });
        }

        $query->whereHas('campaignStudents.campaign', function($q) use ($activeSchoolId) {
            $q->where('school_id', $activeSchoolId);
            if ($this->filterCampaign) {
                $q->where('id', $this->filterCampaign);
            }
        });

        if ($this->filterGrade || $this->filterDivision) {
            $query->whereHas('campaignStudents', function($q) {
                if ($this->filterGrade) {
                    $q->where('grade_id', $this->filterGrade);
                }
                if ($this->filterDivision) {
                    $q->where('division_id', $this->filterDivision);
                }
            });
        }

        if (!empty(trim($this->search))) {
            $s = '%' . trim($this->search) . '%';
            $query->where(function($q) use ($s) {
                $q->where('first_name', 'like', $s)
                  ->orWhere('middle_name', 'like', $s)
                  ->orWhere('last_name', 'like', $s)
                  ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", [$s])
                  ->orWhereRaw("CONCAT(first_name, ' ', middle_name, ' ', last_name) LIKE ?", [$s])
                  ->orWhere('contact_number', 'like', $s)
                  ->orWhere('address', 'like', $s)
                  ->orWhere('pincode', 'like', $s)
                  ->orWhereHas('campaignStudents', function($csQ) use ($s) {
                      $csQ->where('roll_no', 'like', $s)
                          ->orWhere('serial_number', 'like', $s);
                  });
            });
        }

        $targetStudentIds = $query->pluck('id')->all();

        if (empty($targetStudentIds)) {
            session()->flash('message', 'No eligible students found to export for active filter selection.');
            return;
        }

        $params = [
            'campaign_id' => $this->filterCampaign ?: null,
            'student_ids' => $targetStudentIds,
            'page_size' => $this->exportPageSize,
            'custom_width_mm' => $this->exportCustomWidthMm,
            'custom_height_mm' => $this->exportCustomHeightMm,
            'bleed_mm' => $this->exportBleedMm,
            'margin_mm' => $this->exportMarginMm,
            'gutter_mm' => $this->exportGutterMm,
        ];

        $export = \App\Models\Export::create([
            'user_id' => $user->id,
            'school_id' => $activeSchoolId,
            'type' => $this->exportType,
            'status' => 'processing',
            'params' => $params,
            'total_items' => count($targetStudentIds),
            'processed_items' => 0,
        ]);

        try {
            match ($this->exportType) {
                'excel_photo_zip' => \App\Jobs\ExportExcelPhotoZipJob::dispatchSync($export->id),
                'png_zip' => \App\Jobs\ExportPngZipJob::dispatchSync($export->id),
                'single_card_pdf' => \App\Jobs\ExportSingleCardPdfJob::dispatchSync($export->id),
                'imposition_pdf' => \App\Jobs\ExportImpositionPdfJob::dispatchSync($export->id),
            };
            session()->flash('message', 'Export completed successfully! Click Download to save file.');
        } catch (\Throwable $e) {
            $export->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
            session()->flash('message', 'Export failed: ' . $e->getMessage());
        }
    }

    public function deleteExport($exportId)
    {
        $export = \App\Models\Export::where('school_id', session('active_school_id'))
            ->where('user_id', auth()->id())
            ->where('id', $exportId)
            ->first();

        if ($export) {
            if ($export->file_path) {
                if (\Illuminate\Support\Facades\Storage::disk('local')->exists($export->file_path)) {
                    \Illuminate\Support\Facades\Storage::disk('local')->delete($export->file_path);
                }
                $fullPath = storage_path('app/private/' . $export->file_path);
                if (file_exists($fullPath)) {
                    @unlink($fullPath);
                }
            }
            $export->delete();
            session()->flash('message', 'Export deleted successfully.');
        }
    }

    public function clearAllExports()
    {
        $exports = \App\Models\Export::where('school_id', session('active_school_id'))
            ->where('user_id', auth()->id())
            ->get();

        foreach ($exports as $export) {
            if ($export->file_path) {
                if (\Illuminate\Support\Facades\Storage::disk('local')->exists($export->file_path)) {
                    \Illuminate\Support\Facades\Storage::disk('local')->delete($export->file_path);
                }
                $fullPath = storage_path('app/private/' . $export->file_path);
                if (file_exists($fullPath)) {
                    @unlink($fullPath);
                }
            }
            $export->delete();
        }
        session()->flash('message', 'All export records cleared.');
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


    public function getEffectiveTemplate($student = null)
    {
        $activeSchoolId = session('active_school_id');
        
        // 1. Check if filterGrade is explicitly selected
        if ($this->filterGrade) {
            $grade = \App\Models\Grade::find($this->filterGrade);
            if ($grade) {
                if ($grade->school_template_id && ($st = \App\Models\SchoolTemplate::find($grade->school_template_id))) {
                    return $st;
                }
                if ($grade->template_id && ($mt = \App\Models\Template::find($grade->template_id))) {
                    return $mt;
                }
            }
        }

        // 2. Check student's individual grade if passed
        if ($student) {
            $enrollment = is_object($student) ? ($student->campaignStudents ? $student->campaignStudents->first() : null) : null;
            if ($enrollment && $enrollment->grade) {
                $g = $enrollment->grade;
                if ($g->school_template_id && ($st = \App\Models\SchoolTemplate::find($g->school_template_id))) {
                    return $st;
                }
                if ($g->template_id && ($mt = \App\Models\Template::find($g->template_id))) {
                    return $mt;
                }
            }
        }

        // 3. Check active school default template
        if ($activeSchoolId) {
            $school = \App\Models\School::find($activeSchoolId);
            if ($school) {
                if ($school->school_template_id && ($st = \App\Models\SchoolTemplate::find($school->school_template_id))) {
                    return $st;
                }
                if ($school->template_id && ($mt = \App\Models\Template::find($school->template_id))) {
                    return $mt;
                }
                $defaultSt = \App\Models\SchoolTemplate::where('school_id', $activeSchoolId)->where('is_default', true)->first();
                if ($defaultSt) return $defaultSt;
            }
        }

        return null;
    }

    // Pagination properties
    public $perPage = 12;
    public bool $hasMore = false;

    public function loadMore()
    {
        $this->perPage += 12;
    }

    public function updatedSearch()
    {
        $this->perPage = 12;
        session(['students_filter_search' => $this->search]);
    }

    public function updatedFilterCampaign()
    {
        $this->perPage = 12;
        session(['students_filter_campaign' => $this->filterCampaign]);
    }

    public function updatedFilterGrade()
    {
        $this->filterDivision = '';
        $this->perPage = 12;
        session([
            'students_filter_grade' => $this->filterGrade,
            'students_filter_division' => '',
        ]);
    }

    public function updatedFilterDivision()
    {
        $this->perPage = 12;
        session(['students_filter_division' => $this->filterDivision]);
    }

    public function updatedViewMode()
    {
        session(['students_view_mode' => $this->viewMode]);
    }

    public function updatedFilterBloodGroup()
    {
        $this->perPage = 12;
    }

    public function getPermittedScopes()
    {
        $user = auth()->user();
        $activeSchoolId = session('active_school_id');
        if (!$activeSchoolId || !$user) {
            return ['restricted' => true, 'grades' => [], 'divisions' => []];
        }

        $isSaasAdmin = $user->hasRole('saas_admin');
        $isSchoolAdmin = \App\Models\SchoolUserRole::where('user_id', $user->id)
            ->where('school_id', $activeSchoolId)
            ->whereHas('role', function($q) { $q->where('slug', 'school_admin'); })
            ->exists();

        if ($isSaasAdmin || $isSchoolAdmin) {
            return [
                'restricted' => false,
                'grades' => [],
                'divisions' => []
            ];
        }

        $teacherRole = \App\Models\SchoolUserRole::where('user_id', $user->id)
            ->where('school_id', $activeSchoolId)
            ->whereHas('role', function($q) { $q->where('slug', 'teacher'); })
            ->first();

        if (!$teacherRole) {
            return [
                'restricted' => true,
                'grades' => [],
                'divisions' => []
            ];
        }

        $divisionIds = $teacherRole->assignments()->pluck('division_id')->toArray();
        $gradeIds = $teacherRole->assignments()->pluck('grade_id')->toArray();

        if ($teacherRole->division_id) {
            $divisionIds[] = $teacherRole->division_id;
        }
        if ($teacherRole->grade_id) {
            $gradeIds[] = $teacherRole->grade_id;
        }

        return [
            'restricted' => true,
            'grades' => array_unique($gradeIds),
            'divisions' => array_unique($divisionIds)
        ];
    }

    public function loadStudents()
    {
        $activeSchoolId = session('active_school_id');
        if (!$activeSchoolId) {
            $this->hasMore = false;
            return [];
        }

        $query = Student::query();

        $scopes = $this->getPermittedScopes();
        if ($scopes['restricted']) {
            $query->whereHas('campaignStudents', function($q) use ($scopes) {
                $q->whereIn('grade_id', $scopes['grades'])
                  ->whereIn('division_id', $scopes['divisions']);
            });
        }

        // Join campaign students for active school filtering & selection
        $query->whereHas('campaignStudents.campaign', function($q) use ($activeSchoolId) {
            $q->where('school_id', $activeSchoolId);
            if ($this->filterCampaign) {
                $q->where('id', $this->filterCampaign);
            }
        });

        if ($this->filterGrade || $this->filterDivision) {
            $query->whereHas('campaignStudents', function($q) {
                if ($this->filterGrade) {
                    $q->where('grade_id', $this->filterGrade);
                }
                if ($this->filterDivision) {
                    $q->where('division_id', $this->filterDivision);
                }
            });
        }

        if (!empty(trim($this->search))) {
            $s = '%' . trim($this->search) . '%';
            $query->where(function($q) use ($s) {
                $q->where('first_name', 'like', $s)
                  ->orWhere('middle_name', 'like', $s)
                  ->orWhere('last_name', 'like', $s)
                  ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", [$s])
                  ->orWhereRaw("CONCAT(first_name, ' ', middle_name, ' ', last_name) LIKE ?", [$s])
                  ->orWhere('contact_number', 'like', $s)
                  ->orWhere('address', 'like', $s)
                  ->orWhere('pincode', 'like', $s)
                  ->orWhereHas('campaignStudents', function($csQ) use ($s) {
                      $csQ->where('roll_no', 'like', $s)
                          ->orWhere('serial_number', 'like', $s);
                  });
            });
        }

        $totalCount = $query->count();
        $this->hasMore = $totalCount > $this->perPage;

        return $query->with(['campaignStudents' => function($q) use ($activeSchoolId) {
            $q->whereHas('campaign', function($inner) use ($activeSchoolId) {
                $inner->where('school_id', $activeSchoolId);
            })->with(['grade', 'division', 'campaign']);
        }])->orderBy('created_at', 'desc')->take($this->perPage)->get()->all();
    }

    public function getStudentCounts()
    {
        $activeSchoolId = session('active_school_id');
        if (!$activeSchoolId) {
            return ['total' => 0, 'filtered' => 0, 'is_filtered' => false];
        }

        $scopes = $this->getPermittedScopes();

        $buildBaseQuery = function() use ($activeSchoolId, $scopes) {
            $q = Student::query();
            if ($scopes['restricted']) {
                $q->whereHas('campaignStudents', function($cs) use ($scopes) {
                    $cs->whereIn('grade_id', $scopes['grades'])
                       ->whereIn('division_id', $scopes['divisions']);
                });
            }
            $q->whereHas('campaignStudents.campaign', function($c) use ($activeSchoolId) {
                $c->where('school_id', $activeSchoolId);
            });
            return $q;
        };

        $totalCount = $buildBaseQuery()->count();

        $filteredQuery = $buildBaseQuery();

        if ($this->filterCampaign) {
            $filteredQuery->whereHas('campaignStudents', function($q) {
                $q->where('campaign_id', $this->filterCampaign);
            });
        }

        if ($this->filterGrade) {
            $filteredQuery->whereHas('campaignStudents', function($q) {
                $q->where('grade_id', $this->filterGrade);
            });
        }

        if ($this->filterDivision) {
            $filteredQuery->whereHas('campaignStudents', function($q) {
                $q->where('division_id', $this->filterDivision);
            });
        }

        if (!empty(trim($this->search))) {
            $s = '%' . trim($this->search) . '%';
            $filteredQuery->where(function($q) use ($s) {
                $q->where('first_name', 'like', $s)
                  ->orWhere('middle_name', 'like', $s)
                  ->orWhere('last_name', 'like', $s)
                  ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", [$s])
                  ->orWhereRaw("CONCAT(first_name, ' ', middle_name, ' ', last_name) LIKE ?", [$s])
                  ->orWhere('contact_number', 'like', $s)
                  ->orWhere('address', 'like', $s)
                  ->orWhere('pincode', 'like', $s)
                  ->orWhereHas('campaignStudents', function($csQ) use ($s) {
                      $csQ->where('roll_no', 'like', $s)
                          ->orWhere('serial_number', 'like', $s);
                  });
            });
        }

        $filteredCount = $filteredQuery->count();
        $isFiltered = !empty($this->filterCampaign) || !empty($this->filterGrade) || !empty($this->filterDivision) || !empty(trim($this->search));

        return [
            'total' => $totalCount,
            'filtered' => $filteredCount,
            'is_filtered' => $isFiltered
        ];
    }

    public function resetFilters()
    {
        $this->reset(['filterCampaign', 'filterGrade', 'filterDivision', 'search']);
        session()->forget([
            'students_filter_search',
            'students_filter_campaign',
            'students_filter_grade',
            'students_filter_division',
        ]);
    }



    public function updatedGradeId($value)
    {
        $this->divisionId = '';
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
        $this->blood_group = $student->blood_group ?? '';
        $this->gender = $student->gender ?? '';
        $this->dob = $student->dob ?? '';
        $this->address = $student->address;
        $this->pincode = $student->pincode;
        $this->contact_number = $student->contact_number;
        $this->currentPhotoPath = $student->photo_path;

        // Load enrollment details for this school
        $activeSchoolId = session('active_school_id');
        $enrollment = \App\Models\CampaignStudent::where('student_id', $student->id)
            ->whereHas('campaign', function($q) use ($activeSchoolId) {
                $q->where('school_id', $activeSchoolId);
            })->first();

        if ($enrollment) {
            $this->campaignId = $enrollment->campaign_id;
            $this->gradeId = $enrollment->grade_id;
            $this->divisionId = $enrollment->division_id;
            $this->roll_no = $enrollment->roll_no ?? '';
            $this->serial_number = $enrollment->serial_number ?? '';

            $scopes = $this->getPermittedScopes();
            if ($scopes['restricted']) {
                if (!in_array($this->gradeId, $scopes['grades']) || !in_array($this->divisionId, $scopes['divisions'])) {
                    abort(403, 'You do not have permission to edit this student.');
                }
            }
        }

        $this->isModalOpen = true;
    }

    public $activeStep = 1;

    public function setStep($step)
    {
        $this->activeStep = $step;
    }

    public function nextStep()
    {
        if ($this->activeStep === 1) {
            if (empty($this->contact_number)) {
                $this->addError('contact_number', 'Contact number is required.');
                return;
            }
        } elseif ($this->activeStep === 2) {
            $this->validate([
                'first_name' => ['required', 'string', 'max:255'],
                'last_name' => ['required', 'string', 'max:255'],
                'address' => ['required', 'string'],
                'pincode' => ['required', 'string', 'max:20'],
            ]);
        } elseif ($this->activeStep === 3) {
            $this->validate([
                'campaignId' => ['required', 'exists:campaigns,id'],
                'gradeId' => ['required', 'exists:grades,id'],
                'divisionId' => ['required', 'exists:divisions,id'],
            ]);

            if ($this->isAlreadyEnrolled) {
                $this->addError('campaignId', 'This student is already enrolled in the selected campaign.');
                return;
            }
        }

        if ($this->activeStep < 4) {
            $this->activeStep++;
        }
    }

    public function prevStep()
    {
        if ($this->activeStep > 1) {
            $this->activeStep--;
        }
    }

    public function getIsAlreadyEnrolledProperty()
    {
        if (!$this->campaignId) {
            return false;
        }

        // When editing an existing student ($this->studentId is present), they are ALREADY enrolled in the campaign.
        // Editing their class/division/details is allowed and should not trigger duplicate enrollment error.
        if ($this->studentId) {
            return false;
        }

        $targetStudentId = null;
        if (!empty($this->contact_number) && !empty($this->first_name) && !empty($this->last_name)) {
            $matched = Student::findExisting($this->contact_number, $this->first_name, $this->last_name, $this->dob);
            if ($matched) {
                $targetStudentId = $matched->id;
            }
        }

        if (!$targetStudentId) {
            return false;
        }

        return \App\Models\CampaignStudent::where('campaign_id', $this->campaignId)
            ->where('student_id', $targetStudentId)
            ->exists();
    }

    public function getMatchingStudentsProperty()
    {
        if (empty($this->contact_number)) {
            return collect();
        }
        $normalized = \App\Support\PhoneNumber::normalize($this->contact_number);
        if (!$normalized || strlen($normalized) < 10) {
            return collect();
        }

        return Student::query()
            ->get()
            ->filter(fn ($s) => \App\Support\PhoneNumber::normalize($s->contact_number) === $normalized)
            ->values();
    }

    public function selectExistingStudent($id)
    {
        $student = Student::findOrFail($id);
        $this->studentId = $student->id;
        $this->first_name = $student->first_name;
        $this->middle_name = $student->middle_name ?? '';
        $this->last_name = $student->last_name;
        $this->blood_group = $student->blood_group ?? '';
        $this->gender = $student->gender ?? '';
        $this->dob = $student->dob ?? '';
        $this->address = $student->address;
        $this->pincode = $student->pincode;
        $this->contact_number = $student->contact_number;
        $this->currentPhotoPath = $student->photo_path;
        $this->activeStep = 2; // Advance to Student Details step
    }

    public function resetForm()
    {
        $this->activeStep = 1;
        $this->studentId = null;
        $this->first_name = '';
        $this->middle_name = '';
        $this->last_name = '';
        $this->roll_no = '';
        $this->serial_number = '';
        $this->campaignId = '';
        $this->gradeId = '';
        $this->divisionId = '';
        $this->blood_group = '';
        $this->gender = '';
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
            'roll_no' => ['nullable', 'string', 'max:100'],
            'serial_number' => ['nullable', 'string', 'max:100'],
            'campaignId' => ['required', 'exists:campaigns,id'],
            'gradeId' => ['required', 'exists:grades,id'],
            'divisionId' => ['required', 'exists:divisions,id'],
            'blood_group' => ['nullable', 'string', 'max:10'],
            'gender' => ['nullable', 'string', 'max:50'],
            'dob' => ['nullable', 'date'],
            'address' => ['required', 'string'],
            'pincode' => ['required', 'string', 'max:20'],
            'contact_number' => ['required', 'string', 'max:20'],
            'photo' => ['nullable', 'image', 'max:4096'], // Max 4MB
        ];

        $validated = $this->validate($rules);

        if ($this->isAlreadyEnrolled && !$this->studentId) {
            $this->addError('campaignId', 'This student is already enrolled in the selected campaign. Double enrollment is not allowed.');
            $this->activeStep = 3;
            return;
        }

        $scopes = $this->getPermittedScopes();
        if ($scopes['restricted']) {
            if (!in_array($this->gradeId, $scopes['grades']) || !in_array($this->divisionId, $scopes['divisions'])) {
                $this->addError('divisionId', 'You do not have permission to assign students to this grade/division.');
                return;
            }
        }

        $photoPath = $this->currentPhotoPath;
        if ($this->photo) {
            if ($this->currentPhotoPath && Storage::disk('public')->exists($this->currentPhotoPath)) {
                Storage::disk('public')->delete($this->currentPhotoPath);
            }
            $photoPath = $this->photo->store('photos', 'public');
        }

        $studentData = [
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name ?: null,
            'last_name' => $this->last_name,
            'blood_group' => $this->blood_group ?: null,
            'gender' => $this->gender ?: null,
            'dob' => $this->dob ?: null,
            'address' => $this->address,
            'pincode' => $this->pincode,
            'contact_number' => $this->contact_number,
            'photo_path' => $photoPath,
        ];

        $matchedExisting = false;

        if ($this->studentId) {
            $student = Student::findOrFail($this->studentId);
            $student->update($studentData);
        } else {
            $student = Student::findExisting($this->contact_number, $this->first_name, $this->last_name, $this->dob);

            if ($student) {
                $matchedExisting = true;
                // Matched an existing student — do not overwrite their data.
                if ($photoPath && Storage::disk('public')->exists($photoPath)) {
                    Storage::disk('public')->delete($photoPath);
                }
            } else {
                $student = Student::create($studentData);
            }
        }

        $student->attemptParentLink();

        // Sync Campaign Student enrollment
        \App\Models\CampaignStudent::updateOrCreate(
            [
                'campaign_id' => $this->campaignId,
                'student_id' => $student->id,
            ],
            [
                'grade_id' => $this->gradeId,
                'division_id' => $this->divisionId,
                'roll_no' => $this->roll_no ?: null,
                'serial_number' => $this->serial_number ?: null,
            ]
        );

        $this->isModalOpen = false;
        $this->resetForm();

        if ($this->studentId) {
            session()->flash('message', 'Student updated successfully.');
        } elseif ($matchedExisting) {
            session()->flash('message', 'Matched an existing student profile — enrolled in this campaign.');
        } else {
            session()->flash('message', 'Student created successfully.');
        }
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

            $activeSchoolId = session('active_school_id');
            $query = \App\Models\CampaignStudent::where('student_id', $student->id);

            if ($this->filterCampaign) {
                $query->where('campaign_id', $this->filterCampaign);
            } else if ($activeSchoolId) {
                $query->whereHas('campaign', function($q) use ($activeSchoolId) {
                    $q->where('school_id', $activeSchoolId);
                });
            }

            $enrollments = $query->get();

            if ($enrollments->isNotEmpty()) {
                $scopes = $this->getPermittedScopes();
                if ($scopes['restricted']) {
                    foreach ($enrollments as $enrollment) {
                        if (!in_array($enrollment->grade_id, $scopes['grades']) || !in_array($enrollment->division_id, $scopes['divisions'])) {
                            abort(403, 'You do not have permission to remove this student from campaign.');
                        }
                    }
                }

                foreach ($enrollments as $enrollment) {
                    $enrollment->delete();
                }
                session()->flash('message', 'Student removed from campaign successfully.');
            }
        }
        $this->isConfirmDeleteOpen = false;
    }

    // --- Bulk Import Methods ---
    public function openBulkModal()
    {
        $this->resetValidation();
        $this->reset(['bulkCsvs', 'bulkZip']);
        $this->isBulkModalOpen = true;
    }

    public function downloadSampleCsv()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="students_import_sample.csv"',
        ];

        $columns = [
            'first_name',
            'middle_name',
            'last_name',
            'roll_no',
            'serial_number',
            'dob',
            'gender',
            'blood_group',
            'contact_number',
            'address',
            'pincode',
            'campaign_name',
            'grade_name',
            'division_name',
            'photo_filename'
        ];

        $sampleRow = [
            'Aarav',
            'Kumar',
            'Sharma',
            '101',
            'REF-2026-001',
            '2015-05-15',
            'Male',
            'O+',
            '9876543210',
            '123 Main Street, Sector 4',
            '400001',
            'Annual ID Card Campaign 2026',
            'Class 5',
            'A',
            'aarav_sharma.jpg'
        ];

        $callback = function () use ($columns, $sampleRow) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            fputcsv($file, $sampleRow);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function importBulkStudents()
    {
        if (! auth()->user()->hasAnyRole(['saas_admin', 'school_admin', 'teacher'])) {
            abort(403);
        }

        $activeSchoolId = session('active_school_id');
        if (!$activeSchoolId) {
            $this->addError('bulkCsvs', 'Please select a school first.');
            return;
        }

        $rules = [
            'bulkCsvs' => ['required', 'array', 'min:1'],
            'bulkCsvs.*' => ['file', 'mimes:csv,txt', 'max:2048'], // CSV max 2MB
            'bulkZip' => ['nullable', 'file', 'mimes:zip', 'max:51200'], // ZIP max 50MB
        ];

        $this->validate($rules);

        // Temporary directory to extract ZIP images if present
        $extractedPath = null;
        if ($this->bulkZip) {
            $zip = new \ZipArchive();
            if ($zip->open($this->bulkZip->getRealPath()) === true) {
                $extractedPath = storage_path('app/temp_zip_' . uniqid());
                if (!file_exists($extractedPath)) {
                    mkdir($extractedPath, 0755, true);
                }
                $zip->extractTo($extractedPath);
                $zip->close();
            } else {
                $this->addError('bulkZip', 'Unable to open or extract ZIP file.');
                return;
            }
        }

        $insertedCount = 0;
        $matchedCount = 0;
        $skippedCount = 0;
        $errorCount = 0;
        $errorsLog = [];

        foreach ($this->bulkCsvs as $bulkCsv) {
            $csvPath = $bulkCsv->getRealPath();
            $fileName = $bulkCsv->getClientOriginalName();

            if (($handle = fopen($csvPath, 'r')) !== false) {
                // Read headers
                $headers = fgetcsv($handle, 1000, ',');
                if ($headers) {
                    // Trim header whitespace
                    $headers = array_map('trim', $headers);
                    
                    // Map columns to indexes
                    $headerMap = array_flip($headers);

                    $requiredColumns = ['first_name', 'last_name', 'address', 'pincode', 'contact_number', 'campaign_name', 'grade_name', 'division_name'];
                    $missing = [];
                    foreach ($requiredColumns as $req) {
                        if (!isset($headerMap[$req])) {
                            $missing[] = $req;
                        }
                    }

                    if (!empty($missing)) {
                        $errorCount++;
                        $errorsLog[] = "[{$fileName}]: Missing required CSV headers: " . implode(', ', $missing);
                        fclose($handle);
                        continue;
                    }

                    $rowNum = 1;
                    while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                        $rowNum++;
                        // Build row data
                        $data = [];
                        foreach ($headerMap as $col => $idx) {
                            $data[$col] = isset($row[$idx]) ? trim($row[$idx]) : '';
                        }

                        // Basic validation
                        if (empty($data['first_name']) || empty($data['last_name']) || empty($data['campaign_name']) || empty($data['grade_name']) || empty($data['division_name'])) {
                            $errorCount++;
                            $errorsLog[] = "[{$fileName}] Row {$rowNum}: Missing required fields.";
                            continue;
                        }

                        // Find campaign, grade, division
                        $campaign = Campaign::where('school_id', $activeSchoolId)
                            ->where('name', $data['campaign_name'])
                            ->first();

                        $grade = Grade::where('school_id', $activeSchoolId)
                            ->where('name', $data['grade_name'])
                            ->first();

                        if (!$campaign || !$grade) {
                            $errorCount++;
                            $errorsLog[] = "[{$fileName}] Row {$rowNum}: Campaign '{$data['campaign_name']}' or Grade '{$data['grade_name']}' not found in active school.";
                            continue;
                        }

                        $division = Division::where('grade_id', $grade->id)
                            ->where('name', $data['division_name'])
                            ->first();

                        if (!$division) {
                            $errorCount++;
                            $errorsLog[] = "[{$fileName}] Row {$rowNum}: Division '{$data['division_name']}' not found under grade '{$grade->name}'.";
                            continue;
                        }

                        $scopes = $this->getPermittedScopes();
                        if ($scopes['restricted']) {
                            if (!in_array($grade->id, $scopes['grades']) || !in_array($division->id, $scopes['divisions'])) {
                                $errorCount++;
                                $errorsLog[] = "[{$fileName}] Row {$rowNum}: Grade '{$grade->name}' or Division '{$division->name}' is outside your permitted access scope.";
                                continue;
                            }
                        }

                        // Process photo matching
                        $photoPath = null;
                        if (!empty($data['photo_filename']) && $extractedPath) {
                            $localPhotoFile = $extractedPath . '/' . $data['photo_filename'];
                            
                            // Handle potential subdirectory matching inside zip
                            if (!file_exists($localPhotoFile)) {
                                $files = new \RecursiveIteratorIterator(
                                    new \RecursiveDirectoryIterator($extractedPath),
                                    \RecursiveIteratorIterator::LEAVES_ONLY
                                );
                                foreach ($files as $file) {
                                    if (!$file->isDir() && basename($file->getPathname()) === $data['photo_filename']) {
                                        $localPhotoFile = $file->getPathname();
                                        break;
                                    }
                                }
                            }

                            if (file_exists($localPhotoFile) && !is_dir($localPhotoFile)) {
                                $extension = pathinfo($localPhotoFile, PATHINFO_EXTENSION);
                                $newFileName = 'photos/' . uniqid() . '.' . $extension;
                                Storage::disk('public')->put($newFileName, file_get_contents($localPhotoFile));
                                $photoPath = $newFileName;
                            }
                        }

                        // Check if student profile matches (mobile + name + DOB)
                        $existingStudent = Student::findExisting(
                            $data['contact_number'] ?? null,
                            $data['first_name'],
                            $data['last_name'],
                            $data['dob'] ?? null
                        );

                        if ($existingStudent) {
                            // Check if already enrolled in this campaign
                            $isEnrolled = CampaignStudent::where('campaign_id', $campaign->id)
                                ->where('student_id', $existingStudent->id)
                                ->exists();

                            if ($isEnrolled) {
                                $skippedCount++;
                                continue;
                            }

                            // Matched an existing student — don't overwrite their data;
                            // clean up the extracted photo since it won't be attached.
                            if ($photoPath && Storage::disk('public')->exists($photoPath)) {
                                Storage::disk('public')->delete($photoPath);
                            }

                            $student = $existingStudent;
                            $matchedCount++;
                        } else {
                            // Create new student
                            $student = Student::create([
                                'first_name' => $data['first_name'],
                                'middle_name' => $data['middle_name'] ?: null,
                                'last_name' => $data['last_name'],
                                'gender' => !empty($data['gender']) ? $data['gender'] : null,
                                'blood_group' => $data['blood_group'] ?: null,
                                'dob' => !empty($data['dob']) ? $data['dob'] : null,
                                'address' => $data['address'],
                                'pincode' => $data['pincode'],
                                'contact_number' => $data['contact_number'],
                                'photo_path' => $photoPath,
                            ]);
                        }

                        $student->attemptParentLink();

                        // Create campaign enrollment
                        CampaignStudent::create([
                            'campaign_id' => $campaign->id,
                            'student_id' => $student->id,
                            'grade_id' => $grade->id,
                            'division_id' => $division->id,
                            'roll_no' => $data['roll_no'] ?? null,
                            'serial_number' => $data['serial_number'] ?? null,
                        ]);

                        $insertedCount++;
                    }
                }
                fclose($handle);
            }
        }

        // Cleanup extracted directory
        if ($extractedPath && file_exists($extractedPath)) {
            $this->deleteDir($extractedPath);
        }

        $this->isBulkModalOpen = false;
        $this->reset(['bulkCsvs', 'bulkZip']);

        $message = "Import complete! Added {$insertedCount} new student(s).";
        if ($matchedCount > 0) {
            $message .= " Matched and enrolled {$matchedCount} existing student(s).";
        }
        if ($skippedCount > 0) {
            $message .= " Skipped {$skippedCount} already-enrolled duplicate(s).";
        }
        if ($errorCount > 0) {
            $message .= " Failed {$errorCount} row(s). Check format details.";
        }

        session()->flash('message', $message);
        if (!empty($errorsLog)) {
            session()->flash('bulk_errors', $errorsLog);
        }
    }

    private function deleteDir($dirPath)
    {
        if (!is_dir($dirPath)) {
            return;
        }
        $files = array_diff(scandir($dirPath), ['.', '..']);
        foreach ($files as $file) {
            (is_dir("$dirPath/$file")) ? $this->deleteDir("$dirPath/$file") : unlink("$dirPath/$file");
        }
        return rmdir($dirPath);
    }

    public function syncParentLinks()
    {
        $usersByMobile = \App\Models\User::query()
            ->get(['id', 'mobile'])
            ->filter(fn ($u) => \App\Support\PhoneNumber::normalize($u->mobile) !== null)
            ->keyBy(fn ($u) => \App\Support\PhoneNumber::normalize($u->mobile));

        $linkedCount = 0;

        Student::whereNull('user_id')->chunkById(200, function ($students) use ($usersByMobile, &$linkedCount) {
            foreach ($students as $student) {
                $normalized = \App\Support\PhoneNumber::normalize($student->contact_number);
                if ($normalized && $usersByMobile->has($normalized)) {
                    $student->update(['user_id' => $usersByMobile->get($normalized)->id]);
                    $linkedCount++;
                }
            }
        });

        if ($linkedCount > 0) {
            session()->flash('message', "Successfully linked {$linkedCount} student record(s) to parent accounts.");
        } else {
            session()->flash('message', "All student records are already synced with parent accounts.");
        }
    }
}; ?>

@php
    $studentsList = $this->loadStudents();
    $studentCounts = $this->getStudentCounts();
@endphp

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

    @if (session()->has('bulk_errors'))
        <div class="p-5 mb-4 bg-red-50 dark:bg-red-950/20 border border-red-100 dark:border-red-900/30 rounded-2xl text-xs space-y-2 text-red-700 dark:text-red-400">
            <h4 class="font-extrabold uppercase tracking-wider text-[10px]">{{ __('Import Failures & Log') }}</h4>
            <ul class="list-disc pl-4 space-y-1">
                @foreach (session('bulk_errors') as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
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
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1 flex flex-wrap items-center gap-2 sm:gap-3">
                    <span class="inline-flex items-center gap-1.5 text-gray-600 dark:text-gray-300 font-medium whitespace-nowrap">
                        <span class="w-2 h-2 rounded-full bg-indigo-500 shrink-0"></span>
                        <strong class="font-bold text-gray-900 dark:text-gray-100">{{ $studentCounts['total'] }}</strong> {{ __('Total Students') }}
                    </span>
                    @if ($studentCounts['is_filtered'])
                        <span class="text-gray-300 dark:text-gray-600 select-none">•</span>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 border border-indigo-200/80 dark:border-indigo-700/40 whitespace-nowrap shadow-sm">
                            <svg class="w-3.5 h-3.5 shrink-0 text-indigo-500 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                            </svg>
                            <span><strong class="font-bold text-indigo-700 dark:text-indigo-300">{{ $studentCounts['filtered'] }}</strong> {{ __('Filtered') }}</span>
                        </span>
                    @endif
                </div>
            </div>
        </div>
        <div class="flex flex-col sm:flex-row items-center gap-3 w-full sm:w-auto">
            <button wire:click="syncParentLinks" class="inline-flex items-center justify-center gap-2 w-full sm:w-auto px-5 py-2.5 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-indigo-600 dark:text-indigo-400 font-bold text-xs uppercase tracking-wider rounded-xl transition shadow hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer">
                <svg class="w-4 h-4 text-indigo-500 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                <span>{{ __('Sync Links') }}</span>
            </button>
            <button wire:click="openExportModal" class="inline-flex items-center justify-center gap-2 w-full sm:w-auto px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs uppercase tracking-wider rounded-xl transition shadow cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                <span>{{ __('Export Center') }}</span>
            </button>
            <button wire:click="openBulkModal" class="inline-flex items-center justify-center gap-2 w-full sm:w-auto px-5 py-2.5 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 font-bold text-xs uppercase tracking-wider rounded-xl transition shadow hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                </svg>
                <span>{{ __('Bulk Import') }}</span>
            </button>
            <button wire:click="openCreateModal" class="inline-flex items-center justify-center gap-2 w-full sm:w-auto px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs uppercase tracking-wider rounded-xl transition shadow cursor-pointer">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                </svg>
                <span>{{ __('Add Student') }}</span>
            </button>
        </div>
    </div>

    <!-- Filters Bar -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 bg-white dark:bg-gray-800 p-5 rounded-3xl border border-gray-200 dark:border-gray-700 shadow-xl shadow-gray-200/50 dark:shadow-none">
        <!-- Search Input -->
        <div>
            <label class="text-[9px] uppercase font-bold text-gray-500 dark:text-gray-400 tracking-wider block mb-1.5">{{ __('Search Student') }}</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="{{ __('Name, Roll, Mobile, Address...') }}" class="w-full pl-9 pr-8 border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-xl text-xs focus:ring-indigo-500 focus:border-indigo-500 placeholder-gray-400 dark:placeholder-gray-500" />
                @if(!empty($search))
                    <button type="button" wire:click="$set('search', '')" class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                @endif
            </div>
        </div>

        <!-- Campaign Filter -->
        <div>
            <label class="text-[9px] uppercase font-bold text-gray-500 dark:text-gray-400 tracking-wider block mb-1.5">{{ __('Campaign') }}</label>
            <select wire:model.live="filterCampaign" class="w-full border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-xl text-xs focus:ring-indigo-500 focus:border-indigo-500">
                <option value="">{{ __('All Campaigns') }}</option>
                @foreach (\App\Models\Campaign::where('school_id', session('active_school_id'))->orderBy('created_at', 'desc')->get() as $camp)
                    <option value="{{ $camp->id }}">{{ $camp->name }}</option>
                @endforeach
            </select>
        </div>

        <!-- Grade Filter -->
        <div>
            <label class="text-[9px] uppercase font-bold text-gray-500 dark:text-gray-400 tracking-wider block mb-1.5">{{ __('Standard / Class') }}</label>
            <select wire:model.live="filterGrade" class="w-full border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-xl text-xs focus:ring-indigo-500 focus:border-indigo-500">
                <option value="">{{ __('All Standards') }}</option>
                @php
                    $scopes = $this->getPermittedScopes();
                    $gradesQuery = \App\Models\Grade::where('school_id', session('active_school_id'))->orderBy('name', 'asc');
                    if ($scopes['restricted']) {
                        $gradesQuery->whereIn('id', $scopes['grades']);
                    }
                    $filterGradesList = $gradesQuery->get();
                @endphp
                @foreach ($filterGradesList as $grade)
                    <option value="{{ $grade->id }}">{{ $grade->name }}</option>
                @endforeach
            </select>
        </div>

        <!-- Division Filter -->
        <div>
            <label class="text-[9px] uppercase font-bold text-gray-500 dark:text-gray-400 tracking-wider block mb-1.5">{{ __('Division') }}</label>
            <select wire:model.live="filterDivision" class="w-full border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-xl text-xs focus:ring-indigo-500 focus:border-indigo-500">
                <option value="">{{ __('All Divisions') }}</option>
                @if ($filterGrade)
                    @php
                        $divsQuery = \App\Models\Division::where('grade_id', $filterGrade)->orderBy('name', 'asc');
                        if ($scopes['restricted']) {
                            $divsQuery->whereIn('id', $scopes['divisions']);
                        }
                        $filterDivsList = $divsQuery->get();
                    @endphp
                    @foreach ($filterDivsList as $div)
                        <option value="{{ $div->id }}">{{ $div->name }}</option>
                    @endforeach
                @endif
            </select>
        </div>

        <!-- Actions / Clear Filters -->
        <div class="flex items-end">
            @if ($studentCounts['is_filtered'])
                <button wire:click="resetFilters" type="button" class="w-full px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-bold text-xs rounded-xl transition flex items-center justify-center gap-1.5 cursor-pointer">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    {{ __('Clear Filters') }}
                </button>
            @endif
        </div>


    </div>

    <!-- View Switcher & Template Status Bar -->
    <div class="flex flex-col sm:flex-row items-center justify-between gap-4 bg-white dark:bg-gray-800 p-4 rounded-3xl border border-gray-200 dark:border-gray-700 shadow-xl shadow-gray-200/50 dark:shadow-none">
        <div class="flex items-center gap-3">
            <span class="text-xs font-extrabold uppercase tracking-wider text-gray-500 dark:text-gray-400">View Layout:</span>
            <div class="flex items-center bg-gray-100 dark:bg-gray-900 p-1 rounded-2xl border border-gray-200 dark:border-gray-700">
                <button 
                    type="button" 
                    wire:click="$set('viewMode', 'auto')"
                    class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 {{ $viewMode === 'auto' ? 'bg-indigo-600 text-white shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white' }}"
                    title="Auto: Render Assigned Template if selected, otherwise Standard List"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    <span>Auto</span>
                </button>
                <button 
                    type="button" 
                    wire:click="$set('viewMode', 'list')"
                    class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 {{ $viewMode === 'list' ? 'bg-indigo-600 text-white shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white' }}"
                    title="Standard Info List"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                    <span>Standard List</span>
                </button>
                <button 
                    type="button" 
                    wire:click="$set('viewMode', 'template')"
                    class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 {{ $viewMode === 'template' ? 'bg-indigo-600 text-white shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white' }}"
                    title="Template ID Cards View"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/></svg>
                    <span>Template ID Cards</span>
                </button>
            </div>
        </div>

        @php
            $activeSchoolObj = session('active_school_id') ? \App\Models\School::find(session('active_school_id')) : null;
            $defaultSchoolTemplate = $this->getEffectiveTemplate();
        @endphp
        <div>
            @if($defaultSchoolTemplate)
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    Active Template: {{ $defaultSchoolTemplate->name }}
                </span>
            @else
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20">
                    <span>No Template Assigned (Showing Standard List)</span>
                </span>
            @endif
        </div>
    </div>

    <!-- Grid / List Container -->
    @php
        $isGlobalTemplateView = ($viewMode === 'template') || ($viewMode === 'auto' && $defaultSchoolTemplate !== null);
    @endphp
    
    <div class="{{ $isGlobalTemplateView ? 'grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6' : 'flex flex-col gap-6' }}">
        @forelse ($studentsList as $student)
            @php
                $studentTemplate = $this->getEffectiveTemplate($student);
                $isTemplateMode = ($viewMode === 'template') || ($viewMode === 'auto' && $studentTemplate !== null);
            @endphp

            @if($isTemplateMode && $studentTemplate)
                <!-- Template Design ID Card Box -->
                <div class="bg-white dark:bg-gray-800 rounded-3xl p-5 shadow-xl shadow-gray-200/40 dark:shadow-none border border-gray-100 dark:border-gray-700 hover:border-indigo-500/30 transition-all duration-300 flex flex-col justify-between items-center gap-4">
                    <!-- Top Info Header -->
                    <div class="w-full flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-700">
                        <div class="space-y-0.5">
                            <h4 class="font-extrabold text-gray-900 dark:text-gray-100 text-base leading-tight">
                                {{ $student->first_name }} {{ $student->last_name }}
                            </h4>
                            <div class="flex items-center gap-2">
                                <span class="text-[10px] font-bold text-indigo-600 dark:text-indigo-400">
                                    Template: {{ $studentTemplate->name }}
                                </span>
                            </div>
                        </div>
                        <button wire:click="openPreviewIdCard({{ $student->id }})" type="button" class="px-3 py-1.5 bg-indigo-50 dark:bg-indigo-950/40 hover:bg-indigo-100 dark:hover:bg-indigo-900/60 text-indigo-600 dark:text-indigo-400 font-extrabold text-xs rounded-xl transition flex items-center gap-1 cursor-pointer">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            Preview
                        </button>
                    </div>

                    <!-- Scaled Canvas Template Card Component -->
                    <div class="py-2 cursor-pointer transition-transform hover:scale-[1.02]" wire:click="openPreviewIdCard({{ $student->id }})">
                        <x-id-card-renderer 
                            :template="$studentTemplate" 
                            :student="$student" 
                            :school="$activeSchoolObj" 
                            :scale="($studentTemplate->orientation ?? 'landscape') === 'portrait' ? 0.46 : 0.36" 
                        />
                    </div>

                    <!-- Bottom Action Footer -->
                    <div class="w-full pt-3 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between">
                        <span class="text-[10px] font-mono font-bold tracking-widest text-gray-500 dark:text-gray-400">
                            ST-ID: #{{ $student->id }}
                        </span>
                        <div class="flex items-center gap-2">
                            <button wire:click="openPreviewIdCard({{ $student->id }})" class="p-2 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/60 dark:hover:bg-indigo-900/80 rounded-xl text-indigo-600 dark:text-indigo-400 transition-colors" title="View & Print ID Card">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/></svg>
                            </button>
                            <button wire:click="openEditModal({{ $student->id }})" class="p-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 rounded-xl text-gray-700 hover:text-indigo-600 dark:text-gray-200 dark:hover:text-indigo-400 transition-colors" title="Edit Student">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </button>
                            <button wire:click="confirmDelete({{ $student->id }})" class="p-2 bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/60 dark:hover:bg-rose-900/80 rounded-xl text-rose-600 dark:text-rose-400 transition-colors" title="Delete Student">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
            @else
                <!-- Standard Info List Item -->
                <div class="bg-white dark:bg-gray-800 rounded-3xl overflow-hidden shadow-xl shadow-gray-200/40 dark:shadow-none border border-gray-100 dark:border-gray-700 hover:border-indigo-500/30 dark:hover:border-indigo-400/20 transition-all duration-300 flex flex-col md:flex-row group">
                    <!-- Left Side Square Photo -->
                    <div class="relative w-full md:w-56 h-56 md:h-auto md:aspect-square bg-gray-100 dark:bg-gray-900 overflow-hidden shrink-0 border-r border-gray-200 dark:border-gray-700">
                        @if ($student->photo_path)
                            <img src="{{ asset('storage/' . $student->photo_path) }}" alt="{{ $student->first_name }}" class="object-cover w-full h-full group-hover:scale-105 transition-transform duration-500" />
                        @else
                            <div class="w-full h-full bg-gradient-to-br from-indigo-500 via-purple-500 to-pink-500 flex items-center justify-center text-white font-bold text-3xl">
                                {{ strtoupper(substr($student->first_name, 0, 1) . substr($student->last_name, 0, 1)) }}
                            </div>
                        @endif
                    </div>

                    <!-- Right Side Details -->
                    <div class="p-6 flex-1 flex flex-col justify-between space-y-4">
                        <div>
                            <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                                <h4 class="text-xl font-extrabold text-gray-900 dark:text-gray-100">
                                    {{ $student->first_name }} {{ $student->middle_name ? $student->middle_name . ' ' : '' }}{{ $student->last_name }}
                                </h4>
                                @if ($student->roll_no)
                                    <span class="px-3 py-1 bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 rounded-xl text-xs font-bold">
                                        Roll No: {{ $student->roll_no }}
                                    </span>
                                @endif
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3 text-xs text-gray-600 dark:text-gray-400 mt-3">
                                <div>
                                    <span class="font-bold text-gray-400 uppercase text-[9px] block">Contact</span>
                                    <span>{{ $student->contact_number }}</span>
                                </div>
                                @if ($student->dob)
                                    <div>
                                        <span class="font-bold text-gray-400 uppercase text-[9px] block">Date of Birth</span>
                                        <span>{{ \Carbon\Carbon::parse($student->dob)->format('d M, Y') }}</span>
                                    </div>
                                @endif
                                @if ($student->blood_group)
                                    <div>
                                        <span class="font-bold text-gray-400 uppercase text-[9px] block">Blood Group</span>
                                        <span>{{ $student->blood_group }}</span>
                                    </div>
                                @endif
                                @if ($student->address)
                                    <div class="sm:col-span-2 md:col-span-3">
                                        <span class="font-bold text-gray-400 uppercase text-[9px] block">Address</span>
                                        <span>{{ $student->address }}, {{ $student->pincode }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Card Actions -->
                        <div class="pt-4 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
                            <span class="text-[10px] uppercase font-bold tracking-widest text-gray-500 dark:text-gray-400">
                                ST-ID: #{{ $student->id }}
                            </span>
                            <div class="flex items-center gap-2">
                                @if($studentTemplate)
                                    <button wire:click="openPreviewIdCard({{ $student->id }})" class="px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/60 dark:hover:bg-indigo-900/80 rounded-xl text-indigo-600 dark:text-indigo-400 font-bold text-xs flex items-center gap-1 transition-colors">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/></svg>
                                        <span>View ID Card</span>
                                    </button>
                                @endif
                                <button wire:click="openEditModal({{ $student->id }})" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/60 dark:hover:bg-indigo-900/80 text-indigo-600 dark:text-indigo-400 border border-indigo-200/80 dark:border-indigo-700/60 rounded-xl text-xs font-bold transition shadow-sm cursor-pointer" title="Edit Student">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                    <span>Edit</span>
                                </button>
                                <button wire:click="confirmDelete({{ $student->id }})" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/60 dark:hover:bg-rose-900/80 text-rose-600 dark:text-rose-400 border border-rose-200/80 dark:border-rose-700/60 rounded-xl text-xs font-bold transition shadow-sm cursor-pointer" title="Delete Student">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                    <span>Delete</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        @empty
            <div class="bg-white dark:bg-gray-800 rounded-3xl p-12 text-center text-gray-400 dark:text-gray-500 border border-gray-100 dark:border-gray-700">
                {{ __('No students found.') }}
            </div>
        @endforelse
    </div>

    @if ($this->hasMore)
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
                <span>Loading more students...</span>
            </div>

            <button 
                wire:loading.remove 
                wire:target="loadMore" 
                wire:click="loadMore" 
                class="px-6 py-3 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/60 text-gray-700 dark:text-gray-300 font-extrabold text-xs uppercase tracking-wider rounded-2xl transition shadow-sm flex items-center gap-2 cursor-pointer"
            >
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 13l-7 7-7-7m14-6l-7 7-7-7"/>
                </svg>
                {{ __('Load More') }}
            </button>
        </div>
    @endif

    <!-- Create/Edit Modal (4-Step Wizard) -->
    @if ($isModalOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-gray-950/60 backdrop-blur-sm transition-opacity" wire:click="$set('isModalOpen', false)"></div>

            <!-- Modal Container -->
            <div class="bg-white dark:bg-gray-800 rounded-3xl overflow-hidden shadow-2xl transform transition-all w-full max-w-4xl z-50 border border-gray-100 dark:border-gray-700 flex flex-col max-h-[90vh]">
                <form wire:submit="saveStudent" class="flex flex-col h-full max-h-[90vh]">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between p-6 border-b border-gray-100 dark:border-gray-700 shrink-0">
                        <div>
                            <h3 class="text-lg font-black text-gray-900 dark:text-gray-100">
                                {{ $studentId ? __('Edit Student Record') : __('Add New Student') }}
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Complete the 4-part student profile setup</p>
                        </div>
                        <button type="button" wire:click="$set('isModalOpen', false)" class="p-2 rounded-xl text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- 4-Step Progress Header -->
                    <div class="px-6 py-3 border-b border-gray-100 dark:border-gray-700 bg-gray-50/80 dark:bg-gray-900/40 shrink-0">
                        <div class="flex items-center justify-between max-w-2xl mx-auto">
                            <!-- Step 1 -->
                            <button type="button" wire:click="setStep(1)" class="flex items-center gap-2 cursor-pointer">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center font-black text-xs transition {{ $activeStep === 1 ? 'bg-indigo-600 text-white shadow-md' : ($activeStep > 1 ? 'bg-emerald-500 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-500') }}">
                                    @if ($activeStep > 1) ✓ @else 1 @endif
                                </div>
                                <span class="text-xs font-extrabold hidden sm:inline {{ $activeStep === 1 ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-500' }}">1. Mobile Check</span>
                            </button>

                            <div class="flex-1 h-0.5 mx-2 {{ $activeStep > 1 ? 'bg-emerald-500' : 'bg-gray-200 dark:bg-gray-700' }}"></div>

                            <!-- Step 2 -->
                            <button type="button" wire:click="setStep(2)" class="flex items-center gap-2 cursor-pointer">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center font-black text-xs transition {{ $activeStep === 2 ? 'bg-indigo-600 text-white shadow-md' : ($activeStep > 2 ? 'bg-emerald-500 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-500') }}">
                                    @if ($activeStep > 2) ✓ @else 2 @endif
                                </div>
                                <span class="text-xs font-extrabold hidden sm:inline {{ $activeStep === 2 ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-500' }}">2. Student Details</span>
                            </button>

                            <div class="flex-1 h-0.5 mx-2 {{ $activeStep > 2 ? 'bg-emerald-500' : 'bg-gray-200 dark:bg-gray-700' }}"></div>

                            <!-- Step 3 -->
                            <button type="button" wire:click="setStep(3)" class="flex items-center gap-2 cursor-pointer">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center font-black text-xs transition {{ $activeStep === 3 ? 'bg-indigo-600 text-white shadow-md' : ($activeStep > 3 ? 'bg-emerald-500 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-500') }}">
                                    @if ($activeStep > 3) ✓ @else 3 @endif
                                </div>
                                <span class="text-xs font-extrabold hidden sm:inline {{ $activeStep === 3 ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-500' }}">3. Campaign</span>
                            </button>

                            <div class="flex-1 h-0.5 mx-2 {{ $activeStep > 3 ? 'bg-emerald-500' : 'bg-gray-200 dark:bg-gray-700' }}"></div>

                            <!-- Step 4 -->
                            <button type="button" wire:click="setStep(4)" class="flex items-center gap-2 cursor-pointer">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center font-black text-xs transition {{ $activeStep === 4 ? 'bg-indigo-600 text-white shadow-md' : 'bg-gray-200 dark:bg-gray-700 text-gray-500' }}">
                                    4
                                </div>
                                <span class="text-xs font-extrabold hidden sm:inline {{ $activeStep === 4 ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-500' }}">4. Photo</span>
                            </button>
                        </div>
                    </div>

                    <!-- Scrollable Modal Body -->
                    <div class="p-6 sm:p-8 overflow-y-auto flex-1 space-y-6">
                        <!-- PART 1: MOBILE NUMBER CHECK -->
                        @if ($activeStep === 1)
                            <div class="space-y-5">
                                <div class="bg-gradient-to-r from-indigo-50/80 via-purple-50/50 to-indigo-50/80 dark:from-indigo-950/40 dark:via-purple-950/30 dark:to-indigo-950/40 p-6 rounded-2xl border border-indigo-100 dark:border-indigo-900/60">
                                    <h4 class="text-sm font-black text-indigo-900 dark:text-indigo-200 uppercase tracking-wider mb-2">Part 1: Mobile Number Lookup</h4>
                                    <p class="text-xs text-gray-600 dark:text-gray-400 mb-4">Enter parent or guardian contact number to check for existing student profiles.</p>
                                    
                                    <div class="relative">
                                        <x-input-label for="contact_number" :value="__('Parent / Guardian Contact Number')" class="font-bold text-xs uppercase" />
                                        <div class="relative mt-1">
                                            <x-text-input wire:model.live.debounce.300ms="contact_number" id="contact_number" type="text" class="block w-full pl-10 text-lg font-bold" placeholder="e.g. 9876543210" required />
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-indigo-500">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.94.725l.548 2.2a1 1 0 01-.321.988l-1.305.98a10.582 10.582 0 004.872 4.872l.98-1.305a1 1 0 01.988-.321l2.2.548a1 1 0 01.725.94V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                            </div>
                                        </div>
                                        <x-input-error :messages="$errors->get('contact_number')" class="mt-1" />
                                    </div>

                                    @if ($this->matchingStudents->count() > 0 && !$studentId)
                                        <div class="mt-5 pt-4 border-t border-indigo-200/60 dark:border-indigo-800/60">
                                            <div class="flex items-center justify-between mb-3">
                                                <span class="text-xs font-black uppercase tracking-wider text-amber-600 dark:text-amber-400 flex items-center gap-1.5">
                                                    <svg class="w-4 h-4 text-amber-500 animate-bounce" fill="currentColor" viewBox="0 0 20 20"><path d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.57l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.57l7-10a1 1 0 011.12-.384z"/></svg>
                                                    {{ $this->matchingStudents->count() }} Existing Student Profile(s) Linked to {{ $contact_number }}
                                                </span>
                                            </div>

                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                                @foreach ($this->matchingStudents as $mStudent)
                                                    <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-indigo-200 dark:border-gray-700 shadow-sm flex items-center justify-between gap-3">
                                                        <div class="flex items-center gap-3 min-w-0">
                                                            @if ($mStudent->photo_path)
                                                                <img src="{{ asset('storage/' . $mStudent->photo_path) }}" class="w-10 h-10 rounded-full object-cover shrink-0 border" />
                                                            @else
                                                                <div class="w-10 h-10 rounded-full bg-indigo-600 text-white font-black text-xs flex items-center justify-center shrink-0">
                                                                    {{ strtoupper(substr($mStudent->first_name, 0, 1) . substr($mStudent->last_name, 0, 1)) }}
                                                                </div>
                                                            @endif
                                                            <div class="min-w-0">
                                                                <h5 class="font-extrabold text-sm text-gray-900 dark:text-gray-100 truncate">{{ $mStudent->first_name }} {{ $mStudent->last_name }}</h5>
                                                                <p class="text-[11px] text-gray-500 dark:text-gray-400">DOB: {{ $mStudent->dob ?? 'N/A' }} • ID #{{ $mStudent->id }}</p>
                                                            </div>
                                                        </div>

                                                        <button type="button" wire:click="selectExistingStudent({{ $mStudent->id }})" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold shrink-0 shadow-sm cursor-pointer">
                                                            Select & Continue
                                                        </button>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @elseif ($studentId)
                                        <div class="mt-4 flex items-center justify-between bg-emerald-50 dark:bg-emerald-950/40 p-3 rounded-xl border border-emerald-200 dark:border-emerald-800">
                                            <span class="text-xs font-bold text-emerald-800 dark:text-emerald-300">✓ Reusing Profile: <strong>{{ $first_name }} {{ $last_name }}</strong> (#{{ $studentId }})</span>
                                            <button type="button" wire:click="$set('studentId', null)" class="text-xs text-rose-600 font-bold hover:underline cursor-pointer">Clear</button>
                                        </div>
                                    @endif
                                </div>

                                <div class="flex justify-end pt-3">
                                    <button type="button" wire:click="nextStep" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-xs uppercase tracking-wider rounded-xl shadow-md transition cursor-pointer flex items-center gap-2">
                                        <span>Next: Student Details</span>
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </button>
                                </div>
                            </div>
                        @endif

                        <!-- PART 2: STUDENT DETAILS -->
                        @if ($activeStep === 2)
                            <div class="space-y-5">
                                <h4 class="text-sm font-black text-gray-900 dark:text-gray-100 uppercase tracking-wider">Part 2: Personal Student Information</h4>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                                    <div>
                                        <x-input-label for="first_name" :value="__('First Name')" />
                                        <x-text-input wire:model="first_name" id="first_name" type="text" class="mt-1 block w-full" placeholder="First Name" required />
                                        <x-input-error :messages="$errors->get('first_name')" class="mt-2" />
                                    </div>

                                    <div>
                                        <x-input-label for="middle_name" :value="__('Middle Name')" />
                                        <x-text-input wire:model="middle_name" id="middle_name" type="text" class="mt-1 block w-full" placeholder="Middle Name" />
                                        <x-input-error :messages="$errors->get('middle_name')" class="mt-2" />
                                    </div>

                                    <div>
                                        <x-input-label for="last_name" :value="__('Last Name')" />
                                        <x-text-input wire:model="last_name" id="last_name" type="text" class="mt-1 block w-full" placeholder="Last Name" required />
                                        <x-input-error :messages="$errors->get('last_name')" class="mt-2" />
                                    </div>

                                    <div>
                                        <x-input-label for="dob" :value="__('Date of Birth')" />
                                        <x-text-input wire:model="dob" id="dob" type="date" class="mt-1 block w-full" />
                                        <x-input-error :messages="$errors->get('dob')" class="mt-2" />
                                    </div>

                                    <div>
                                        <x-input-label for="gender" :value="__('Gender')" />
                                        <select wire:model="gender" id="gender" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 rounded-xl shadow-sm">
                                            <option value="">Select Gender</option>
                                            <option value="Male">Male</option>
                                            <option value="Female">Female</option>
                                            <option value="Other">Other</option>
                                        </select>
                                        <x-input-error :messages="$errors->get('gender')" class="mt-2" />
                                    </div>

                                    <div>
                                        <x-input-label for="blood_group" :value="__('Blood Group')" />
                                        <select wire:model="blood_group" id="blood_group" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 rounded-xl shadow-sm">
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

                                    <div class="md:col-span-2">
                                        <x-input-label for="address" :value="__('Full Address')" />
                                        <x-text-input wire:model="address" id="address" type="text" class="mt-1 block w-full" placeholder="Full residential address" required />
                                        <x-input-error :messages="$errors->get('address')" class="mt-2" />
                                    </div>

                                    <div>
                                        <x-input-label for="pincode" :value="__('Pincode')" />
                                        <x-text-input wire:model="pincode" id="pincode" type="text" class="mt-1 block w-full" placeholder="e.g. 400001" required />
                                        <x-input-error :messages="$errors->get('pincode')" class="mt-2" />
                                    </div>
                                </div>

                                <div class="flex items-center justify-between pt-4 border-t border-gray-100 dark:border-gray-700">
                                    <button type="button" wire:click="prevStep" class="px-5 py-2.5 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/60 rounded-xl font-bold text-xs uppercase text-gray-700 dark:text-gray-300 transition cursor-pointer">
                                        ← Back: Mobile Check
                                    </button>
                                    <button type="button" wire:click="nextStep" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-xs uppercase tracking-wider rounded-xl shadow-md transition cursor-pointer flex items-center gap-2">
                                        <span>Next: Campaign Details</span>
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </button>
                                </div>
                            </div>
                        @endif

                        <!-- PART 3: CAMPAIGN DETAILS -->
                        @if ($activeStep === 3)
                            <div class="space-y-5">
                                <h4 class="text-sm font-black text-gray-900 dark:text-gray-100 uppercase tracking-wider">Part 3: Academic & Campaign Enrollment</h4>

                                <!-- Campaign Duplicate Warning Alert -->
                                @if ($this->isAlreadyEnrolled)
                                    <div class="p-4 bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800 rounded-2xl flex items-center gap-3">
                                        <svg class="w-6 h-6 text-rose-600 dark:text-rose-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                        <div>
                                            <h5 class="text-xs font-black text-rose-800 dark:text-rose-300 uppercase tracking-wider">Student Already Enrolled in this Campaign</h5>
                                            <p class="text-xs text-rose-700 dark:text-rose-400 mt-0.5 font-medium">This student is already enrolled in the selected Campaign. Double enrollment for the same campaign is not allowed.</p>
                                        </div>
                                    </div>
                                @endif

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                                    <div>
                                        <x-input-label for="campaignId" :value="__('Campaign')" />
                                        <select wire:model.live="campaignId" id="campaignId" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 rounded-xl shadow-sm" required>
                                            <option value="">Select Campaign</option>
                                            @foreach (\App\Models\Campaign::where('school_id', session('active_school_id'))->orderBy('created_at', 'desc')->get() as $camp)
                                                <option value="{{ $camp->id }}">{{ $camp->name }}</option>
                                            @endforeach
                                        </select>
                                        <x-input-error :messages="$errors->get('campaignId')" class="mt-2" />
                                    </div>

                                    <div>
                                        <x-input-label for="gradeId" :value="__('Standard / Class')" />
                                        <select wire:model.live="gradeId" id="gradeId" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 rounded-xl shadow-sm" required>
                                            <option value="">Select Standard</option>
                                            @php
                                                $scopes = $this->getPermittedScopes();
                                                $gradesQuery = \App\Models\Grade::where('school_id', session('active_school_id'))->orderBy('name', 'asc');
                                                if ($scopes['restricted']) {
                                                    $gradesQuery->whereIn('id', $scopes['grades']);
                                                }
                                                $formGradesList = $gradesQuery->get();
                                            @endphp
                                            @foreach ($formGradesList as $grade)
                                                <option value="{{ $grade->id }}">{{ $grade->name }}</option>
                                            @endforeach
                                        </select>
                                        <x-input-error :messages="$errors->get('gradeId')" class="mt-2" />
                                    </div>

                                    <div>
                                        <x-input-label for="divisionId" :value="__('Division / Section')" />
                                        <select wire:model="divisionId" id="divisionId" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 rounded-xl shadow-sm" required>
                                            <option value="">Select Division</option>
                                            @php
                                                if ($gradeId) {
                                                    $divsQuery = \App\Models\Division::where('grade_id', $gradeId);
                                                    if ($scopes['restricted']) {
                                                        $divsQuery->whereIn('id', $scopes['divisions']);
                                                    }
                                                    $divisions = $divsQuery->get();
                                                } else {
                                                    $divisions = collect();
                                                }
                                            @endphp
                                            @foreach ($divisions as $div)
                                                <option value="{{ $div->id }}">{{ $div->name }}</option>
                                            @endforeach
                                        </select>
                                        <x-input-error :messages="$errors->get('divisionId')" class="mt-2" />
                                    </div>

                                    <div>
                                        <x-input-label for="roll_no" :value="__('Roll No')" />
                                        <x-text-input wire:model="roll_no" id="roll_no" type="text" class="mt-1 block w-full" placeholder="e.g. 101" />
                                        <x-input-error :messages="$errors->get('roll_no')" class="mt-2" />
                                    </div>

                                    <div>
                                        <x-input-label for="serial_number" :value="__('Serial / Ref No')" />
                                        <x-text-input wire:model="serial_number" id="serial_number" type="text" class="mt-1 block w-full" placeholder="e.g. REF-1001" />
                                        <x-input-error :messages="$errors->get('serial_number')" class="mt-2" />
                                    </div>
                                </div>

                                <div class="flex items-center justify-between pt-4 border-t border-gray-100 dark:border-gray-700">
                                    <button type="button" wire:click="prevStep" class="px-5 py-2.5 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/60 rounded-xl font-bold text-xs uppercase text-gray-700 dark:text-gray-300 transition cursor-pointer">
                                        ← Back: Student Details
                                    </button>
                                    <button type="button" wire:click="nextStep" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-xs uppercase tracking-wider rounded-xl shadow-md transition cursor-pointer flex items-center gap-2">
                                        <span>Next: Photo</span>
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </button>
                                </div>
                            </div>
                        @endif

                        <!-- PART 4: PHOTO & SUMMARY REVIEW -->
                        @if ($activeStep === 4)
                            <div class="space-y-6">
                                <h4 class="text-sm font-black text-gray-900 dark:text-gray-100 uppercase tracking-wider">Part 4: Student Photo & Final Review</h4>

                                <div class="bg-gray-50 dark:bg-gray-900/50 p-5 rounded-2xl border border-gray-200 dark:border-gray-700" x-data="photoStudio()" x-init="initStudio()">
                                    <x-input-label :value="__('Student Photo')" class="font-bold text-xs uppercase" />
                                    <div class="mt-3 flex items-center gap-5">
                                        @if ($photo)
                                            <img src="{{ $photo->temporaryUrl() }}" class="h-24 w-24 object-cover rounded-2xl border-2 border-indigo-500 shadow-md shrink-0" />
                                        @elseif ($currentPhotoPath)
                                            <img src="{{ asset('storage/' . $currentPhotoPath) }}" class="h-24 w-24 object-cover rounded-2xl border-2 border-indigo-500 shadow-md shrink-0" />
                                        @else
                                            <div class="h-24 w-24 bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 flex items-center justify-center text-gray-400 shrink-0 shadow-sm">
                                                <svg class="h-10 w-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                            </div>
                                        @endif

                                        <div class="flex-1 space-y-2">
                                            <input type="file" id="photo-studio-input" @change="openStudio($event)" class="hidden" accept="image/*" />
                                            <label for="photo-studio-input" class="cursor-pointer inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-black text-xs uppercase rounded-xl transition shadow-md shadow-indigo-600/20">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                                <span>{{ __('Open Photo Studio Editor 🎨') }}</span>
                                            </label>
                                            <p class="text-[11px] text-gray-500 dark:text-gray-400 font-medium">Crop (1:1 / 3:4), Remove & Change Background Color, Touch-up filters with Passport Silhouette Guide.</p>
                                        </div>
                                    </div>
                                    <x-input-error :messages="$errors->get('photo')" class="mt-2" />

                                    <!-- PHOTO STUDIO MODAL -->
                                    <div x-show="isOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
                                        <div class="fixed inset-0 bg-gray-950/80 backdrop-blur-md transition-opacity" @click="closeStudio()"></div>

                                        <div class="bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-3xl max-w-4xl w-full p-6 space-y-6 shadow-2xl relative z-10 text-gray-900 dark:text-white max-h-[90vh] flex flex-col">
                                            <!-- Modal Header -->
                                            <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-4 shrink-0">
                                                <div class="space-y-1">
                                                    <h3 class="text-lg font-black text-gray-900 dark:text-white flex items-center gap-2">
                                                        <span>🎨 Student Photo Studio</span>
                                                        <span class="text-[10px] font-black uppercase tracking-wider bg-indigo-50 text-indigo-600 dark:bg-indigo-500/20 dark:text-indigo-400 px-2.5 py-0.5 rounded-full border border-indigo-200 dark:border-indigo-500/30">AI Powered WASM</span>
                                                    </h3>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400">Crop, remove background, change background color, and touch-up before saving.</p>
                                                </div>
                                                <button type="button" @click="closeStudio()" class="text-gray-400 hover:text-gray-600 dark:hover:text-white p-2 transition">
                                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                </button>
                                            </div>

                                            <!-- Step Tabs -->
                                            <div class="flex items-center gap-2 border-b border-gray-100 dark:border-gray-800 pb-3 shrink-0">
                                                <button type="button" @click="step = 'crop'" :class="step === 'crop' ? 'bg-indigo-600 text-white shadow-md' : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300'" class="px-4 py-2 rounded-xl font-extrabold text-xs transition flex items-center gap-1.5 cursor-pointer">
                                                    <span>1. Crop & Align</span>
                                                </button>
                                                <button type="button" @click="step = 'background'" :class="step === 'background' ? 'bg-indigo-600 text-white shadow-md' : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300'" class="px-4 py-2 rounded-xl font-extrabold text-xs transition flex items-center gap-1.5 cursor-pointer">
                                                    <span>2. Background</span>
                                                </button>
                                                <button type="button" @click="step = 'touchup'" :class="step === 'touchup' ? 'bg-indigo-600 text-white shadow-md' : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300'" class="px-4 py-2 rounded-xl font-extrabold text-xs transition flex items-center gap-1.5 cursor-pointer">
                                                    <span>3. Touch-up Filters</span>
                                                </button>
                                                <button type="button" @click="step = 'preview'" :class="step === 'preview' ? 'bg-indigo-600 text-white shadow-md' : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300'" class="px-4 py-2 rounded-xl font-extrabold text-xs transition flex items-center gap-1.5 cursor-pointer">
                                                    <span>4. Final Preview</span>
                                                </button>
                                            </div>

                                            <!-- Warning Banner -->
                                            <template x-if="resWarning">
                                                <div class="bg-amber-50 border border-amber-200 text-amber-800 dark:bg-amber-500/10 dark:border-amber-500/20 dark:text-amber-300 p-3 rounded-xl text-xs font-semibold flex items-center gap-2 shrink-0">
                                                    <svg class="w-4 h-4 shrink-0 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                                    <span x-text="resWarning"></span>
                                                </div>
                                            </template>

                                            <!-- Main Studio Stage -->
                                            <div class="flex-1 overflow-y-auto min-h-0 space-y-4">
                                                <!-- STEP 1: CROP & ALIGN -->
                                                <div x-show="step === 'crop'" class="space-y-4">
                                                    <div class="flex flex-wrap items-center justify-between gap-3 bg-gray-50 dark:bg-gray-800/60 p-3 rounded-2xl border border-gray-100 dark:border-gray-700/60">
                                                        <div class="flex items-center gap-2">
                                                            <span class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Aspect Ratio:</span>
                                                            <button type="button" @click="setAspectRatio(1)" :class="aspectRatio === 1 ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 dark:bg-gray-700 dark:text-gray-300'" class="px-3 py-1.5 rounded-lg font-bold text-xs border border-gray-200 dark:border-gray-600 transition">1:1 Square</button>
                                                            <button type="button" @click="setAspectRatio(0.75)" :class="aspectRatio === 0.75 ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 dark:bg-gray-700 dark:text-gray-300'" class="px-3 py-1.5 rounded-lg font-bold text-xs border border-gray-200 dark:border-gray-600 transition">3:4 Passport</button>
                                                        </div>

                                                        <div class="flex items-center gap-2">
                                                            <button type="button" @click="rotate(-90)" class="p-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-xs font-bold hover:bg-gray-100 transition" title="Rotate Left 90°">↺ 90°</button>
                                                            <button type="button" @click="rotate(90)" class="p-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-xs font-bold hover:bg-gray-100 transition" title="Rotate Right 90°">↻ 90°</button>
                                                            <button type="button" @click="flipHorizontal()" class="p-2 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg text-xs font-bold hover:bg-gray-100 transition" title="Flip Horizontal">⇄ Flip</button>
                                                        </div>
                                                    </div>

                                                    <!-- Crop Stage Container with Passport Oval Silhouette Overlay -->
                                                    <div class="relative w-full h-80 bg-gray-950 rounded-2xl overflow-hidden flex items-center justify-center border border-gray-800">
                                                        <img x-ref="cropImage" class="max-h-full max-w-full block" />
                                                        
                                                        <!-- Passport Head Oval Silhouette Overlay Guide -->
                                                        <div class="absolute inset-0 pointer-events-none flex flex-col items-center justify-center opacity-35">
                                                            <div class="w-44 h-56 border-2 border-dashed border-indigo-400 rounded-[50%] flex flex-col items-center justify-start pt-6">
                                                                <div class="w-full border-t border-indigo-300/60 my-2"></div>
                                                                <span class="text-[9px] font-black uppercase text-indigo-300 tracking-wider bg-black/50 px-2 py-0.5 rounded">Eye Line</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- STEP 2: BACKGROUND -->
                                                <div x-show="step === 'background'" class="space-y-4">
                                                    <div class="bg-gray-50 dark:bg-gray-800/60 p-4 rounded-2xl border border-gray-100 dark:border-gray-700/60 space-y-4">
                                                        <div class="flex items-center justify-between">
                                                            <div>
                                                                <h5 class="text-xs font-extrabold uppercase text-gray-900 dark:text-white">Client-Side AI Background Isolation</h5>
                                                                <p class="text-[11px] text-gray-500 dark:text-gray-400">Isolate student subject and replace background with solid backdrop color.</p>
                                                            </div>

                                                            <button type="button" @click="removeBg()" :disabled="isProcessingBg" class="px-5 py-2.5 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white font-black text-xs uppercase rounded-xl transition shadow flex items-center gap-2 cursor-pointer disabled:opacity-50">
                                                                <template x-if="isProcessingBg">
                                                                    <div class="flex items-center gap-2">
                                                                        <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                                                        <span>Removing BG...</span>
                                                                    </div>
                                                                </template>
                                                                <template x-if="!isProcessingBg">
                                                                    <span>✨ Remove Background Now</span>
                                                                </template>
                                                            </button>
                                                        </div>

                                                        <template x-if="bgErrorMessage">
                                                            <p class="text-xs font-semibold text-rose-500 bg-rose-50 dark:bg-rose-950/40 p-2.5 rounded-xl border border-rose-200 dark:border-rose-900/50" x-text="bgErrorMessage"></p>
                                                        </template>

                                                        <!-- Background Color Swatches -->
                                                        <div class="space-y-2 pt-2 border-t border-gray-200 dark:border-gray-700">
                                                            <span class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Select Backdrop Fill Color:</span>
                                                            <div class="flex flex-wrap items-center gap-3">
                                                                <button type="button" @click="bgColor = '#ffffff'; renderCompositedCanvas()" :class="bgColor === '#ffffff' ? 'ring-2 ring-indigo-500 scale-110' : ''" class="w-8 h-8 rounded-full bg-white border border-gray-300 shadow-sm transition" title="White"></button>
                                                                <button type="button" @click="bgColor = '#f1f5f9'; renderCompositedCanvas()" :class="bgColor === '#f1f5f9' ? 'ring-2 ring-indigo-500 scale-110' : ''" class="w-8 h-8 rounded-full bg-slate-100 border border-gray-300 shadow-sm transition" title="Light Grey"></button>
                                                                <button type="button" @click="bgColor = '#38bdf8'; renderCompositedCanvas()" :class="bgColor === '#38bdf8' ? 'ring-2 ring-indigo-500 scale-110' : ''" class="w-8 h-8 rounded-full bg-sky-400 border border-sky-300 shadow-sm transition" title="Sky Blue"></button>
                                                                <button type="button" @click="bgColor = '#1e3a8a'; renderCompositedCanvas()" :class="bgColor === '#1e3a8a' ? 'ring-2 ring-indigo-500 scale-110' : ''" class="w-8 h-8 rounded-full bg-blue-900 border border-blue-800 shadow-sm transition" title="Navy Blue"></button>
                                                                <button type="button" @click="bgColor = '#dc2626'; renderCompositedCanvas()" :class="bgColor === '#dc2626' ? 'ring-2 ring-indigo-500 scale-110' : ''" class="w-8 h-8 rounded-full bg-red-600 border border-red-500 shadow-sm transition" title="Red"></button>
                                                                
                                                                <!-- Custom Color Picker -->
                                                                <div class="flex items-center gap-2 ml-2 pl-3 border-l border-gray-300 dark:border-gray-700">
                                                                    <input type="color" x-model="bgColor" @input="renderCompositedCanvas()" class="w-8 h-8 rounded-lg cursor-pointer border-0 bg-transparent" />
                                                                    <span class="text-xs font-mono text-gray-500" x-text="bgColor"></span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- STEP 3: TOUCH-UP FILTERS -->
                                                <div x-show="step === 'touchup'" class="space-y-4">
                                                    <div class="bg-gray-50 dark:bg-gray-800/60 p-4 rounded-2xl border border-gray-100 dark:border-gray-700/60 space-y-4">
                                                        <div class="flex items-center justify-between">
                                                            <span class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">One-Click Presets:</span>
                                                            <div class="flex items-center gap-2">
                                                                <button type="button" @click="applyPreset('enhance')" class="px-3 py-1.5 bg-indigo-600 text-white rounded-lg font-bold text-xs shadow-sm hover:bg-indigo-700 transition">✨ ID Photo Enhance</button>
                                                                <button type="button" @click="applyPreset('studio')" class="px-3 py-1.5 bg-purple-600 text-white rounded-lg font-bold text-xs shadow-sm hover:bg-purple-700 transition">💡 Studio Bright</button>
                                                                <button type="button" @click="applyPreset('reset')" class="px-3 py-1.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg font-bold text-xs hover:bg-gray-300 transition">Reset Filters</button>
                                                            </div>
                                                        </div>

                                                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2 border-t border-gray-200 dark:border-gray-700">
                                                            <!-- Brightness -->
                                                            <div class="space-y-1">
                                                                <div class="flex justify-between text-xs font-bold">
                                                                    <span>Brightness</span>
                                                                    <span x-text="brightness + '%'"></span>
                                                                </div>
                                                                <input type="range" min="50" max="150" x-model="brightness" @input="renderCompositedCanvas()" class="w-full accent-indigo-600" />
                                                            </div>

                                                            <!-- Contrast -->
                                                            <div class="space-y-1">
                                                                <div class="flex justify-between text-xs font-bold">
                                                                    <span>Contrast</span>
                                                                    <span x-text="contrast + '%'"></span>
                                                                </div>
                                                                <input type="range" min="50" max="150" x-model="contrast" @input="renderCompositedCanvas()" class="w-full accent-indigo-600" />
                                                            </div>

                                                            <!-- Saturation -->
                                                            <div class="space-y-1">
                                                                <div class="flex justify-between text-xs font-bold">
                                                                    <span>Saturation</span>
                                                                    <span x-text="saturation + '%'"></span>
                                                                </div>
                                                                <input type="range" min="50" max="150" x-model="saturation" @input="renderCompositedCanvas()" class="w-full accent-indigo-600" />
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- STEP 4: FINAL PREVIEW & CANVAS DISPLAY -->
                                                <div class="flex flex-col items-center justify-center p-4 bg-gray-950 rounded-2xl border border-gray-800 min-h-[260px]">
                                                    <canvas x-ref="studioCanvas" class="max-h-72 max-w-full rounded-xl shadow-2xl border border-gray-700/80 object-contain"></canvas>
                                                </div>
                                            </div>

                                            <!-- Modal Footer -->
                                            <div class="flex items-center justify-between pt-4 border-t border-gray-100 dark:border-gray-800 shrink-0">
                                                <button type="button" @click="resetState(); initStudio()" class="px-4 py-2 border border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-xl text-xs font-bold transition">
                                                    ↺ Reset to Original
                                                </button>

                                                <div class="flex items-center gap-3">
                                                    <button type="button" @click="closeStudio()" class="px-5 py-2.5 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl font-bold text-xs uppercase tracking-wider transition">
                                                        Cancel
                                                    </button>
                                                    <button type="button" @click="savePhoto()" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs uppercase tracking-wider rounded-xl shadow-lg shadow-emerald-600/20 transition cursor-pointer flex items-center gap-2">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                        <span>Save Photo</span>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Summary Box -->
                                <div class="bg-indigo-50/50 dark:bg-indigo-950/30 p-4 rounded-2xl border border-indigo-100 dark:border-indigo-900/40 text-xs space-y-2">
                                    <h5 class="font-extrabold text-indigo-900 dark:text-indigo-200 uppercase tracking-wider text-[11px]">Enrollment Summary</h5>
                                    <div class="grid grid-cols-2 gap-2 text-gray-700 dark:text-gray-300">
                                        <div><span class="text-gray-400 font-bold">Student:</span> {{ $first_name }} {{ $last_name }}</div>
                                        <div><span class="text-gray-400 font-bold">Contact:</span> {{ $contact_number }}</div>
                                        <div><span class="text-gray-400 font-bold">DOB:</span> {{ $dob ?: 'N/A' }}</div>
                                        <div><span class="text-gray-400 font-bold">Address:</span> {{ $address }}</div>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between pt-4 border-t border-gray-100 dark:border-gray-700">
                                    <button type="button" wire:click="prevStep" class="px-5 py-2.5 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/60 rounded-xl font-bold text-xs uppercase text-gray-700 dark:text-gray-300 transition cursor-pointer">
                                        ← Back: Campaign
                                    </button>

                                    <button type="submit" @if($this->isAlreadyEnrolled && !$studentId) disabled @endif class="px-7 py-3 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white font-extrabold text-xs uppercase tracking-wider rounded-xl shadow-lg shadow-indigo-600/30 transition cursor-pointer flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        <span>{{ $studentId ? __('Update Student Record') : __('Save & Complete Enrollment') }}</span>
                                    </button>
                                </div>
                            </div>
                        @endif
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
                                {{ __('Remove Student from Campaign') }}
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                {{ __('Campaign Enrollment Removal') }}
                            </p>
                        </div>
                    </div>

                    <p class="text-xs text-gray-600 dark:text-gray-300 mb-6 leading-relaxed">
                        {{ __('Are you sure you want to remove this student from the campaign? Their enrollment in this campaign will be removed, but their master profile will remain intact.') }}
                    </p>

                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                        <button type="button" wire:click="$set('isConfirmDeleteOpen', false)" class="px-5 py-2.5 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/60 rounded-xl font-bold text-xs uppercase text-gray-700 dark:text-gray-300 transition cursor-pointer">
                            {{ __('Cancel') }}
                        </button>
                        <button type="button" wire:click="deleteStudent" class="px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-xl font-bold text-xs uppercase shadow transition cursor-pointer">
                            {{ __('Remove') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Bulk Import Modal -->
    @if ($isBulkModalOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-gray-950/65 backdrop-blur-sm transition-opacity" wire:click="$set('isBulkModalOpen', false)"></div>

            <div class="bg-white dark:bg-gray-800 rounded-3xl overflow-hidden shadow-2xl transform transition-all max-w-lg w-full border border-gray-100 dark:border-gray-700 z-10 p-6 sm:p-8">
                <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-gray-700 mb-6">
                    <h3 class="text-lg font-black text-gray-900 dark:text-gray-100">
                        {{ __('Bulk Import Students') }}
                    </h3>
                    <button wire:click="$set('isBulkModalOpen', false)" class="text-gray-400 hover:text-gray-650 dark:hover:text-gray-300">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form wire:submit="importBulkStudents" class="space-y-6">
                    <!-- CSV File Input -->
                    <div>
                        <div class="flex items-center justify-between">
                            <x-input-label for="bulkCsvs" :value="__('1. Upload CSV Data File(s) (Required)')" />
                            <button type="button" wire:click="downloadSampleCsv" class="inline-flex items-center gap-1.5 text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 hover:underline cursor-pointer transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                </svg>
                                {{ __('Download Sample CSV') }}
                            </button>
                        </div>
                        <input wire:model="bulkCsvs" id="bulkCsvs" type="file" accept=".csv" class="mt-2 block w-full text-xs text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-indigo-50 dark:file:bg-indigo-950/30 file:text-indigo-700 dark:file:text-indigo-400 file:cursor-pointer hover:file:bg-indigo-100 dark:hover:file:bg-indigo-900/50 transition" multiple required>
                        <span class="text-[10px] text-gray-405 dark:text-gray-500 mt-1.5 block leading-normal">
                            {{ __('Accepts standard .csv containing student fields. You can select multiple files at once. Required CSV columns: ') }}
                            <code class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-900 rounded text-indigo-650 dark:text-indigo-400 font-mono text-[9px]">first_name, last_name, address, pincode, contact_number, campaign_name, grade_name, division_name</code>.
                            <br>
                            {{ __('Optional columns: ') }}
                            <code class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-900 rounded text-[9px] font-mono">middle_name, roll_no, serial_number, dob, blood_group, gender, photo_filename</code>.
                        </span>
                        <x-input-error :messages="$errors->get('bulkCsvs')" class="mt-2" />
                    </div>

                    <!-- ZIP Photos Input -->
                    <div>
                        <x-input-label for="bulkZip" :value="__('2. Upload Photos Archive ZIP (Optional)')" />
                        <input wire:model="bulkZip" id="bulkZip" type="file" accept=".zip" class="mt-2 block w-full text-xs text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-indigo-50 dark:file:bg-indigo-950/30 file:text-indigo-700 dark:file:text-indigo-400 file:cursor-pointer hover:file:bg-indigo-100 dark:hover:file:bg-indigo-900/50 transition">
                        <span class="text-[10px] text-gray-405 dark:text-gray-500 mt-1.5 block leading-normal">
                            {{ __('Upload a .zip file containing all student photos. Ensure filenames match exactly with the ') }}
                            <code class="px-1 py-0.5 bg-gray-100 dark:bg-gray-900 rounded text-[9px] font-mono">photo_filename</code>
                            {{ __(' column in the CSV.') }}
                        </span>
                        <x-input-error :messages="$errors->get('bulkZip')" class="mt-2" />
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                        <button type="button" wire:click="$set('isBulkModalOpen', false)" class="px-5 py-2.5 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/60 rounded-xl font-bold text-xs uppercase text-gray-700 dark:text-gray-300 transition cursor-pointer">
                            {{ __('Cancel') }}
                        </button>
                        <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold text-xs uppercase shadow transition cursor-pointer">
                            {{ __('Import Now') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Interactive Student ID Card Preview & Print Modal -->
    @if ($isPreviewIdCardOpen && $previewStudentId)
        @php
            $targetStudent = \App\Models\Student::with('campaignStudents.grade', 'campaignStudents.division', 'campaignStudents.campaign')->find($previewStudentId);
            $targetTemplate = $targetStudent ? $this->getEffectiveTemplate($targetStudent) : null;
            $activeSchoolObj = session('active_school_id') ? \App\Models\School::find(session('active_school_id')) : null;
        @endphp
        @if($targetStudent && $targetTemplate)
            <div class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
                <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-md transition-opacity" wire:click="closePreviewIdCard"></div>

                <div class="bg-slate-900 border border-slate-800 rounded-3xl max-w-4xl w-full p-6 sm:p-8 space-y-6 shadow-2xl relative z-10 text-white">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between border-b border-slate-800 pb-4">
                        <div class="space-y-1">
                            <h3 class="text-xl font-black text-white flex items-center gap-2">
                                <span>🪪 Student ID Card Preview</span>
                            </h3>
                            <p class="text-xs text-slate-400">
                                {{ $targetStudent->first_name }} {{ $targetStudent->last_name }} &bull; Active Template: <strong class="text-indigo-400">{{ $targetTemplate->name }}</strong>
                            </p>
                        </div>
                        <button wire:click="closePreviewIdCard" class="text-slate-400 hover:text-white p-2 transition">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Card Render Area -->
                    <div id="printable-id-card-area" class="flex items-center justify-center p-8 bg-slate-950/80 rounded-2xl border border-slate-800/80 shadow-inner overflow-auto">
                        <x-id-card-renderer 
                            :template="$targetTemplate" 
                            :student="$targetStudent" 
                            :school="$activeSchoolObj" 
                            :scale="($targetTemplate->orientation ?? 'landscape') === 'portrait' ? 0.65 : 0.75" 
                        />
                    </div>

                    <!-- Modal Actions -->
                    <div class="flex items-center justify-between pt-2 border-t border-slate-800">
                        <span class="text-xs text-slate-400 font-mono">Format: Standard CR-80 (85.6mm &times; 54.0mm)</span>
                        <div class="flex items-center gap-3">
                            <a href="/school-admin/students/{{ $targetStudent->id }}/export-pdf?school_id={{ session('active_school_id') }}&campaign_id={{ $filterCampaign }}" target="_blank" class="px-5 py-2.5 bg-purple-600 hover:bg-purple-700 text-white font-bold text-xs uppercase tracking-wider rounded-xl transition shadow flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                </svg>
                                <span>Download PDF</span>
                            </a>
                            <a href="/school-admin/students/{{ $targetStudent->id }}/export-png?school_id={{ session('active_school_id') }}&campaign_id={{ $filterCampaign }}" target="_blank" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs uppercase tracking-wider rounded-xl transition shadow flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <span>Download PNG</span>
                            </a>
                            <button type="button" wire:click="closePreviewIdCard" class="px-5 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-xs uppercase tracking-wider rounded-xl transition cursor-pointer">
                                {{ __('Close') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endif

    <!-- Export Center Modal -->
    @if ($isExportModalOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto" wire:poll.5s>
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-gray-900/60 dark:bg-black/80 backdrop-blur-sm" wire:click="closeExportModal"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

                <div class="inline-block w-full max-w-3xl p-6 overflow-hidden text-left align-middle transition-all transform bg-white dark:bg-gray-800 rounded-3xl shadow-2xl border border-gray-100 dark:border-gray-700 sm:my-8">
                    <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-gray-700">
                        <div class="flex items-center gap-3">
                            <div class="p-2.5 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 rounded-xl">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ __('Export & Print Center') }}</h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Bulk ID cards export, excel roster, and imposition print PDF.') }}</p>
                            </div>
                        </div>
                        <button wire:click="closeExportModal" class="text-gray-400 hover:text-gray-500 focus:outline-none">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    @php
                        $targetStudentsCount = count($studentsList);
                        $unverifiedCount = collect($studentsList)->filter(function($s) {
                            $enrollments = is_array($s) ? ($s['campaign_students'] ?? []) : ($s->campaignStudents ?? []);
                            $first = collect($enrollments)->first();
                            return !($first && !empty(is_array($first) ? $first['verified_at'] : $first->verified_at));
                        })->count();
                    @endphp

                    @if ($unverifiedCount > 0)
                        <div class="mt-4 p-4 bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-900/40 rounded-2xl flex items-center gap-3 text-xs text-amber-800 dark:text-amber-400">
                            <svg class="w-5 h-5 text-amber-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <span><strong>{{ __('Verification Warning') }}:</strong> {{ __(':unverified of :total selected students are not yet verified.', ['unverified' => $unverifiedCount, 'total' => $targetStudentsCount]) }} {{ __('You may proceed with export.') }}</span>
                        </div>
                    @endif

                    <!-- Export Configuration Options -->
                    <div class="mt-6 space-y-4">
                        <div>
                            <label class="text-xs font-bold text-gray-700 dark:text-gray-300 block mb-2">{{ __('Select Export Format') }}</label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                                <label class="p-3 border rounded-2xl flex flex-col justify-between cursor-pointer transition {{ $exportType === 'excel_photo_zip' ? 'border-indigo-600 bg-indigo-50/50 dark:bg-indigo-950/20 text-indigo-900 dark:text-indigo-200' : 'border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/40' }}">
                                    <input type="radio" wire:model.live="exportType" value="excel_photo_zip" class="hidden" />
                                    <span class="font-bold text-xs">{{ __('Excel Roster + Photos ZIP') }}</span>
                                    <span class="text-[10px] text-gray-500 dark:text-gray-400 mt-1">{{ __('Spreadsheet plus student photos directory.') }}</span>
                                </label>
                                <label class="p-3 border rounded-2xl flex flex-col justify-between cursor-pointer transition {{ $exportType === 'png_zip' ? 'border-indigo-600 bg-indigo-50/50 dark:bg-indigo-950/20 text-indigo-900 dark:text-indigo-200' : 'border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/40' }}">
                                    <input type="radio" wire:model.live="exportType" value="png_zip" class="hidden" />
                                    <span class="font-bold text-xs">{{ __('Rendered Cards PNG (ZIP)') }}</span>
                                    <span class="text-[10px] text-gray-500 dark:text-gray-400 mt-1">{{ __('High-resolution PNG image per student.') }}</span>
                                </label>
                                <label class="p-3 border rounded-2xl flex flex-col justify-between cursor-pointer transition {{ $exportType === 'single_card_pdf' ? 'border-indigo-600 bg-indigo-50/50 dark:bg-indigo-950/20 text-indigo-900 dark:text-indigo-200' : 'border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/40' }}">
                                    <input type="radio" wire:model.live="exportType" value="single_card_pdf" class="hidden" />
                                    <span class="font-bold text-xs">{{ __('Single Card PDF (ID Printer)') }}</span>
                                    <span class="text-[10px] text-gray-500 dark:text-gray-400 mt-1">{{ __('1 Card per Page (CR80 exact size) for direct thermal ID card printers.') }}</span>
                                </label>
                                <label class="p-3 border rounded-2xl flex flex-col justify-between cursor-pointer transition {{ $exportType === 'imposition_pdf' ? 'border-indigo-600 bg-indigo-50/50 dark:bg-indigo-950/20 text-indigo-900 dark:text-indigo-200' : 'border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/40' }}">
                                    <input type="radio" wire:model.live="exportType" value="imposition_pdf" class="hidden" />
                                    <span class="font-bold text-xs">{{ __('Print Imposition PDF') }}</span>
                                    <span class="text-[10px] text-gray-500 dark:text-gray-400 mt-1">{{ __('Multi-card layout sheet with trim marks & bleed.') }}</span>
                                </label>
                            </div>
                        </div>

                        @if ($exportType === 'imposition_pdf')
                            <div class="p-4 bg-gray-50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 rounded-2xl space-y-3 text-xs">
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                    <div>
                                        <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">{{ __('Page Size') }}</label>
                                        <select wire:model.live="exportPageSize" class="w-full border-gray-200 dark:border-gray-700 dark:bg-gray-800 rounded-xl text-xs font-semibold">
                                            <option value="A4">A4 (210 x 297 mm)</option>
                                            <option value="Letter">Letter (215.9 x 279.4 mm)</option>
                                            <option value="Custom">Custom Size</option>
                                        </select>
                                    </div>
                                    @if ($exportPageSize === 'Custom')
                                        <div>
                                            <label class="block font-bold text-indigo-600 dark:text-indigo-400 mb-1">{{ __('Custom Width (mm)') }}</label>
                                            <input type="number" step="1" min="50" max="2000" wire:model="exportCustomWidthMm" class="w-full border-indigo-300 focus:border-indigo-500 dark:border-indigo-700 dark:bg-gray-800 rounded-xl text-xs font-semibold" placeholder="210" />
                                        </div>
                                        <div>
                                            <label class="block font-bold text-indigo-600 dark:text-indigo-400 mb-1">{{ __('Custom Height (mm)') }}</label>
                                            <input type="number" step="1" min="50" max="2000" wire:model="exportCustomHeightMm" class="w-full border-indigo-300 focus:border-indigo-500 dark:border-indigo-700 dark:bg-gray-800 rounded-xl text-xs font-semibold" placeholder="297" />
                                        </div>
                                    @endif
                                    <div>
                                        <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">{{ __('Bleed (mm)') }}</label>
                                        <input type="number" step="0.5" wire:model="exportBleedMm" class="w-full border-gray-200 dark:border-gray-700 dark:bg-gray-800 rounded-xl text-xs" />
                                    </div>
                                    <div>
                                        <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">{{ __('Safety Margin (mm)') }}</label>
                                        <input type="number" step="0.5" wire:model="exportMarginMm" class="w-full border-gray-200 dark:border-gray-700 dark:bg-gray-800 rounded-xl text-xs" />
                                    </div>
                                    <div>
                                        <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">{{ __('Gutter (mm)') }}</label>
                                        <input type="number" step="0.5" wire:model="exportGutterMm" class="w-full border-gray-200 dark:border-gray-700 dark:bg-gray-800 rounded-xl text-xs" />
                                    </div>
                                </div>
                                @if ($exportPageSize === 'Custom')
                                    <p class="text-[11px] text-indigo-500 dark:text-indigo-400 font-medium">
                                        💡 Custom page dimensions ({{ $exportCustomWidthMm }}mm x {{ $exportCustomHeightMm }}mm) and layout options are saved to your setup for future exports.
                                    </p>
                                @endif
                            </div>
                        @endif

                        <div class="flex justify-end pt-2">
                            <button wire:click="triggerExport" wire:loading.attr="disabled" wire:target="triggerExport" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-60 text-white font-bold text-xs uppercase tracking-wider rounded-xl transition shadow cursor-pointer flex items-center justify-center gap-2">
                                <span wire:loading.remove wire:target="triggerExport" class="flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                    <span>{{ __('Start Background Export') }}</span>
                                </span>
                                <span wire:loading wire:target="triggerExport" class="flex items-center gap-2">
                                    <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    <span>{{ __('Processing Export...') }}</span>
                                </span>
                            </button>
                        </div>
                    </div>

                    <!-- My Recent Exports List -->
                    @php
                        $userExports = \App\Models\Export::where('school_id', session('active_school_id'))
                            ->where('user_id', auth()->id())
                            ->orderBy('id', 'desc')
                            ->take(10)
                            ->get();
                        $hasActiveExport = $userExports->contains(fn($e) => in_array($e->status, ['pending', 'processing']));
                    @endphp
                    <div class="mt-8 border-t border-gray-100 dark:border-gray-700 pt-5" @if($hasActiveExport) wire:poll.2s @endif>
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="text-xs font-extrabold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('My Recent Exports') }}</h4>
                            @if ($userExports->count() > 0)
                                <button wire:click="clearAllExports" wire:confirm="Are you sure you want to delete all export history and files?" type="button" class="text-[11px] font-semibold text-rose-500 hover:text-rose-600 dark:text-rose-400 hover:underline transition cursor-pointer">
                                    {{ __('Clear All History') }}
                                </button>
                            @endif
                        </div>

                        <div class="space-y-2 max-h-56 overflow-y-auto pr-1">
                            @forelse ($userExports as $exp)
                                <div class="p-3 bg-gray-50 dark:bg-gray-900/60 border border-gray-100 dark:border-gray-700/60 rounded-2xl flex items-center justify-between text-xs">
                                    <div>
                                        <span class="font-bold text-gray-800 dark:text-gray-200 uppercase text-[11px] block">
                                            {{ str_replace('_', ' ', $exp->type) }}
                                        </span>
                                        <span class="text-[10px] text-gray-500">
                                            {{ $exp->created_at->format('M d, H:i') }} • {{ $exp->processed_items }}/{{ $exp->total_items ?? 0 }} items
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        @if ($exp->status === 'completed')
                                            <span class="px-2.5 py-1 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 rounded-lg text-[10px] font-bold">COMPLETED</span>
                                            <a href="{{ route('exports.download', $exp) }}" target="_blank" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow transition">
                                                Download
                                            </a>
                                        @elseif ($exp->status === 'processing')
                                            <span class="px-2.5 py-1 bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 rounded-lg text-[10px] font-bold flex items-center gap-1.5">
                                                <svg class="animate-spin h-3 w-3" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                                PROCESSING
                                            </span>
                                        @elseif ($exp->status === 'failed')
                                            <div class="text-right">
                                                <span class="px-2.5 py-1 bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 rounded-lg text-[10px] font-bold">FAILED</span>
                                                @if ($exp->error_message)
                                                    <span class="block text-[9px] text-rose-500 dark:text-rose-400 max-w-xs truncate mt-1" title="{{ $exp->error_message }}">
                                                        {{ $exp->error_message }}
                                                    </span>
                                                @endif
                                            </div>
                                        @else
                                            <span class="px-2.5 py-1 bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 rounded-lg text-[10px] font-bold">PENDING</span>
                                        @endif

                                        <button wire:click="deleteExport({{ $exp->id }})" wire:confirm="Delete this export file and record?" type="button" class="p-1.5 text-gray-400 hover:text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/40 rounded-xl transition cursor-pointer" title="Delete export">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-4 text-xs text-gray-400">
                                    {{ __('No recent export tasks found.') }}
                                </div>
                            @endforelse
                        </div>
                    </div>

                </div>
            </div>
        </div>
    @endif
</div>

