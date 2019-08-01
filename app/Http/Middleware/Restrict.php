<?php

namespace App\Http\Middleware;

use Closure;
use App\Enroll;
use  App\CourseSegment;
use App\Http\Controllers\HelperController;
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
            'Course_ID' => 'required|exists:courses,id'
        ]);
        if ($request->user() == null)
            return HelperController::api_response_format(401, [], 'User not logged in');

        $session_id = $request->user()->id;
        $course_segment_ids = Enroll::where('user_id', $session_id)->where('role_id', 4)->pluck('course_segment');
        foreach ($course_segment_ids as $course_segment_id) {
            $couse_id = CourseSegment::where('id', $course_segment_id)->pluck('course_id')->first();
            if ($couse_id == $request->Course_ID) {
                return $next($request);
            }
        }
        return HelperController::api_response_format(403 , null , 'This Course is not yours...');
        }
}
