<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use app\Course;
use app\GradeCategory;

class ScriptsController extends Controller
{
    public function CreateGradeCatForCourse(Request $request)
    {
        $allCourse = Course::all();
        foreach($allCourse as $course)
        {
            $gradeCat = GradeCategory::firstOrCreate([
                'name' => $course->name . ' Total',
                'course_id' => $course->id
            ]);
        }

        return 'done';
    }

    public function setLogo(Request $request)
    {
        $request->validate([
            'school_logo' => 'required|mimes::jpg,jpeg,png',
            'school_name' => 'string',
        ]);

        $attachment = attachment::upload_attachment($request->school_logo, 'Logo');
        dd($attachment);

        return $attachment;
    }
}
