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
 
    public function options(Request $request)
    {
        $request->validate(['school_id' => 'required|exists:schools,id']);
        $schoolId = $request->school_id;
        
        $this->checkAccess($schoolId);
 
        $grades = \App\Models\Grade::where('school_id', $schoolId)
            ->with(['divisions'])
            ->get();
            
        $campaigns = \App\Models\Campaign::where('school_id', $schoolId)->get();
 
        return response()->json([
            'grades' => $grades,
            'campaigns' => $campaigns,
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
 

 
        $students = $query->with(['campaignStudents' => function($q) use ($schoolId) {
            $q->whereHas('campaign', function($inner) use ($schoolId) {
                $inner->where('school_id', $schoolId);
            })->with(['grade', 'division', 'campaign']);
        }])->orderBy('created_at', 'desc')->get();
 
        return response()->json($students);
    }
 
    public function saveStudent(Request $request)
    {
        $request->validate([
            'school_id' => 'required|exists:schools,id',
            'student_id' => 'nullable|exists:students,id',
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'campaign_id' => 'required|exists:campaigns,id',
            'grade_id' => 'required|exists:grades,id',
            'division_id' => 'required|exists:divisions,id',
            'blood_group' => 'nullable|string|max:10',
            'dob' => 'required|date',
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
            'blood_group' => $request->blood_group ?: null,
            'dob' => $request->dob,
            'address' => $request->address,
            'pincode' => $request->pincode,
            'contact_number' => $request->contact_number,
        ];
 
        if ($request->photo_path) {
            $studentData['photo_path'] = $request->photo_path;
        }
 
        if ($request->student_id) {
            $student = Student::findOrFail($request->student_id);
            if ($request->photo_path && $student->photo_path && $student->photo_path !== $request->photo_path) {
                if (\Illuminate\Support\Facades\Storage::disk('public')->exists($student->photo_path)) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($student->photo_path);
                }
            }
            $student->update($studentData);
        } else {
            $student = Student::create($studentData);
        }
 
        \App\Models\CampaignStudent::updateOrCreate(
            [
                'campaign_id' => $request->campaign_id,
                'student_id' => $student->id,
            ],
            [
                'grade_id' => $request->grade_id,
                'division_id' => $request->division_id,
            ]
        );
 
        return response()->json($student->load(['campaignStudents' => function($q) use ($schoolId) {
            $q->whereHas('campaign', function($inner) use ($schoolId) {
                $inner->where('school_id', $schoolId);
            })->with(['grade', 'division', 'campaign']);
        }]));
    }
 
    public function deleteStudent(Request $request, string $id)
    {
        $request->validate(['school_id' => 'required|exists:schools,id']);
        $schoolId = $request->school_id;
        $scopes = $this->getPermittedScopes($schoolId);
 
        $student = Student::findOrFail($id);
 
        $enrollment = \App\Models\CampaignStudent::where('student_id', $student->id)
            ->whereHas('campaign', function($q) use ($schoolId) {
                $q->where('school_id', $schoolId);
            })->firstOrFail();
 
        if ($scopes['restricted']) {
            if (!in_array($enrollment->grade_id, $scopes['grades']) || !in_array($enrollment->division_id, $scopes['divisions'])) {
                return response()->json(['message' => 'You do not have permission to delete this student.'], 403);
            }
        }
 
        if ($student->photo_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($student->photo_path)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($student->photo_path);
        }

        $student->delete();
        return response()->json(['success' => true, 'message' => 'Student deleted successfully.']);
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
}
