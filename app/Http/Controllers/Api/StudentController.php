<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Student;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = Student::query();
        if ($user) {
            $query->where('user_id', $user->id);
        }

        $query->with(['campaignStudents.grade', 'campaignStudents.campaign']);
        
        $perPage = $request->input('per_page', 15);
        $schoolAdminCtrl = new \App\Http\Controllers\Api\SchoolAdminController();

        $mapStudentTemplate = function($student) use ($schoolAdminCtrl) {
            $firstE = $student->campaignStudents ? $student->campaignStudents->first() : null;
            $schoolId = $firstE && $firstE->campaign ? $firstE->campaign->school_id : null;
            $gradeId = $firstE ? $firstE->grade_id : null;
            $tpl = $schoolAdminCtrl->getEffectiveTemplateForGradeOrSchool($schoolId, $gradeId);
            $student->setAttribute('effective_template', $tpl);
            return $student;
        };

        if ($request->has('page')) {
            $studentsPaginator = $query->simplePaginate($perPage);
            $items = collect($studentsPaginator->items())->map($mapStudentTemplate);
            return response()->json($items);
        } else {
            $items = $query->get()->map($mapStudentTemplate);
            return response()->json($items);
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'gender' => 'nullable|string|max:50',
            'blood_group' => 'nullable|string|max:10',
            'dob' => 'nullable|date',
            'address' => 'required|string',
            'pincode' => 'required|string|max:20',
            'contact_number' => 'required|string|max:20',
            'photo_path' => 'nullable|string',
        ]);

        $existing = Student::findExisting(
            $validated['contact_number'],
            $validated['first_name'],
            $validated['last_name'],
            $validated['dob'] ?? null
        );

        // Only reuse the match if it's unowned, or already owned by this same parent.
        // Never attach to a student that belongs to a different parent.
        if ($existing && (is_null($existing->user_id) || $existing->user_id === auth()->id())) {
            if (is_null($existing->user_id)) {
                $existing->update(['user_id' => auth()->id()]);
            }
            return response()->json($existing, 200);
        }

        $validated['user_id'] = auth()->id();

        $student = Student::create($validated);
        return response()->json($student, 201);
    }

    public function show(string $id)
    {
        $user = auth()->user();
        $student = Student::findOrFail($id);

        if ($user && $user->hasRole('parent') && $student->user_id !== $user->id) {
            abort(403, 'Unauthorized access to student record.');
        }

        return response()->json($student);
    }

    public function update(Request $request, string $id)
    {
        $user = auth()->user();
        $student = Student::findOrFail($id);

        if ($user && $user->hasRole('parent') && $student->user_id !== $user->id) {
            abort(403, 'Unauthorized to update student record.');
        }
        
        $validated = $request->validate([
            'first_name' => 'sometimes|required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'gender' => 'nullable|string|max:50',
            'blood_group' => 'nullable|string|max:10',
            'dob' => 'nullable|date',
            'address' => 'sometimes|required|string',
            'pincode' => 'sometimes|required|string|max:20',
            'contact_number' => 'sometimes|required|string|max:20',
            'photo_path' => 'nullable|string',
        ]);

        if (array_key_exists('photo_path', $validated) && $validated['photo_path'] !== $student->photo_path) {
            if ($student->photo_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($student->photo_path)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($student->photo_path);
            }
        }

        $student->update($validated);
        return response()->json($student);
    }

    public function destroy(string $id)
    {
        $user = auth()->user();
        $student = Student::findOrFail($id);

        if ($user && $user->hasRole('parent') && $student->user_id !== $user->id) {
            abort(403, 'Unauthorized to delete student record.');
        }

        // Delete photo if it exists
        if ($student->photo_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($student->photo_path)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($student->photo_path);
        }

        $student->delete();
        return response()->json(null, 204);
    }

    public function uploadPhoto(Request $request)
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:10240',
        ]);

        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('students/photos', 'public');
            return response()->json([
                'success' => true,
                'photo_path' => $path,
                'photo_url' => asset('storage/' . $path),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'No photo file provided.',
        ], 400);
    }

    public function sync(Request $request)
    {
        $user = $request->user();
        $linked = $user->linkUnlinkedStudents();

        return response()->json([
            'linked_count' => $linked,
            'students' => $user->students()->get(),
        ]);
    }
}
