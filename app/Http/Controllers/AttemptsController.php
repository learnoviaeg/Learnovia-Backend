<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Auth;
use Browser;
use Carbon\Carbon;
use Modules\QuestionBank\Entities\userQuiz;
use Modules\QuestionBank\Entities\quiz;
use Modules\QuestionBank\Entities\QuizLesson;
use Modules\QuestionBank\Entities\QuizOverride;
use App\Http\Controllers\HelperController;
use Modules\QuestionBank\Entities\Questions;
use Modules\QuestionBank\Entities\QuestionsAnswer;
use Modules\QuestionBank\Entities\quiz_questions;
use Modules\QuestionBank\Entities\userQuizAnswer;
use App\LastAction;

class AttemptsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request,$submit=null)
    {
        $request->validate([
            'quiz_id' => 'required|integer|exists:quizzes,id',
            'lesson_id' => 'required|integer|exists:lessons,id',
        ]);

        $user = Auth::User();

        $quiz_lesson = QuizLesson::where('quiz_id', $request->quiz_id)
            ->where('lesson_id', $request->lesson_id)->first();
        
        $duration = $quiz_lesson->quiz->duration;
        
        // return $quiz_lesson->due_date;
        if (!isset($quiz_lesson)) 
            return HelperController::api_response_format(400, null, __('messages.quiz.quiz_not_belong'));

        LastAction::lastActionInCourse($quiz_lesson->lesson->courseSegment->course_id);
        
        $user_quizz=userQuiz::where('quiz_lesson_id', $quiz_lesson->id)->where('user_id', $user->id);
        $max_attempt_index = $user_quizz->get()->max('attempt_index');
        $max_id = $user_quizz->get()->max('id');

        $userQuiz=$user_quizz->first();
        $override_flag = false;
        $attempt_index = 0;
        $override = QuizOverride::where('user_id',$user->id)->where('quiz_lesson_id',$quiz_lesson->id)->first();
        if(isset($override)){
            $override_flag = true;
            if($override->attemps <= $quiz_lesson->max_attemp &&  $override->attemps >= 0  ){
                $max_attempt_index  =  $quiz_lesson->max_attemp - $override->attemps ; 
                if($max_attempt_index==0)
                    $max_attempt_index=null;
            }
        }
        //first attempt
        if ($max_attempt_index == null) {
            $attempt_index = 1;

            //first attempt in case override
            if($override_flag)
            {
                $override->attemps=$override->attemps-1;
                $override->save();
            }
        } else if (isset($userQuiz)) {
            if ($max_attempt_index < $userQuiz->quiz_lesson->max_attemp  || $override_flag ) {
                //When Time finish, he can't enter on same attempt
                if(Carbon::parse($userQuiz->open_time)->addSeconds($duration)->format('Y-m-d H:i:s') <= Carbon::now()->format('Y-m-d H:i:s'))
                {  
                    // $user_quiz_answer=UserQuizAnswer::where('user_quiz_id',$max_id)->whereNull('answered')->get();
                    $user_quiz_answer=UserQuizAnswer::where('user_quiz_id',$max_id)->get();
                    foreach($user_quiz_answer as $user_ans)
                    {
                        if(isset($user_ans)){
                            $user_ans->update([
                                'answered' => 1,
                                'force_submit' => 1,
                            ]);
                        }
                    }
                    //create one more then continue api
                    $attempt_index = ++$max_attempt_index;
                    
                    //in case override
                    if($override_flag)
                    {
                        //case in last attempt in override
                        if($override->attemps == 0)
                            return HelperController::api_response_format(400, null, __('messages.error.submit_limit'));

                        $override->attemps=$override->attemps-1;
                        $override->save();
                    }
                }

                else {
                    $answered=UserQuizAnswer::where('user_quiz_id',$max_id)->whereNull('force_submit')->get()->count();

                    //his time isn't ended, but he submits so he creates one more attempt 
                    if($answered < 1)
                    {  
                        $attempt_index = ++$max_attempt_index;

                        //case override
                        if($override_flag)
                        {
                            //case in last attempt in override
                            if($override->attemps == 0)
                                return HelperController::api_response_format(400, null, __('messages.error.submit_limit'));
                            $override->attemps--;
                            $override->save();
                        }
                    }

                    //his time isn't ended 
                    else
                        return HelperController::api_response_format(200, $userQuiz, __('messages.quiz.continue_quiz'));
                }
            }else {  
                $answ=UserQuizAnswer::where('user_quiz_id',$max_id)->whereNull('force_submit')->get()->count();
                if($answ > 0)
                    return HelperController::api_response_format(200, $userQuiz, __('messages.quiz.continue_quiz'));

                return HelperController::api_response_format(400, null, __('messages.error.submit_limit'));
            }
        }

        $deviceData = collect([]);
        $deviceData->put('isDesktop', Browser::isDesktop());
        $deviceData->put('isMobile', Browser::isMobile());
        $deviceData->put('isTablet', Browser::isTablet());
        $deviceData->put('isBot', Browser::isBot());

        $deviceData->put('platformName', Browser::platformName());
        $deviceData->put('platformFamily', Browser::platformFamily());
        $deviceData->put('platformVersion', Browser::platformVersion());

        $deviceData->put('deviceFamily', Browser::deviceFamily());
        $deviceData->put('deviceModel', Browser::deviceModel());
        $deviceData->put('mobileGrade', Browser::mobileGrade());


        $browserData = collect([]);
        $browserData->put('browserName', Browser::browserName());
        $browserData->put('browserFamily', Browser::browserFamily());
        $browserData->put('browserVersion', Browser::browserVersion());
        $browserData->put('browserEngine', Browser::browserEngine());

        // return $browserData;
        $userQuiz = userQuiz::create([
            'user_id' => $user->id,
            'quiz_lesson_id' => $quiz_lesson->id,
            'status_id' => 2,
            'feedback' => null,
            'grade' => null,
            'attempt_index' => ($user->can('site/quiz/store_user_quiz')) ? $attempt_index : 0,
            'ip' => $request->ip(),
            'device_data' => $deviceData,
            'browser_data' => $browserData,
            'open_time' => Carbon::now()->format('Y-m-d H:i:s'),
            'submit_time'=> null,
        ]);
        
        $end_date = Carbon::parse($userQuiz->open_time)->addSeconds($duration);
        $seconds = $end_date->diffInSeconds(Carbon::now());
        if($seconds < 0) 
            $seconds = 0;
        
        $job = (new \App\Jobs\CloseQuizAttempt($userQuiz))->delay($seconds);
        dispatch($job);

        foreach($quiz_lesson->quiz->Question as $question)
            userQuizAnswer::create(['user_quiz_id'=>$userQuiz->id , 'question_id'=>$question->id]);
        
        return HelperController::api_response_format(200, $userQuiz);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
