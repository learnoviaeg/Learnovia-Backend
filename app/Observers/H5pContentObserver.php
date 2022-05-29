<?php

namespace App\Observers;

use App\h5pLesson;
use App\AuditLog;
use Carbon\Carbon;
use Djoudi\LaravelH5p\Eloquents\H5pContent;
use App\Lesson as Lessonmodel;
use App\Course;
use App\Segment;
use Auth;
use App\User;

class H5pContentObserver
{
    public static function get_year_name($subject_id)
    {
        $course_id    = Self::get_course_name($subject_id);
        $segment_id   = Course::where('id', $course_id)->first()->segment_id;
        $segment      = Segment::where('id', $segment_id)->first();
        $academic_year_id = $segment->academic_year_id;
        return $academic_year_id;
    }

    public static function get_type_name($subject_id)
    {
        $course_id    = Self::get_course_name($subject_id);
        $segment_id   = Course::where('id', $course_id)->first()->segment_id;
        $segment      = Segment::where('id', $segment_id)->first();
        $academic_type_id = $segment->academic_type_id;
        return $academic_type_id;
    }

    public static function get_level_name($subject_id)
    {
        $course_id    = Self::get_course_name($subject_id);
        $level_id     = Course::where('id', $course_id)->first()->level_id;
        return $level_id;
    }

    public static function get_class_name($subject_id)
    {
        $course_id    = Self::get_course_name($subject_id);
        $classes      = Course::where('id', $course_id)->first()->classes;
        return $classes;
    }

    public static function get_segment_name($subject_id)
    {
        $course_id    = Self::get_course_name($subject_id);
        $segment_id   = Course::where('id', $course_id)->first()->segment_id;
        return $segment_id;
    }

    public static function get_course_name($subject_id)
    {
        $lesson_id  = h5pLesson::where('content_id', $subject_id)->first()->lesson_id;
        $course_id  = Lessonmodel::where('id', $lesson_id)->first()->course_id;
        return $course_id;
    }

	public function common($action, $subject_id, $now, $before, $item_name, $item_id, $hole_description)
	{
		AuditLog::create([
                'action'           => $action,
                'subject_id'       => $subject_id,
                'subject_type'     => 'H5pContent',
                'user_id'          => auth()->id() ?? null,
                'role_id'          => auth()->id() ? auth()->user()->roles->pluck('id')->toArray() : null,
                'properties'       => $now,
                'host'             => request()->ip() ?? null,
                'year_id'          => Self::get_year_name($subject_id), 
                'type_id'          => Self::get_type_name($subject_id), 
                'level_id'         => Self::get_level_name($subject_id), 
                'class_id'         => Self::get_class_name($subject_id), 
                'segment_id'       => Self::get_segment_name($subject_id), 
                'course_id'        => Self::get_course_name($subject_id), 
                'before'           => $before,
                'created_at'       => Carbon::now(),
                'notes'            => null,
                'item_name'        => $item_name,
                'item_id'          => $item_id,
                'hole_description' => $hole_description,
            ]);
	}
    /**
     * Handle the h5p lesson "created" event.
     *
     * @param  \App\h5pLesson  $h5pLesson
     * @return void
     */
    public function created(H5pContent $h5pLesson)
    {  
        $user_fullname = User::find(Auth::guard('api')->id());
    	//$user_fullname = $user->fullname;  
    	$action           = 'created';
    	$subject_id       = $h5pLesson->id;
    	$hole_description = 'Item in module H5pContent has been 
                created by ( '. $user_fullname. ' )';
    	$item_id   = null;
    	$item_name = $h5pLesson->title;
    	$before = $h5pLesson->getOriginal();
    	$now    = $h5pLesson;
    	Self::common($action, $subject_id, $now, $before, $item_name, $item_id, $hole_description);
    }

    /**
     * Handle the h5p lesson "updated" event.
     *
     * @param  \App\h5pLesson  $h5pLesson
     * @return void
     */
    public function updated(H5pContent $h5pLesson)
    {  
        $user_fullname = User::find(Auth::guard('api')->id());
        //$user_fullname = $user->fullname;   
    	$action           = 'updated';
    	$subject_id       = $h5pLesson->id;
    	$hole_description = 'Item in module H5pContent has been 
                updated by ( '. $user_fullname. ' )';
    	$item_id   = null;
    	$item_name = $h5pLesson->title;
    	$before = $h5pLesson->getOriginal();
    	$now    = $h5pLesson;
    	Self::common($action, $subject_id, $now, $before, $item_name, $item_id, $hole_description);
    }

    /**
     * Handle the h5p lesson "deleted" event.
     *
     * @param  \App\h5pLesson  $h5pLesson
     * @return void
     */
    public function deleted(H5pContent $h5pLesson)
    {  
        $user_fullname = User::find(Auth::guard('api')->id());
        // $user_fullname = $user->fullname;  
    	$action           = 'deleted';
    	$subject_id       = $h5pLesson->id;
    	$hole_description = 'Item in module H5pContent has been 
                deleted by ( '. $user_fullname. ' )';
    	$item_id   = null;
    	$item_name = $h5pLesson->title;
    	$before = $h5pLesson->getOriginal();
    	$now    = $h5pLesson;
    	Self::common($action, $subject_id, $now, $before, $item_name, $item_id, $hole_description);
    }
}
