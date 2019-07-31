<?php

namespace Modules\QuestionBank\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Auth;
use Browser;
use Carbon\Carbon;
use Modules\QuestionBank\Entities\userQuiz;
use App\Http\Controllers\HelperController;

class UserQuizController extends Controller
{
   public function store_user_quiz(Request $request){

        $request->validate([
            'quiz_lesson_id' => 'required|integer|exists:quiz_lessons,id',
            'status_id' => 'required|integer|exists:statuses,id',
            'feedback' => 'required|string',
            'grade' => 'required|integer',
        ]);

        $max_attempt_index = userQuiz::where('quiz_lesson_id', $request->quiz_lesson_id)
            ->where('user_id', Auth::user()->id)
            ->get()->max('attempt_index');

        $userQuiz = userQuiz::where('quiz_lesson_id', $request->quiz_lesson_id)
                    ->where('user_id', Auth::user()->id)
                    ->first();

        $attempt_index = 0;
        if($max_attempt_index == null){
            $attempt_index = 1;
        }
        else if(isset($userQuiz)){
            if($max_attempt_index < $userQuiz->quiz_lesson->max_attemp){
                $attempt_index = ++$max_attempt_index;
            }
            else{
                return HelperController::api_response_format(400, null, 'Max Attempt number reached');
            }
        }

        $deviceData = collect([]);
        $deviceData->put('isDesktop',Browser::isDesktop());
        $deviceData->put('isMobile',Browser::isMobile());
        $deviceData->put('isTablet',Browser::isTablet());
        $deviceData->put('isBot',Browser::isBot());

        $deviceData->put('platformName',Browser::platformName());
        $deviceData->put('platformFamily',Browser::platformFamily());
        $deviceData->put('platformVersion',Browser::platformVersion());

        $deviceData->put('deviceFamily',Browser::deviceFamily());
        $deviceData->put('deviceModel',Browser::deviceModel());
        $deviceData->put('mobileGrade',Browser::mobileGrade());


        $browserData = collect([]);
        $browserData->put('browserName',Browser::browserName());
        $browserData->put('browserFamily',Browser::browserFamily());
        $browserData->put('browserVersion',Browser::browserVersion());
        $browserData->put('browserEngine',Browser::browserEngine());

        $userQuiz = userQuiz::create([
            'user_id' => Auth::user()->id,
            'quiz_lesson_id' => $request->quiz_lesson_id,
            'status_id' => $request->status_id,
            'feedback' => $request->feedback,
            'grade' => $request->grade,
            'attempt_index' => $attempt_index,
            'ip' => $request->ip(),
            'device_data' => $deviceData,
            'browser_data' => $browserData,
            'open_time' => Carbon::now()
        ]);

        return HelperController::api_response_format(200, $userQuiz);

   }
}
