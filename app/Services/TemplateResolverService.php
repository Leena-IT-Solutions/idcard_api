<?php

namespace App\Services;

use App\Models\Grade;
use App\Models\School;
use App\Models\SchoolTemplate;
use App\Models\Template;

class TemplateResolverService
{
    public function getEffectiveTemplate($schoolId, $gradeId = null)
    {
        $tpl = null;

        // 1. Grade-level specific override (overrides school template if grade template exists)
        if ($gradeId) {
            $grade = Grade::find($gradeId);
            if ($grade) {
                if ($grade->school_template_id) {
                    $tpl = SchoolTemplate::find($grade->school_template_id);
                }
                
                if (!$tpl && $grade->template_id) {
                    $tpl = SchoolTemplate::find($grade->template_id)
                        ?: Template::find($grade->template_id)
                        ?: Template::where('slug', $grade->template_id)->first();
                }
            }
        }

        // 2. School-level default configuration (used when grade template does not exist)
        if (!$tpl && $schoolId) {
            $school = School::find($schoolId);
            if ($school && $school->template_id) {
                $tpl = SchoolTemplate::find($school->template_id)
                    ?: Template::find($school->template_id)
                    ?: Template::where('slug', $school->template_id)->first();
            }

            if (!$tpl && $schoolId) {
                $tpl = SchoolTemplate::where('school_id', $schoolId)->where('is_default', true)->first();
            }

            if (!$tpl && $schoolId) {
                $tpl = SchoolTemplate::where('school_id', $schoolId)->first();
            }
        }

        // 3. Fallback to active system default master template
        if (!$tpl) {
            $tpl = Template::where('is_active', true)->first() ?: Template::first();
        }

        return $tpl;
    }
}
