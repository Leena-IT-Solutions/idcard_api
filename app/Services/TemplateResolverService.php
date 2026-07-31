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

        if (!$tpl && $schoolId) {
            $school = School::find($schoolId);
            if ($school) {
                if ($school->school_template_id && ($st = SchoolTemplate::find($school->school_template_id))) {
                    $tpl = $st;
                } elseif ($school->template_id) {
                    if ($st = SchoolTemplate::find($school->template_id)) $tpl = $st;
                    elseif ($mt = Template::find($school->template_id)) $tpl = $mt;
                    elseif ($mt = Template::where('slug', $school->template_id)->first()) $tpl = $mt;
                }
                if (!$tpl) {
                    $tpl = SchoolTemplate::where('school_id', $schoolId)->where('is_default', true)->first();
                }
                if (!$tpl) {
                    $tpl = SchoolTemplate::where('school_id', $schoolId)->first();
                }
            }
        }

        if (!$tpl) {
            $tpl = Template::where('is_active', true)->first() ?: Template::first();
        }


        return $tpl;
    }
}
