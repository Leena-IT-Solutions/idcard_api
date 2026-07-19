<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Campaign;
use App\Models\CampaignStudent;
use App\Models\ParentAccess;
use App\Models\Student;
use App\Models\Grade;
use App\Models\Division;

class CampaignController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json([]);
        }

        $mobile = $user->mobile;
        if (!$mobile) {
            return response()->json([]);
        }

        // Find schools that authorized this mobile number
        $schoolIds = ParentAccess::where('mobile', $mobile)->pluck('school_id');

        if ($schoolIds->isEmpty()) {
            return response()->json([]);
        }

        // Get campaigns for these schools
        $campaigns = Campaign::whereIn('school_id', $schoolIds)
            ->with(['school'])
            ->orderBy('registration_end_date', 'asc')
            ->get();

        return response()->json($campaigns);
    }

    public function options(string $campaignId)
    {
        $campaign = Campaign::findOrFail($campaignId);
        
        $grades = Grade::where('school_id', $campaign->school_id)
            ->with(['divisions'])
            ->get();

        return response()->json([
            'grades' => $grades
        ]);
    }

    public function enroll(Request $request)
    {
        $request->validate([
            'campaign_id' => 'required|exists:campaigns,id',
            'student_id' => 'required|exists:students,id',
            'grade_id' => 'required|exists:grades,id',
            'division_id' => 'required|exists:divisions,id',
        ]);

        $campaign = Campaign::findOrFail($request->campaign_id);
        
        // Security check: ensure student belongs to current user
        $student = Student::where('user_id', auth()->id())->findOrFail($request->student_id);
        $grade = Grade::where('school_id', $campaign->school_id)->findOrFail($request->grade_id);
        $division = Division::where('grade_id', $grade->id)->findOrFail($request->division_id);

        $enrollment = CampaignStudent::updateOrCreate(
            [
                'campaign_id' => $campaign->id,
                'student_id' => $student->id,
            ],
            [
                'grade_id' => $grade->id,
                'division_id' => $division->id,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Student enrolled successfully',
            'enrollment' => $enrollment
        ]);
    }

    public function enrollments()
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json([]);
        }

        $studentIds = Student::where('user_id', $user->id)->pluck('id');

        $enrollments = CampaignStudent::whereIn('student_id', $studentIds)
            ->with(['student', 'grade', 'division'])
            ->get();

        return response()->json($enrollments);
    }
}
