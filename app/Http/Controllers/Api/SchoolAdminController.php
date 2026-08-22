<?php
 
namespace App\Http\Controllers\Api;
 
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Student;
 
class SchoolAdminController extends Controller
{
    private function checkAccess($schoolId)
    {
        $user = auth()->user();
        if ($user->hasRole('saas_admin')) {
            return;
        }
 
        $isSchoolAdmin = \App\Models\SchoolUserRole::where('user_id', $user->id)
            ->where('school_id', $schoolId)
            ->whereHas('role', function($q) { $q->where('slug', 'school_admin'); })
            ->exists();
 
        if (!$isSchoolAdmin) {
            abort(403, 'Unauthorized access to school records.');
        }
    }
 
    private function getPermittedScopes($schoolId)
    {
        $user = auth()->user();
        $isSaasAdmin = $user->hasRole('saas_admin');
        
        $isSchoolAdmin = \App\Models\SchoolUserRole::where('user_id', $user->id)
            ->where('school_id', $schoolId)
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
            ->where('school_id', $schoolId)
            ->whereHas('role', function($q) { $q->where('slug', 'teacher'); })
            ->first();
 
        if (!$teacherRole) {
            abort(403, 'Unauthorized access to school directory.');
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
 
    public function schools()
    {
        $user = auth()->user();
        if ($user->hasRole('saas_admin')) {
            $schools = \App\Models\School::all()->map(function($school) {
                $school->setAttribute('role_slug', 'saas_admin');
                return $school;
            });
            return response()->json($schools);
        }

        $memberships = \App\Models\SchoolUserRole::where('user_id', $user->id)
            ->with('role')
            ->get();

        $schools = \App\Models\School::whereIn('id', $memberships->pluck('school_id')->unique())->get();

        $schools = $schools->map(function($school) use ($memberships) {
            $member = $memberships->where('school_id', $school->id)->first();
            $school->setAttribute('role_slug', $member && $member->role ? $member->role->slug : 'parent');
            return $school;
        });

        return response()->json($schools);
    }
 
    private function formatTemplateForApi($template)
    {
        if (!$template) return null;
        
        $bgImage = $template->background_image;
        if ($bgImage === 'null' || $bgImage === 'undefined' || trim((string)$bgImage) === '') {
            $bgImage = null;
        }

        if (empty($bgImage)) {
            // 1. Check parent master template if this is a SchoolTemplate
            if ($template instanceof \App\Models\SchoolTemplate && !empty($template->template_id)) {
                $master = \App\Models\Template::find($template->template_id)
                    ?: \App\Models\Template::where('slug', $template->template_id)->first();
                if ($master && !empty($master->background_image)) {
                    $bgImage = $master->background_image;
                }
            }

            // 2. Fallback to first available master preset background for orientation
            if (empty($bgImage)) {
                $orientation = $template->orientation ?? 'landscape';
                $defaultMaster = \App\Models\Template::where('orientation', $orientation)
                    ->whereNotNull('background_image')
                    ->where('background_image', '!=', '')
                    ->where('background_image', '!=', 'null')
                    ->first();
                if ($defaultMaster) {
                    $bgImage = $defaultMaster->background_image;
                }
            }
        }

        $template->setAttribute('background_image', $bgImage);
        
        return $template;
    }

    public function getEffectiveTemplateForGradeOrSchool($schoolId, $gradeId = null)
    {
        $resolver = app(\App\Services\TemplateResolverService::class);
        $tpl = $resolver->getEffectiveTemplate($schoolId, $gradeId);
        if ($tpl && $schoolId) {
            $school = \App\Models\School::find($schoolId);
            if ($school) {
                $tpl->setAttribute('school', $school);
            }
        }
        return $this->formatTemplateForApi($tpl);
    }

    public function options(Request $request)
    {
        $request->validate(['school_id' => 'required|exists:schools,id']);
        $schoolId = $request->school_id;
        
        $scopes = $this->getPermittedScopes($schoolId);

        $gradesQuery = \App\Models\Grade::where('school_id', $schoolId);

        if ($scopes['restricted']) {
            $gradesQuery->whereIn('id', $scopes['grades']);
        }

        $grades = $gradesQuery->with(['divisions' => function($q) use ($scopes) {
            if ($scopes['restricted']) {
                $q->whereIn('divisions.id', $scopes['divisions']);
            }
        }])->get();
            
        $campaigns = \App\Models\Campaign::where('school_id', $schoolId)->get();
        $school = \App\Models\School::find($schoolId);
        $effectiveTemplate = $this->getEffectiveTemplateForGradeOrSchool($schoolId);

        $totalStudentsQuery = \App\Models\Student::whereHas('campaignStudents.campaign', function($q) use ($schoolId) {
            $q->where('school_id', $schoolId);
        });
        if ($scopes['restricted']) {
            $totalStudentsQuery->whereHas('campaignStudents', function($q) use ($scopes) {
                $q->whereIn('grade_id', $scopes['grades'])->whereIn('division_id', $scopes['divisions']);
            });
        }
        $totalStudentsCount = $totalStudentsQuery->count();

        // Status counts breakdown
        $statusCountsQuery = \App\Models\CampaignStudent::whereHas('campaign', function($q) use ($schoolId) {
            $q->where('school_id', $schoolId);
        });
        if ($scopes['restricted']) {
            $statusCountsQuery->whereIn('grade_id', $scopes['grades'])->whereIn('division_id', $scopes['divisions']);
        }
        $rawStatusCounts = (clone $statusCountsQuery)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $statusCounts = [
            'drafting' => (int)($rawStatusCounts['drafting'] ?? 0),
            'verified' => (int)($rawStatusCounts['verified'] ?? 0),
            'sent_for_printing' => (int)($rawStatusCounts['sent_for_printing'] ?? 0),
            'printed' => (int)($rawStatusCounts['printed'] ?? 0),
            'distributed' => (int)($rawStatusCounts['distributed'] ?? 0),
        ];

        return response()->json([
            'grades' => $grades,
            'campaigns' => $campaigns,
            'school' => $school,
            'effective_template' => $effectiveTemplate,
            'total_students_count' => $totalStudentsCount,
            'status_counts' => $statusCounts,
            'statuses' => \App\Models\CampaignStudent::STATUSES,
        ]);
    }
 
    public function members(Request $request)
    {
        $request->validate(['school_id' => 'required|exists:schools,id']);
        $schoolId = $request->school_id;
        $this->checkAccess($schoolId);
 
        $query = \App\Models\SchoolUserRole::with(['user', 'role', 'assignments.grade', 'assignments.division'])
            ->where('school_id', $schoolId);
 
        if ($request->search) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%')
                  ->orWhere('mobile', 'like', '%' . $request->search . '%');
            });
        }
 
        return response()->json($query->latest()->get());
    }
 
    public function invitations(Request $request)
    {
        $request->validate(['school_id' => 'required|exists:schools,id']);
        $schoolId = $request->school_id;
        $this->checkAccess($schoolId);
 
        $query = \App\Models\SchoolInvitation::with(['role', 'assignments.grade', 'assignments.division', 'user'])
            ->where('school_id', $schoolId)
            ->where('status', 'pending');
 
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('email', 'like', '%' . $request->search . '%')
                  ->orWhere('mobile', 'like', '%' . $request->search . '%')
                  ->orWhereHas('user', function ($sub) use ($request) {
                      $sub->where('name', 'like', '%' . $request->search . '%');
                  });
            });
        }
 
        return response()->json($query->latest()->get());
    }
 
    public function invite(Request $request)
    {
        $request->validate([
            'school_id' => 'required|exists:schools,id',
            'role_slug' => 'required|in:school_admin,teacher',
            'email' => 'nullable|email|max:255',
            'mobile' => 'nullable|string|max:20',
            'assignments' => 'nullable|array',
            'assignments.*.grade_id' => 'required|exists:grades,id',
            'assignments.*.division_id' => 'required|exists:divisions,id',
        ]);
 
        $schoolId = $request->school_id;
        $this->checkAccess($schoolId);
 
        $role = \App\Models\Role::where('slug', $request->role_slug)->firstOrFail();
 
        if (empty(trim($request->email)) && empty(trim($request->mobile))) {
            return response()->json(['message' => 'Provide email or mobile number.'], 422);
        }
 
        $user = null;
        if (!empty($request->email)) {
            $user = User::where('email', trim($request->email))->first();
        }
        if (!$user && !empty($request->mobile)) {
            $user = User::where('mobile', trim($request->mobile))->first();
        }
 
        if ($user) {
            $alreadyActive = \App\Models\SchoolUserRole::where('school_id', $schoolId)
                ->where('user_id', $user->id)
                ->where('role_id', $role->id)
                ->exists();
 
            if ($alreadyActive) {
                return response()->json(['message' => 'User is already an active member of this school with this role.'], 422);
            }
        }
 
        $pendingQuery = \App\Models\SchoolInvitation::where('school_id', $schoolId)
            ->where('role_id', $role->id)
            ->where('status', 'pending');
 
        if (!empty($request->email)) {
            $pendingQuery->where(function($q) use ($request) {
                $q->where('email', trim($request->email))
                  ->orWhere('mobile', trim($request->mobile));
            });
        } else {
            $pendingQuery->where('mobile', trim($request->mobile));
        }
 
        if ($pendingQuery->exists()) {
            return response()->json(['message' => 'A pending invitation already exists for this contact.'], 422);
        }
 
        $invitation = \App\Models\SchoolInvitation::create([
            'school_id' => $schoolId,
            'role_id' => $role->id,
            'grade_id' => ($request->role_slug === 'teacher' && !empty($request->assignments)) ? $request->assignments[0]['grade_id'] : null,
            'division_id' => ($request->role_slug === 'teacher' && !empty($request->assignments)) ? $request->assignments[0]['division_id'] : null,
            'email' => !empty($request->email) ? trim($request->email) : null,
            'mobile' => !empty($request->mobile) ? trim($request->mobile) : null,
            'user_id' => $user ? $user->id : null,
            'status' => 'pending',
        ]);
 
        if ($request->role_slug === 'teacher' && !empty($request->assignments)) {
            foreach ($request->assignments as $assign) {
                \App\Models\SchoolInvitationAssignment::create([
                    'school_invitation_id' => $invitation->id,
                    'grade_id' => $assign['grade_id'],
                    'division_id' => $assign['division_id'],
                ]);
            }
        }
 
        return response()->json(['success' => true, 'message' => 'Invitation sent successfully.']);
    }
 
    public function deleteMember(string $id)
    {
        $member = \App\Models\SchoolUserRole::findOrFail($id);
        $this->checkAccess($member->school_id);
        $member->delete();
        return response()->json(['success' => true, 'message' => 'Member removed successfully.']);
    }
 
    public function revokeInvitation(string $id)
    {
        $invitation = \App\Models\SchoolInvitation::findOrFail($id);
        $this->checkAccess($invitation->school_id);
        $invitation->delete();
        return response()->json(['success' => true, 'message' => 'Invitation revoked successfully.']);
    }
 
    public function lookupByMobile(Request $request)
    {
        $request->validate([
            'mobile' => 'required|string',
        ]);

        $normalized = \App\Support\PhoneNumber::normalize($request->mobile);
        if (!$normalized || strlen($normalized) < 10) {
            return response()->json([]);
        }

        $students = Student::query()
            ->get()
            ->filter(fn ($s) => \App\Support\PhoneNumber::normalize($s->contact_number) === $normalized)
            ->values();

        return response()->json($students);
    }

    public function students(Request $request)
    {
        $request->validate(['school_id' => 'required|exists:schools,id']);
        $schoolId = $request->school_id;
        
        $scopes = $this->getPermittedScopes($schoolId);
 
        $query = Student::query();
 
        if ($scopes['restricted']) {
            $query->whereHas('campaignStudents', function($q) use ($scopes) {
                $q->whereIn('grade_id', $scopes['grades'])
                  ->whereIn('division_id', $scopes['divisions']);
            });
        }
 
        $query->whereHas('campaignStudents.campaign', function($q) use ($schoolId) {
            $q->where('school_id', $schoolId);
        });
 
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('first_name', 'like', '%' . $request->search . '%')
                  ->orWhere('last_name', 'like', '%' . $request->search . '%')
                  ->orWhere('contact_number', 'like', '%' . $request->search . '%');
            });
        }
 
        if ($request->filter_campaign) {
            $query->whereHas('campaignStudents', function($q) use ($request) {
                $q->where('campaign_id', $request->filter_campaign);
            });
        }
 
        if ($request->filter_grade) {
            $query->whereHas('campaignStudents', function($q) use ($request) {
                $q->where('grade_id', $request->filter_grade);
            });
        }
 
        if ($request->filter_division) {
            $query->whereHas('campaignStudents', function($q) use ($request) {
                $q->where('division_id', $request->filter_division);
            });
        }

        if ($request->filter_status) {
            $query->whereHas('campaignStudents', function($q) use ($request) {
                $q->where('status', $request->filter_status);
            });
        }
 

 
        $perPage = $request->input('per_page', 15);
        $sortBy = $request->input('sort_by', 'name');
        
        if ($sortBy === 'serial_number') {
            $campaignId = $request->filter_campaign;
            
            // Subquery to check if serial number is null or empty (1 if null/empty, 0 if present)
            $nullSortSubquery = \App\Models\CampaignStudent::selectRaw('CASE WHEN serial_number IS NULL OR serial_number = "" THEN 1 ELSE 0 END')
                ->whereColumn('student_id', 'students.id');
            
            // Subquery to get the actual serial number for sorting (cast to unsigned for numeric sorting)
            $orderSubquery = \App\Models\CampaignStudent::selectRaw('CAST(serial_number AS UNSIGNED)')
                ->whereColumn('student_id', 'students.id');

            if ($campaignId) {
                $nullSortSubquery->where('campaign_id', $campaignId);
                $orderSubquery->where('campaign_id', $campaignId);
            } else {
                $nullSortSubquery->join('campaigns', 'campaigns.id', '=', 'campaign_student.campaign_id')
                    ->where('campaigns.school_id', $schoolId)
                    ->orderBy('campaign_student.created_at', 'desc');
                
                $orderSubquery->join('campaigns', 'campaigns.id', '=', 'campaign_student.campaign_id')
                    ->where('campaigns.school_id', $schoolId)
                    ->orderBy('campaign_student.created_at', 'desc');
            }
            $nullSortSubquery->limit(1);
            $orderSubquery->limit(1);

            $query->orderBy($nullSortSubquery, 'asc')
                ->orderBy($orderSubquery, 'asc')
                ->orderBy('first_name', 'asc')
                ->orderBy('last_name', 'asc');
        } else {
            $query->orderBy('first_name', 'asc')
                ->orderBy('last_name', 'asc');
        }

        $mapStudentTemplate = function($student) use ($schoolId, $request) {
            $firstE = $student->campaignStudents ? $student->campaignStudents->first() : null;
            $gradeId = $request->filter_grade ?: ($firstE ? $firstE->grade_id : null);
            $tpl = $this->getEffectiveTemplateForGradeOrSchool($schoolId, $gradeId);
            $student->setAttribute('effective_template', $tpl);
            return $student;
        };

        $totalMatchingCount = (clone $query)->count();

        if ($request->has('page')) {
            $studentsPaginator = $query->with(['campaignStudents' => function($q) use ($schoolId) {
                $q->whereHas('campaign', function($inner) use ($schoolId) {
                    $inner->where('school_id', $schoolId);
                })->with(['grade', 'division', 'campaign', 'verifier']);
            }])->simplePaginate($perPage);
            
            $items = collect($studentsPaginator->items())->map($mapStudentTemplate);
            return response()->json([
                'data' => $items,
                'total' => $totalMatchingCount,
                'per_page' => (int)$perPage,
                'current_page' => (int)$request->page,
            ])->header('X-Total-Count', $totalMatchingCount);
        } else {
            $students = $query->with(['campaignStudents' => function($q) use ($schoolId) {
                $q->whereHas('campaign', function($inner) use ($schoolId) {
                    $inner->where('school_id', $schoolId);
                })->with(['grade', 'division', 'campaign', 'verifier']);
            }])->get()->map($mapStudentTemplate);
            
            return response()->json([
                'data' => $students,
                'total' => $totalMatchingCount,
            ])->header('X-Total-Count', $totalMatchingCount);
        }
    }
 
    public function saveStudent(Request $request)
    {
        $request->validate([
            'school_id' => 'required|exists:schools,id',
            'student_id' => 'nullable|exists:students,id',
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'roll_no' => 'nullable|string|max:100',
            'campaign_id' => 'required|exists:campaigns,id',
            'grade_id' => 'required|exists:grades,id',
            'division_id' => 'required|exists:divisions,id',
            'serial_number' => 'nullable|string|max:100',
            'blood_group' => 'nullable|string|max:10',
            'gender' => 'nullable|string|max:50',
            'dob' => 'nullable|date',
            'address' => 'required|string',
            'pincode' => 'required|string|max:20',
            'contact_number' => 'required|string|max:20',
            'photo_path' => 'nullable|string',
        ]);
 
        $schoolId = $request->school_id;
        $scopes = $this->getPermittedScopes($schoolId);
 
        if ($scopes['restricted']) {
            if (!in_array($request->grade_id, $scopes['grades']) || !in_array($request->division_id, $scopes['divisions'])) {
                return response()->json(['message' => 'You do not have permission to assign students to this grade/division.'], 403);
            }
        }
 
        $studentData = [
            'first_name' => $request->first_name,
            'middle_name' => $request->middle_name ?: null,
            'last_name' => $request->last_name,
            'gender' => $request->gender ?: null,
            'blood_group' => $request->blood_group ?: null,
            'dob' => $request->dob ?: null,
            'address' => $request->address,
            'pincode' => $request->pincode,
            'contact_number' => $request->contact_number,
        ];
 
        if ($request->photo_path) {
            $studentData['photo_path'] = $request->photo_path;
        }
 
        // Check if student is already enrolled in the selected campaign
        $existingCheck = Student::findExisting($request->contact_number, $request->first_name, $request->last_name, $request->dob);
        if ($existingCheck && (!$request->student_id || (string)$existingCheck->id !== (string)$request->student_id)) {
            $alreadyEnrolled = \App\Models\CampaignStudent::where('campaign_id', $request->campaign_id)
                ->where('student_id', $existingCheck->id)
                ->exists();

            if ($alreadyEnrolled) {
                return response()->json([
                    'message' => 'This student is already enrolled in the selected campaign.',
                    'errors' => [
                        'campaign_id' => ['This student is already enrolled in the selected campaign.']
                    ]
                ], 422);
            }
        }

        $wasMatched = false;

        if ($request->student_id) {
            $student = Student::findOrFail($request->student_id);
            if ($request->photo_path && $student->photo_path && $student->photo_path !== $request->photo_path) {
                if (\Illuminate\Support\Facades\Storage::disk('public')->exists($student->photo_path)) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($student->photo_path);
                }
            }
            $student->update($studentData);
        } else {
            $student = Student::findExisting($request->contact_number, $request->first_name, $request->last_name, $request->dob);

            if ($student) {
                $wasMatched = true;
                $student->update($studentData);
            } else {
                $student = Student::create($studentData);
            }
        }

        $student->attemptParentLink();
 
        \App\Models\CampaignStudent::updateOrCreate(
            [
                'campaign_id' => $request->campaign_id,
                'student_id' => $student->id,
            ],
            [
                'grade_id' => $request->grade_id,
                'division_id' => $request->division_id,
                'roll_no' => $request->roll_no ?: null,
                'serial_number' => $request->serial_number ?: null,
            ]
        );
 
        $student->load(['campaignStudents' => function($q) use ($schoolId) {
            $q->whereHas('campaign', function($inner) use ($schoolId) {
                $inner->where('school_id', $schoolId);
            })->with(['grade', 'division', 'campaign', 'verifier']);
        }]);

        $firstE = $student->campaignStudents ? $student->campaignStudents->first() : null;
        $gradeId = $request->grade_id ?: ($firstE ? $firstE->grade_id : null);
        $tpl = $this->getEffectiveTemplateForGradeOrSchool($schoolId, $gradeId);
        $student->setAttribute('effective_template', $tpl);
        $student->setAttribute('matched_existing', $wasMatched);

        return response()->json($student);
    }
 
    public function deleteStudent(Request $request, string $id)
    {
        $request->validate(['school_id' => 'required|exists:schools,id']);
        $schoolId = $request->school_id;
        $scopes = $this->getPermittedScopes($schoolId);

        $student = Student::findOrFail($id);

        $query = \App\Models\CampaignStudent::where('student_id', $student->id);

        if ($request->has('campaign_id') && $request->campaign_id) {
            $query->where('campaign_id', $request->campaign_id);
        } else {
            $query->whereHas('campaign', function($q) use ($schoolId) {
                $q->where('school_id', $schoolId);
            });
        }

        $enrollments = $query->get();

        if ($enrollments->isEmpty()) {
            return response()->json(['message' => 'No active enrollment found for this campaign.'], 404);
        }

        if ($scopes['restricted']) {
            foreach ($enrollments as $enrollment) {
                if (!in_array($enrollment->grade_id, $scopes['grades']) || !in_array($enrollment->division_id, $scopes['divisions'])) {
                    return response()->json(['message' => 'You do not have permission to remove this student enrollment.'], 403);
                }
            }
        }

        foreach ($enrollments as $enrollment) {
            $enrollment->delete();
        }

        return response()->json(['success' => true, 'message' => 'Student enrollment removed from campaign successfully.']);
    }

    public function verifyStudent(Request $request, string $id)
    {
        $request->validate([
            'school_id' => 'required|exists:schools,id',
            'campaign_id' => 'required|exists:campaigns,id',
        ]);

        $schoolId = $request->school_id;
        $scopes = $this->getPermittedScopes($schoolId);

        $enrollment = \App\Models\CampaignStudent::where('student_id', $id)
            ->where('campaign_id', $request->campaign_id)
            ->whereHas('campaign', function ($q) use ($schoolId) {
                $q->where('school_id', $schoolId);
            })
            ->firstOrFail();

        if ($scopes['restricted']) {
            if (!in_array($enrollment->grade_id, $scopes['grades']) || !in_array($enrollment->division_id, $scopes['divisions'])) {
                return response()->json(['message' => 'You do not have permission to verify students in this grade/division.'], 403);
            }
        }

        $enrollment->update([
            'verified_at' => now(),
            'verified_by' => auth()->id(),
            'status' => \App\Models\CampaignStudent::STATUS_VERIFIED,
            'status_updated_at' => now(),
            'status_updated_by' => auth()->id(),
        ]);

        return response()->json($enrollment->load(['grade', 'division', 'campaign', 'verifier', 'statusUpdater']));
    }

    public function unverifyStudent(Request $request, string $id)
    {
        $request->validate([
            'school_id' => 'required|exists:schools,id',
            'campaign_id' => 'required|exists:campaigns,id',
        ]);

        $schoolId = $request->school_id;
        $scopes = $this->getPermittedScopes($schoolId);

        $enrollment = \App\Models\CampaignStudent::where('student_id', $id)
            ->where('campaign_id', $request->campaign_id)
            ->whereHas('campaign', function ($q) use ($schoolId) {
                $q->where('school_id', $schoolId);
            })
            ->firstOrFail();

        if ($scopes['restricted']) {
            if (!in_array($enrollment->grade_id, $scopes['grades']) || !in_array($enrollment->division_id, $scopes['divisions'])) {
                return response()->json(['message' => 'You do not have permission to verify students in this grade/division.'], 403);
            }
        }

        $enrollment->update([
            'verified_at' => null,
            'verified_by' => null,
            'status' => \App\Models\CampaignStudent::STATUS_DRAFTING,
            'status_updated_at' => now(),
            'status_updated_by' => auth()->id(),
        ]);

        return response()->json($enrollment->load(['grade', 'division', 'campaign', 'statusUpdater']));
    }

    public function updateStudentStatus(Request $request, string $id)
    {
        $request->validate([
            'school_id' => 'required|exists:schools,id',
            'campaign_id' => 'required|exists:campaigns,id',
            'status' => 'required|in:drafting,verified,sent_for_printing,printed,distributed',
        ]);

        $schoolId = $request->school_id;
        $scopes = $this->getPermittedScopes($schoolId);

        $enrollment = \App\Models\CampaignStudent::where('student_id', $id)
            ->where('campaign_id', $request->campaign_id)
            ->whereHas('campaign', function ($q) use ($schoolId) {
                $q->where('school_id', $schoolId);
            })
            ->firstOrFail();

        if ($scopes['restricted']) {
            if (!in_array($enrollment->grade_id, $scopes['grades']) || !in_array($enrollment->division_id, $scopes['divisions'])) {
                return response()->json(['message' => 'You do not have permission to update student status in this grade/division.'], 403);
            }
        }

        $updateData = [
            'status' => $request->status,
            'status_updated_at' => now(),
            'status_updated_by' => auth()->id(),
        ];

        if ($request->status === \App\Models\CampaignStudent::STATUS_VERIFIED && !$enrollment->verified_at) {
            $updateData['verified_at'] = now();
            $updateData['verified_by'] = auth()->id();
        } elseif ($request->status === \App\Models\CampaignStudent::STATUS_DRAFTING && $enrollment->verified_at) {
            $updateData['verified_at'] = null;
            $updateData['verified_by'] = null;
        }

        $enrollment->update($updateData);

        return response()->json([
            'success' => true,
            'enrollment' => $enrollment->fresh(['grade', 'division', 'campaign', 'verifier', 'statusUpdater']),
        ]);
    }

    public function bulkUpdateStudentStatus(Request $request)
    {
        $request->validate([
            'school_id' => 'required|exists:schools,id',
            'campaign_id' => 'required|exists:campaigns,id',
            'status' => 'required|in:drafting,verified,sent_for_printing,printed,distributed',
            'student_ids' => 'nullable|array',
            'student_ids.*' => 'exists:students,id',
            'grade_id' => 'nullable|exists:grades,id',
            'division_id' => 'nullable|exists:divisions,id',
        ]);

        $schoolId = $request->school_id;
        $scopes = $this->getPermittedScopes($schoolId);

        $query = \App\Models\CampaignStudent::where('campaign_id', $request->campaign_id)
            ->whereHas('campaign', function ($q) use ($schoolId) {
                $q->where('school_id', $schoolId);
            });

        if ($scopes['restricted']) {
            $query->whereIn('grade_id', $scopes['grades'])
                ->whereIn('division_id', $scopes['divisions']);
        }

        if (!empty($request->student_ids)) {
            $query->whereIn('student_id', $request->student_ids);
        } else {
            if ($request->grade_id) {
                $query->where('grade_id', $request->grade_id);
            }
            if ($request->division_id) {
                $query->where('division_id', $request->division_id);
            }
        }

        $updateData = [
            'status' => $request->status,
            'status_updated_at' => now(),
            'status_updated_by' => auth()->id(),
        ];

        if ($request->status === \App\Models\CampaignStudent::STATUS_VERIFIED) {
            $updateData['verified_at'] = now();
            $updateData['verified_by'] = auth()->id();
        } elseif ($request->status === \App\Models\CampaignStudent::STATUS_DRAFTING) {
            $updateData['verified_at'] = null;
            $updateData['verified_by'] = null;
        }

        $count = $query->update($updateData);

        return response()->json([
            'success' => true,
            'updated_count' => $count,
            'status' => $request->status,
        ]);
    }

    public function updateMember(Request $request, string $id)
    {
        $request->validate([
            'school_id' => 'required|exists:schools,id',
            'role_slug' => 'required|in:school_admin,teacher',
            'assignments' => 'nullable|array',
            'assignments.*.grade_id' => 'required|exists:grades,id',
            'assignments.*.division_id' => 'required|exists:divisions,id',
        ]);

        $schoolId = $request->school_id;
        $this->checkAccess($schoolId);

        $member = \App\Models\SchoolUserRole::findOrFail($id);
        $role = \App\Models\Role::where('slug', $request->role_slug)->firstOrFail();

        $member->update(['role_id' => $role->id]);

        // Sync class assignments for teachers
        $member->assignments()->delete();
        if ($request->role_slug === 'teacher' && !empty($request->assignments)) {
            foreach ($request->assignments as $assign) {
                \App\Models\SchoolUserRoleAssignment::create([
                    'school_user_role_id' => $member->id,
                    'grade_id' => $assign['grade_id'],
                    'division_id' => $assign['division_id'],
                ]);
            }
        }

        return response()->json(['success' => true, 'message' => 'Member updated successfully.']);
    }

    public function userInvitations()
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json([]);
        }

        $invitations = \App\Models\SchoolInvitation::with(['school', 'role', 'assignments.grade', 'assignments.division'])
            ->where('status', 'pending')
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id);
                if ($user->email) {
                    $q->orWhere('email', $user->email);
                }
                if ($user->mobile) {
                    $q->orWhere('mobile', $user->mobile);
                }
            })
            ->get();

        return response()->json($invitations);
    }

    public function acceptUserInvitation(string $id)
    {
        $user = auth()->user();
        $invite = \App\Models\SchoolInvitation::with('assignments')->findOrFail($id);

        // Check duplicate mapping
        $exists = \App\Models\SchoolUserRole::where('user_id', $user->id)
            ->where('school_id', $invite->school_id)
            ->where('role_id', $invite->role_id)
            ->exists();

        if (!$exists) {
            // 1. Create mapping under school user roles
            $userRole = \App\Models\SchoolUserRole::create([
                'user_id' => $user->id,
                'school_id' => $invite->school_id,
                'role_id' => $invite->role_id,
                'grade_id' => $invite->grade_id,
                'division_id' => $invite->division_id,
            ]);

            foreach ($invite->assignments as $asg) {
                \App\Models\SchoolUserRoleAssignment::create([
                    'school_user_role_id' => $userRole->id,
                    'grade_id' => $asg->grade_id,
                    'division_id' => $asg->division_id,
                ]);
            }

            // 2. Sync to user's standard roles pivot table
            $user->roles()->syncWithoutDetaching([$invite->role_id]);
        }

        // 3. Mark invite as accepted
        $invite->update([
            'status' => 'accepted',
            'user_id' => $user->id,
        ]);

        return response()->json(['success' => true, 'message' => 'Invitation accepted successfully.']);
    }

    public function declineUserInvitation(string $id)
    {
        $user = auth()->user();
        $invite = \App\Models\SchoolInvitation::findOrFail($id);

        // Mark invite as declined
        $invite->update([
            'status' => 'declined',
            'user_id' => $user->id,
        ]);

        return response()->json(['success' => true, 'message' => 'Invitation declined successfully.']);
    }

    public function exportSingleStudentPdf(Request $request, string $id)
    {
        $request->validate([
            'school_id' => 'required|exists:schools,id',
            'campaign_id' => 'nullable|exists:campaigns,id',
        ]);

        $schoolId = $request->school_id;
        $campaignId = $request->campaign_id;
        $permitted = $this->getPermittedScopes($schoolId);

        $student = \App\Models\Student::findOrFail($id);

        $enrollment = \App\Models\CampaignStudent::where('student_id', $student->id)
            ->when($campaignId, fn($q) => $q->where('campaign_id', $campaignId))
            ->when($permitted['restricted'], function($q) use ($permitted) {
                $q->where(function($sub) use ($permitted) {
                    foreach ($permitted['assignments'] as $asg) {
                        $sub->orWhere(function($sq) use ($asg) {
                            $sq->where('grade_id', $asg['grade_id']);
                            if (!empty($asg['division_id'])) {
                                $sq->where('division_id', $asg['division_id']);
                            }
                        });
                    }
                });
            })
            ->with(['grade', 'division', 'verifier', 'campaign'])
            ->first();

        if ($permitted['restricted'] && !$enrollment) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access to student scope.'], 403);
        }

        if ($enrollment) {
            $student->setRelation('campaignStudents', collect([$enrollment]));
        }

        $school = \App\Models\School::findOrFail($schoolId);
        $templateResolver = new \App\Services\TemplateResolverService();
        $template = $templateResolver->getEffectiveTemplate($schoolId, $enrollment?->grade_id);

        $cardRenderer = new \App\Services\CardRenderService();
        $html = $cardRenderer->renderFrontHtml($template, $student, $school);

        $orientation = $template->orientation ?? 'landscape';
        $isPortrait = $orientation === 'portrait';
        $cardWidthMm = $isPortrait ? 54.0 : 85.6;
        $cardHeightMm = $isPortrait ? 85.6 : 54.0;

        $pdf = $cardRenderer->toPdf($html, $cardWidthMm, $cardHeightMm);

        $schoolCode = preg_replace('/[^A-Za-z0-9_-]/', '', $school->school_code ?? $school->name ?? 'SCHOOL');

        $name = preg_replace('/[^A-Za-z0-9_-]/', '_', trim("{$student->first_name}_{$student->last_name}"));
        $filename = "{$schoolCode}_{$name}_idcard.pdf";

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function exportSingleStudentPng(Request $request, string $id)
    {
        $request->validate([
            'school_id' => 'required|exists:schools,id',
            'campaign_id' => 'nullable|exists:campaigns,id',
        ]);

        $schoolId = $request->school_id;
        $campaignId = $request->campaign_id;
        $permitted = $this->getPermittedScopes($schoolId);

        $student = \App\Models\Student::findOrFail($id);

        $enrollment = \App\Models\CampaignStudent::where('student_id', $student->id)
            ->when($campaignId, fn($q) => $q->where('campaign_id', $campaignId))
            ->when($permitted['restricted'], function($q) use ($permitted) {
                $q->where(function($sub) use ($permitted) {
                    foreach ($permitted['assignments'] as $asg) {
                        $sub->orWhere(function($sq) use ($asg) {
                            $sq->where('grade_id', $asg['grade_id']);
                            if (!empty($asg['division_id'])) {
                                $sq->where('division_id', $asg['division_id']);
                            }
                        });
                    }
                });
            })
            ->with(['grade', 'division', 'verifier', 'campaign'])
            ->first();

        if ($permitted['restricted'] && !$enrollment) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access to student scope.'], 403);
        }

        if ($enrollment) {
            $student->setRelation('campaignStudents', collect([$enrollment]));
        }

        $school = \App\Models\School::findOrFail($schoolId);
        $templateResolver = new \App\Services\TemplateResolverService();
        $template = $templateResolver->getEffectiveTemplate($schoolId, $enrollment?->grade_id);

        $cardRenderer = new \App\Services\CardRenderService();
        $html = $cardRenderer->renderFrontHtml($template, $student, $school);

        $orientation = $template->orientation ?? 'landscape';
        $isPortrait = $orientation === 'portrait';
        $widthPx = $isPortrait ? 638 : 1011;
        $heightPx = $isPortrait ? 1011 : 638;

        $png = $cardRenderer->toPng($html, $widthPx, $heightPx);

        $schoolCode = preg_replace('/[^A-Za-z0-9_-]/', '', $school->school_code ?? $school->name ?? 'SCHOOL');

        $name = preg_replace('/[^A-Za-z0-9_-]/', '_', trim("{$student->first_name}_{$student->last_name}"));
        $filename = "{$schoolCode}_{$name}_idcard.png";

        return response($png, 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function createExport(Request $request)
    {
        $request->validate([
            'school_id' => 'required|exists:schools,id',
            'type' => 'required|in:excel_photo_zip,png_zip,single_card_pdf,imposition_pdf',
            'campaign_id' => 'nullable|exists:campaigns,id',
            'student_ids' => 'nullable|array',
            'student_ids.*' => 'exists:students,id',
            'page_size' => 'nullable|string',
            'custom_width_mm' => 'nullable|numeric',
            'custom_height_mm' => 'nullable|numeric',
            'bleed_mm' => 'nullable|numeric',
            'margin_mm' => 'nullable|numeric',
            'gutter_mm' => 'nullable|numeric',
        ]);

        $schoolId = $request->school_id;
        $campaignId = $request->campaign_id;
        $user = auth()->user();

        $permitted = $this->getPermittedScopes($schoolId);

        $query = \App\Models\CampaignStudent::whereHas('campaign', fn($q) => $q->where('school_id', $schoolId))
            ->when($campaignId, fn($q) => $q->where('campaign_id', $campaignId));

        if ($request->filled('student_ids')) {
            $query->whereIn('student_id', $request->student_ids);
        }

        if ($permitted['restricted']) {
            $query->where(function($sub) use ($permitted) {
                foreach ($permitted['assignments'] as $asg) {
                    $sub->orWhere(function($sq) use ($asg) {
                        $sq->where('grade_id', $asg['grade_id']);
                        if (!empty($asg['division_id'])) {
                            $sq->where('division_id', $asg['division_id']);
                        }
                    });
                }
            });
        }

        $targetStudentIds = $query->pluck('student_id')->unique()->values()->all();

        if (empty($targetStudentIds)) {
            return response()->json(['success' => false, 'message' => 'No eligible students found for export scope.'], 422);
        }

        // Credit balance verification (1 Credit = 1 Student ID Card)
        $school = \App\Models\School::findOrFail($schoolId);
        $neededCredits = count($targetStudentIds);

        if (!$school->hasCredits($neededCredits)) {
            return response()->json([
                'success' => false,
                'message' => "Insufficient wallet credits. This export requires {$neededCredits} credits for {$neededCredits} students, but your current balance is {$school->credits_balance} credits. Please recharge your wallet.",
                'credits_needed' => $neededCredits,
                'credits_available' => $school->credits_balance,
            ], 402);
        }

        $params = [
            'campaign_id' => $campaignId,
            'student_ids' => $targetStudentIds,
            'page_size' => $request->page_size ?? 'A4',
            'custom_width_mm' => $request->custom_width_mm,
            'custom_height_mm' => $request->custom_height_mm,
            'bleed_mm' => $request->bleed_mm ?? 3.0,
            'margin_mm' => $request->margin_mm ?? 3.0,
            'gutter_mm' => $request->gutter_mm ?? 6.0,
        ];

        $export = \App\Models\Export::create([
            'user_id' => $user->id,
            'school_id' => $schoolId,
            'type' => $request->type,
            'status' => 'pending',
            'params' => $params,
            'total_items' => count($targetStudentIds),
            'processed_items' => 0,
        ]);

        try {
            if ($request->boolean('sync', true)) {
                match ($request->type) {
                    'excel_photo_zip' => \App\Jobs\ExportExcelPhotoZipJob::dispatchSync($export->id),
                    'png_zip' => \App\Jobs\ExportPngZipJob::dispatchSync($export->id),
                    'single_card_pdf' => \App\Jobs\ExportSingleCardPdfJob::dispatchSync($export->id),
                    'imposition_pdf' => \App\Jobs\ExportImpositionPdfJob::dispatchSync($export->id),
                };
            } else {
                match ($request->type) {
                    'excel_photo_zip' => \App\Jobs\ExportExcelPhotoZipJob::dispatch($export->id),
                    'png_zip' => \App\Jobs\ExportPngZipJob::dispatch($export->id),
                    'single_card_pdf' => \App\Jobs\ExportSingleCardPdfJob::dispatch($export->id),
                    'imposition_pdf' => \App\Jobs\ExportImpositionPdfJob::dispatch($export->id),
                };
            }

            // Deduct credits on successful dispatch/completion
            $school->deductCredits(
                $neededCredits,
                "Export: {$request->type} — {$neededCredits} student cards (Export #{$export->id})",
                $export,
                $user
            );
        } catch (\Throwable $e) {
            $export->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Export task executed successfully.',
            'export' => $export->fresh(),
            'credits_remaining' => $school->fresh()->credits_balance,
        ]);

    }

    public function listExports(Request $request)
    {
        $request->validate(['school_id' => 'required|exists:schools,id']);
        $user = auth()->user();

        $exports = \App\Models\Export::where('school_id', $request->school_id)
            ->where('user_id', $user->id)
            ->orderBy('id', 'desc')
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'exports' => $exports,
        ]);
    }

    public function deleteExport(Request $request, \App\Models\Export $export)
    {
        $request->validate(['school_id' => 'required|exists:schools,id']);
        $user = auth()->user();

        if ($export->school_id != $request->school_id || $export->user_id != $user->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access to export record.'], 403);
        }

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

        return response()->json([
            'success' => true,
            'message' => 'Export deleted successfully.',
        ]);
    }


    public function downloadExport(\App\Models\Export $export)
    {
        $user = auth()->user();

        if ($export->user_id !== $user->id) {
            $permitted = $this->getPermittedScopes($export->school_id);
            if ($permitted['restricted']) {
                abort(403, 'Unauthorized access to export file.');
            }
        }

        if ($export->status !== 'completed' || !$export->file_path) {
            abort(404, 'Export file not ready or failed.');
        }

        if (!\Illuminate\Support\Facades\Storage::disk('local')->exists($export->file_path)) {
            abort(404, 'Export file not found on disk.');
        }

        return \Illuminate\Support\Facades\Storage::disk('local')->download($export->file_path);
    }
}
