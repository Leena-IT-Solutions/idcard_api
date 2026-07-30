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
    public $filterCampaign = '';
    public $filterGrade = '';
    public $filterDivision = '';

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

    public function updatedFilterCampaign()
    {
        $this->perPage = 12;
    }

    public function updatedFilterGrade()
    {
        $this->filterDivision = '';
        $this->perPage = 12;
    }

    public function updatedFilterDivision()
    {
        $this->perPage = 12;
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

        $filteredCount = $filteredQuery->count();
        $isFiltered = !empty($this->filterCampaign) || !empty($this->filterGrade) || !empty($this->filterDivision);

        return [
            'total' => $totalCount,
            'filtered' => $filteredCount,
            'is_filtered' => $isFiltered
        ];
    }

    public function resetFilters()
    {
        $this->reset(['filterCampaign', 'filterGrade', 'filterDivision']);
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

    public function resetForm()
    {
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
            'photo' => ['nullable', 'image', 'max:2048'], // Max 2MB
        ];

        $validated = $this->validate($rules);

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
            $enrollment = \App\Models\CampaignStudent::where('student_id', $student->id)
                ->whereHas('campaign', function($q) use ($activeSchoolId) {
                    $q->where('school_id', $activeSchoolId);
                })->first();

            if ($enrollment) {
                $scopes = $this->getPermittedScopes();
                if ($scopes['restricted']) {
                    if (!in_array($enrollment->grade_id, $scopes['grades']) || !in_array($enrollment->division_id, $scopes['divisions'])) {
                        abort(403, 'You do not have permission to delete this student.');
                    }
                }
            }

            if ($student->photo_path) {
                Storage::disk('public')->delete($student->photo_path);
            }
            $student->delete();
            session()->flash('message', 'Student deleted successfully.');
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
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 bg-white dark:bg-gray-800 p-5 rounded-3xl border border-gray-200 dark:border-gray-700 shadow-xl shadow-gray-200/50 dark:shadow-none">
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
                <button wire:click="resetFilters" type="button" class="w-full px-4 py-2.5 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-bold text-xs rounded-xl transition flex items-center justify-center gap-1.5 cursor-pointer">
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
        <div class="flex justify-center pt-8">
            <button wire:click="loadMore" class="px-6 py-3 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/60 text-gray-700 dark:text-gray-300 font-extrabold text-xs uppercase tracking-wider rounded-2xl transition shadow-sm flex items-center gap-2 cursor-pointer">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 13l-7 7-7-7m14-6l-7 7-7-7"/>
                </svg>
                {{ __('Load More') }}
            </button>
        </div>
    @endif

    <!-- Create/Edit Modal -->
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
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Fill in student details and enrollment parameters</p>
                        </div>
                        <button type="button" wire:click="$set('isModalOpen', false)" class="p-2 rounded-xl text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Scrollable Modal Body -->
                    <div class="p-6 sm:p-8 overflow-y-auto flex-1 space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-5">
                            <!-- Row 1: Names & Roll No -->
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
                                <x-input-label for="roll_no" :value="__('Roll No')" />
                                <x-text-input wire:model="roll_no" id="roll_no" type="text" class="mt-1 block w-full" placeholder="e.g. 101" />
                                <x-input-error :messages="$errors->get('roll_no')" class="mt-2" />
                            </div>

                            <!-- Row 2: Ref No & Academic Enrollment -->
                            <div>
                                <x-input-label for="serial_number" :value="__('Serial / Ref No')" />
                                <x-text-input wire:model="serial_number" id="serial_number" type="text" class="mt-1 block w-full" placeholder="e.g. REF-1001" />
                                <x-input-error :messages="$errors->get('serial_number')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="campaignId" :value="__('Campaign')" />
                                <select wire:model.live="campaignId" id="campaignId" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-xl shadow-sm" required>
                                    <option value="">Select Campaign</option>
                                    @foreach (\App\Models\Campaign::where('school_id', session('active_school_id'))->orderBy('created_at', 'desc')->get() as $camp)
                                        <option value="{{ $camp->id }}">{{ $camp->name }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('campaignId')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="gradeId" :value="__('Standard / Class')" />
                                <select wire:model.live="gradeId" id="gradeId" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-xl shadow-sm" required>
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
                                <select wire:model="divisionId" id="divisionId" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-xl shadow-sm" required>
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

                            <!-- Row 3: Personal & Contact Attributes -->
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

                            <div>
                                <x-input-label for="dob" :value="__('Date of Birth')" />
                                <x-text-input wire:model="dob" id="dob" type="date" class="mt-1 block w-full" />
                                <x-input-error :messages="$errors->get('dob')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="contact_number" :value="__('Contact Number')" />
                                <x-text-input wire:model="contact_number" id="contact_number" type="text" class="mt-1 block w-full" placeholder="e.g. 9876543210" required />
                                <x-input-error :messages="$errors->get('contact_number')" class="mt-2" />
                            </div>

                            <!-- Row 4: Full Address (Text Input) & Pincode -->
                            <div class="md:col-span-3">
                                <x-input-label for="address" :value="__('Full Address')" />
                                <x-text-input wire:model="address" id="address" type="text" class="mt-1 block w-full" placeholder="e.g. 123 Main Street, Sector 4, City" required />
                                <x-input-error :messages="$errors->get('address')" class="mt-2" />
                            </div>

                            <div class="md:col-span-1">
                                <x-input-label for="pincode" :value="__('Pincode')" />
                                <x-text-input wire:model="pincode" id="pincode" type="text" class="mt-1 block w-full" placeholder="e.g. 400001" required />
                                <x-input-error :messages="$errors->get('pincode')" class="mt-2" />
                            </div>

                            <!-- Row 5: Photo Upload Card -->
                            <div class="md:col-span-4 border-t border-gray-100 dark:border-gray-700 pt-4">
                                <x-input-label :value="__('Student Photo')" />
                                <div class="mt-2 flex items-center gap-5 bg-gray-50 dark:bg-gray-900/50 p-4 rounded-2xl border border-gray-200 dark:border-gray-700/80">
                                    @if ($photo)
                                        <img src="{{ $photo->temporaryUrl() }}" class="h-16 w-16 object-cover rounded-xl border border-indigo-200 dark:border-indigo-800 shadow-sm shrink-0" />
                                    @elseif ($currentPhotoPath)
                                        <img src="{{ asset('storage/' . $currentPhotoPath) }}" class="h-16 w-16 object-cover rounded-xl border border-indigo-200 dark:border-indigo-800 shadow-sm shrink-0" />
                                    @else
                                        <div class="h-16 w-16 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 flex items-center justify-center text-gray-400 shrink-0 shadow-sm">
                                            <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                        </div>
                                    @endif

                                    <div class="flex-1">
                                        <input type="file" wire:model="photo" id="photo" class="hidden" accept="image/*" />
                                        <label for="photo" class="cursor-pointer inline-flex items-center gap-2 px-4 py-2 border border-gray-300 dark:border-gray-700 hover:bg-white dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300 font-bold text-xs uppercase rounded-xl transition shadow-sm">
                                            <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                                            </svg>
                                            {{ __('Choose Photo') }}
                                        </label>
                                        <p class="text-[10px] text-gray-400 dark:text-gray-500 mt-1.5 font-medium">JPEG, PNG up to 2MB (Square ratio recommended)</p>
                                    </div>
                                </div>
                                <x-input-error :messages="$errors->get('photo')" class="mt-2" />
                            </div>
                        </div>
                    </div>

                    <!-- Sticky Modal Footer with Clear Action Buttons -->
                    <div class="flex items-center justify-end gap-3 p-4 sm:px-8 border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/60 shrink-0">
                        <button type="button" wire:click="$set('isModalOpen', false)" class="px-5 py-2.5 border border-gray-200 dark:border-gray-700 hover:bg-white dark:hover:bg-gray-800 rounded-xl font-bold text-xs uppercase text-gray-700 dark:text-gray-300 transition cursor-pointer">
                            {{ __('Cancel') }}
                        </button>
                        <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-xs uppercase tracking-wider rounded-xl shadow-lg shadow-indigo-600/20 transition cursor-pointer flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span>{{ $studentId ? __('Update Student') : __('Save Student') }}</span>
                        </button>
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
                                {{ __('Delete Student') }}
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                {{ __('Action Confirmation Required') }}
                            </p>
                        </div>
                    </div>

                    <p class="text-xs text-gray-600 dark:text-gray-300 mb-6 leading-relaxed">
                        {{ __('Are you sure you want to permanently delete this student record? This action cannot be undone.') }}
                    </p>

                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                        <button type="button" wire:click="$set('isConfirmDeleteOpen', false)" class="px-5 py-2.5 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/60 rounded-xl font-bold text-xs uppercase text-gray-700 dark:text-gray-300 transition cursor-pointer">
                            {{ __('Cancel') }}
                        </button>
                        <button type="button" wire:click="deleteStudent" class="px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-xl font-bold text-xs uppercase shadow transition cursor-pointer">
                            {{ __('Delete') }}
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
                            <button onclick="window.print()" type="button" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs uppercase tracking-wider rounded-xl transition shadow flex items-center gap-2 cursor-pointer">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                </svg>
                                <span>Print ID Card</span>
                            </button>
                            <button type="button" wire:click="closePreviewIdCard" class="px-5 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-xs uppercase tracking-wider rounded-xl transition cursor-pointer">
                                {{ __('Close') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endif
</div>
