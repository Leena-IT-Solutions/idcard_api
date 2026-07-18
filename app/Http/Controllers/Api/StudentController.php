<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Student;

class StudentController extends Controller
{
    public function index()
    {
        return response()->json(Student::all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string',
            'middle_name' => 'nullable|string',
            'last_name' => 'required|string',
            'standard' => 'required|string',
            'division' => 'required|string',
            'blood_group' => 'nullable|string',
            'dob' => 'required|date',
            'address' => 'required|string',
            'pincode' => 'required|string',
            'contact_number' => 'required|string',
            'photo_path' => 'nullable|string',
        ]);

        $student = Student::create($validated);
        return response()->json($student, 201);
    }

    public function show(string $id)
    {
        $student = Student::findOrFail($id);
        return response()->json($student);
    }

    public function update(Request $request, string $id)
    {
        $student = Student::findOrFail($id);
        
        $validated = $request->validate([
            'first_name' => 'sometimes|required|string',
            'middle_name' => 'nullable|string',
            'last_name' => 'sometimes|required|string',
            'standard' => 'sometimes|required|string',
            'division' => 'sometimes|required|string',
            'blood_group' => 'nullable|string',
            'dob' => 'sometimes|required|date',
            'address' => 'sometimes|required|string',
            'pincode' => 'sometimes|required|string',
            'contact_number' => 'sometimes|required|string',
            'photo_path' => 'nullable|string',
        ]);

        $student->update($validated);
        return response()->json($student);
    }

    public function destroy(string $id)
    {
        $student = Student::findOrFail($id);
        $student->delete();
        return response()->json(null, 204);
    }
}
