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

        // 1. Grade-level specific override
        if ($gradeId) {
            $grade = Grade::find($gradeId);
            if ($grade) {
                if ($grade->school_template_id && ($st = SchoolTemplate::find($grade->school_template_id))) {
                    $tpl = $st;
                } elseif ($grade->template_id) {
                    if ($st = SchoolTemplate::find($grade->template_id)) $tpl = $st;
                    elseif ($mt = Template::find($grade->template_id)) $tpl = $mt;
                    elseif ($mt = Template::where('slug', $grade->template_id)->first()) $tpl = $mt;
                }
            }
        }

        // 2. School-level default configuration
        if (!$tpl && $schoolId) {
            $school = School::find($schoolId);
            if ($school && $school->template_id) {
                if ($st = SchoolTemplate::find($school->template_id)) {
                    $tpl = $st;
                } elseif ($mt = Template::find($school->template_id)) {
                    $tpl = $mt;
                } elseif ($mt = Template::where('slug', $school->template_id)->first()) {
                    $tpl = $mt;
                }
            }

            if (!$tpl && $schoolId) {
                $tpl = SchoolTemplate::where('school_id', $schoolId)->where('is_default', true)->first();
            }

            if (!$tpl && $schoolId) {
                $tpl = SchoolTemplate::where('school_id', $schoolId)->first();
            }
        }

        // 3. Fallback to system default template
        if (!$tpl) {
            $tpl = Template::where('is_active', true)->first() ?: Template::first();
        }

        return $tpl;
    }
}
