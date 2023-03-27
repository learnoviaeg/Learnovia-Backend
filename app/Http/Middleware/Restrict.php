<?php

namespace App\Http\Middleware;

use Closure;
use App\Enroll;
use  App\CourseSegment;
use App\Http\Controllers\HelperController;
use Illuminate\Support\Facades\Auth;

class Restrict
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $request->validate([
            'course_id' => 'required|exists:courses,id',
            'class_id' => 'required|exists:classes,id',
        ]);
        if ($request->user() == null){
            return HelperController::api_response_format(401, [], 'User not logged in');
        }
        if($request->user()->can('site/guard_name')){
            return $next($request);
        }
            $course_segment_ids = CourseSegment::GetWithClassAndCourse($request->class_id , $request->course_id);
            if($course_segment_ids == null)
                return HelperController::api_response_format(403 , null , 'This Course not associated to this class');
            $session_id = $request->user()->id;
            $course_ids = Enroll::whereIn('course_segment',$course_segment_ids)->where('user_id', $session_id)->get();
            if($course_ids){
                return $next($request);
            }
        return HelperController::api_response_format(403 , null , 'This Course is not yours...');
        }
}
