<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Student;

class StudentController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        if ($user && $user->hasRole('parent')) {
            return response()->json(Student::where('user_id', $user->id)->get());
        }
        return response()->json(Student::all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'blood_group' => 'nullable|string|max:10',
            'dob' => 'required|date',
            'address' => 'required|string',
            'pincode' => 'required|string|max:20',
            'contact_number' => 'required|string|max:20',
            'photo_path' => 'nullable|string',
        ]);

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
            'blood_group' => 'nullable|string|max:10',
            'dob' => 'sometimes|required|date',
            'address' => 'sometimes|required|string',
            'pincode' => 'sometimes|required|string|max:20',
            'contact_number' => 'sometimes|required|string|max:20',
            'photo_path' => 'nullable|string',
        ]);

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

        $student->delete();
        return response()->json(null, 204);
    }
}
