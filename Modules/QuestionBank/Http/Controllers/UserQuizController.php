<?php

namespace Modules\QuestionBank\Http\Controllers;

use App\GradeCategory;
use App\GradeItems;
use App\User;
use App\Enroll;
use App\Lesson;
use App\UserGrade;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Auth;
use Browser;
use App\Events\ManualCorrectionEvent;
use App\Events\GradeAttemptEvent;
use Carbon\Carbon;
use Modules\QuestionBank\Entities\userQuiz;
use Modules\QuestionBank\Entities\quiz;
use Modules\QuestionBank\Entities\QuizLesson;
use Modules\QuestionBank\Entities\QuizOverride;
use App\Http\Controllers\HelperController;
use Modules\QuestionBank\Entities\Questions;
use Modules\QuestionBank\Entities\quiz_questions;
use Modules\QuestionBank\Entities\userQuizAnswer;
use App\LastAction;
use App\ItemDetail;
use App\UserGrader;
use App\ItemDetailsUser;

use function Opis\Closure\serialize;

class UserQuizController extends Controller
{
    public function store_user_quiz(Request $request,$submit=null)
    {
        $request->validate([
            'quiz_id' => 'required|integer|exists:quizzes,id',
            'lesson_id' => 'required|integer|exists:lessons,id',
        ]);

        $user = Auth::User();

        $quiz_lesson = QuizLesson::where('quiz_id', $request->quiz_id)->where('lesson_id', $request->lesson_id)->first();
        
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
        if($override->attemps == 0 || $override->start_date >  Carbon::now() || (Carbon::now() > $override->due_date))
            return HelperController::api_response_format(400, null, __('messages.error.submit_limit'));

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
                    userQuizAnswer::where('user_quiz_id',$max_id)->update(['force_submit'=>'1','answered' => 1]);

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
                    if($answered < 1){  
                        $attempt_index = ++$max_attempt_index;

                        //case override
                        if($override_flag){
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
        {
            if($question->question_type_id == 5)
            {
                $quest=$question->children->pluck('id');
                foreach($quest as $child)
                    userQuizAnswer::create(['user_quiz_id'=>$userQuiz->id , 'question_id'=>$child]);
            }
            userQuizAnswer::create(['user_quiz_id'=>$userQuiz->id , 'question_id'=>$question->id]);
        }
        
        return HelperController::api_response_format(200, $userQuiz);
    }

    public function quiz_answer(Request $request)
    {
        $request->validate([
            'user_quiz_id' => 'required|integer|exists:user_quizzes,id',
            'Questions' => 'required|array',
            'Questions.*.id' => 'required|integer|exists:questions,id',
            'forced' => 'boolean',
        ]);

        // check that question exist in the Quiz
        $user_quiz = userQuiz::find($request->user_quiz_id);
        
        LastAction::lastActionInCourse($user_quiz->quiz_lesson->lesson->courseSegment->course_id);

        $allData = collect([]);
        foreach ($request->Questions as $index => $question) {
            if(isset($question['id'])){
                $currentQuestion = Questions::find($question['id']);
                $question_type_id = $currentQuestion->question_type->id;

                $data = [
                    'user_quiz_id' => $request->user_quiz_id,
                    'question_id' => $question['id'],
                    'answered' => 1
                ];
                switch ($question_type_id) {
                    case 1: // True_false
                        # code...
                        $t_f['is_true'] = isset($question['is_true']) ? $question['is_true']: null;
                        $t_f['and_why'] = isset($question['and_why']) ? $question['and_why']: null;
                        $data['user_answers'] = json_encode($t_f);
                        break;
        
                    case 2: // MCQ
                        $data['user_answers'] = isset($question['MCQ_Choices']) ? json_encode($question['MCQ_Choices']) : null;
                        break;
        
                    case 3: // Match
                        $match['match_a']=isset($question['match_a']) ? $question['match_a'] : null;
                        $match['match_b']=isset($question['match_b']) ? $question['match_b'] : null;
                        $data['user_answers'] = json_encode($match);
                        break;
        
                    case 4: // Essay
                        $data['user_answers'] = isset($question['content']) ? $question['content'] : null; //essay not have special answer
                        break;
                }
                $allData->push($data);
            }
            $answer1= userQuizAnswer::where('user_quiz_id',$request->user_quiz_id)->where('question_id',$question['id'])->first();
            if(isset($answer1))
                $answer1->update($data);
        }

        if($request->forced){
            $answer2=userQuizAnswer::where('user_quiz_id',$request->user_quiz_id)->get();
            foreach($answer2 as $ans){
                $ans->update(['answered'=>'1']);
                $ans->update(['force_submit'=>'1']);
            }
            
            $user_quiz->submit_time=Carbon::now()->format('Y-m-d H:i:s');
            $user_quiz->save();
        }

        return HelperController::api_response_format(200, $allData, __('messages.success.submit_success'));
    }


    public function estimateEssayandAndWhy(Request $request)
    {
        $request->validate([
            'user_quiz_id' => 'required|integer|exists:user_quizzes,id',
            'Questions' => 'required|array',
            'Questions.*.id' => 'required|integer|exists:questions,id',
            'Questions.*.mark' => 'required|numeric',
            'Questions.*.feedback' => 'nullable|string',
            'Questions.*.right' => 'boolean',
        ]);

        $user_quiz = userQuiz::find($request->user_quiz_id);
        $allData = collect([]);
        $Corrected_answers = collect([]);

        foreach ($request->Questions as $question) {
            $currentQuestion = Questions::find($question['id']);
            $question_type_id = $currentQuestion->question_type->id;
            $quiz_questions = quiz_questions::where('question_id',$question['id'])->
            where('quiz_id',$user_quiz->quiz_lesson->quiz_id)->first()->grade_details;
            $data = [
                'question_id' => $question['id']
            ];

            $userQuizAnswer = userQuizAnswer::where('user_quiz_id', $request->user_quiz_id)
                ->where('question_id', $question['id'])->first();
            if (!isset($userQuizAnswer))
                return HelperController::api_response_format(400, null, __('messages.error.not_found'));
                
            if (isset($question_type_id)) {
                $grade_cat=GradeCategory::where('instance_type','Quiz')->where('instance_id',$user_quiz->quiz_lesson->quiz_id)->where('lesson_id',$user_quiz->quiz_lesson->lesson_id)->first();
                $gradeitem=GradeItems::where('index',$user_quiz->attempt_index)->where('grade_category_id',$grade_cat->id)->first();
                $item_details=ItemDetail::where('parent_item_id',$gradeitem->id)->where('item_id',$question['id'])->where('type','Question')->first();
                switch ($question_type_id) {
                    case 1: // True_false
                        if ($currentQuestion->content->and_why == true) {
                            if ($quiz_questions->and_why_mark < $question['mark'])
                                return response()->json(['message' => __('messages.error.grade_less_than').$quiz_questions->and_why_mark , 'body' => null ], 400);
                            if($userQuizAnswer['correction'] != null){
                                $correction = $userQuizAnswer['correction'];
                                $correction->and_why_right = isset($question['right']) ? $question['right'] : null;
                                $correction->and_why_mark = isset($question['mark']) ? $question['mark'] : null;
                                $correction->grade =$question['mark'] + $correction->mark;
                                $correction->feedback = isset($question['feedback']) ? $question['feedback'] : null;
                            }else{
                                $correction = collect([
                                    'and_why_right' => isset($question['right']) ? $question['right'] : null,
                                    'and_why_mark' => isset($question['mark']) ? $question['mark'] : null,
                                    'grade' => $question['mark'],
                                    'feedback' => isset($question['feedback']) ? $question['feedback'] : null
                                ]);
                            }
                            ItemDetailsUser::updateOrCreate([
                                'user_id'=>Auth::id(),
                                'item_details_id' => $item_details->id,
                                'Answers_Correction' => json_encode($correction),
                                'grade' => ($correction instanceof \Illuminate\Database\Eloquent\Collection) ? $correction['grade'] : $correction->grade,
                            ]);
                            $data['correction'] =  json_encode($correction);
                        }
                        else
                            $data = null;
                        break;

                    case 4: // Essay
                        if ($quiz_questions->total_mark < $question['mark'])
                            return response()->json(['message' => __('messages.error.grade_less_than').$quiz_questions->total_mark, 'body' => null ], 400);
                        $correct['right'] = isset($question['right']) ? $question['right'] : null;
                        $correct['grade'] = isset($question['mark']) ? $question['mark'] : null;
                        $correct['feedback'] = isset($question['feedback']) ? $question['feedback'] : null;
                        $data['correction'] =  json_encode($correct);

                        ItemDetailsUser::updateOrCreate([
                            'user_id'=>Auth::id(),
                            'item_details_id' => $item_details->id,
                            'Answers_Correction' => json_encode($correct),
                            'grade' => $question['mark'],
                        ]);

                        break;

                    default:
                        return response()->json(['message' =>__('messages.question.question_type_error'), 'body' => null ], 400);

                        break;
                }

                if ($data != null)
                    $allData->push($data);
            } else
                return response()->json(['message' =>__('messages.error.incomplete_data'), 'body' => null ], 400);
        }
        $attemp=$user_quiz;

        foreach ($allData as $data) {
            $userAnswer = userQuizAnswer::where('user_quiz_id', $request->user_quiz_id)
                ->where('question_id', $data['question_id'])->first();
                $Corrected_answers->push($userAnswer);

            if(isset($data['correction']))
                $userAnswer->correction = $data['correction'];
            $userAnswer->save();
        }
        $count_q_needGraded=0;
        $count_q_Graded=0;
        foreach($user_quiz->UserQuizAnswer as $UA){
            $qqq=Questions::find($UA->question_id);
            if(($qqq->question_type_id == 1 && $qqq->content->and_why) || $qqq->question_type_id == 4){
                $count_q_needGraded++;
                if(isset($UA->correction->grade)){
                    $count_q_Graded++;
                }
            }
        }
        if($count_q_needGraded == $count_q_Graded)        
            $user_quiz->update(['status'=>'Graded']);
        event(new GradeAttemptEvent($attemp));
        // event(new ManualCorrectionEvent($attemp));

        return response()->json(['message' =>__('messages.grade.graded'), 'body' => $Corrected_answers ], 200);
    }

    public function gradeUserQuiz(Request $request)
    {
        $request->validate([
            'user_quiz_id' =>'required|integer|exists:user_quizzes,id',
            'grade' => 'required',
            'feedback' => 'string'
        ]);
        $userQuiz = userQuiz::find($request->user_quiz_id);
        $quiz_lesson = QuizLesson::find($userQuiz->quiz_lesson_id);
        if ($quiz_lesson->grade < $request->grade)
            return HelperController::api_response_format(400, $body = [], $message = __('messages.error.grade_less_than') . $quiz_lesson->grade);

        $userQuiz->grade = $request->grade;
        $userQuiz->status_id = 1;
        $userQuiz->save();
        $grade = userQuiz::calculate_grade_of_attempts_with_method($quiz_lesson->id);
        $grade_item= GradeItems::where('item_type',1)->where('item_Entity',$quiz_lesson->id)->first();
        if (isset($request->feedback)) 
            $userQuiz->feedback = $request->feedback;
        
        $user_grade =UserGrade::create([
            'grade_item_id'=>$grade_item->id,
            'user_id'=>$userQuiz->user_id,
            'raw_grade_max'=>$grade_item->grademax,
            'raw_grade_min'=>$grade_item->grademin,
            'raw_scale_id'=>$grade_item->scale_id,
            'final_grade'=>$grade
        ]);
        if (isset($request->feedback)) {
            $userQuiz->feedback = $request->feedback;
            $user_grade->update(['feedback'=> $request->feedback]);
        }
        return HelperController::api_response_format(200, $body = $user_grade, $message = __('messages.grade.graded'));
    }

    public function feedback(Request $request){
        $request->validate([
            'lesson_id' =>'required|integer|exists:lessons,id',
            'quiz_id' =>'required|integer|exists:quizzes,id',
            'user_id' =>'required|integer|exists:users,id',
        ]);
        $quiz = quiz::where('id',$request->quiz_id)->first();
        $quiz_lesson = QuizLesson::where('quiz_id',$request->quiz_id)->where('lesson_id',$request->lesson_id)->first();
        $user_quiz = userQuiz::where('quiz_lesson_id',$quiz_lesson->id)->where('user_id',$request->user_id)->get();
        if($user_quiz ->count() == 0){
            return HelperController::api_response_format(200, __('messages.error.no_available_data'));}
        $Due_date= QuizLesson::find($quiz_lesson->id);
        $show_is_true=1;
        if($quiz->feedback == 3)
            return HelperController::api_response_format(200, Null , __('messages.error.cannot_see_feedback'));
        elseif(($quiz->feedback == 1 || $quiz_lesson->max_attemp == 1) && $quiz->is_graded == 0 )
           $Final= self::get_feedback($request,$show_is_true   , $user_quiz);
        elseif($quiz->feedback == 2 && Carbon::now() > $Due_date->due_date)
            $Final= self::get_feedback($request,$show_is_true  , $user_quiz);
        elseif($quiz->feedback == 2 && Carbon::now() < $Due_date->due_date && $quiz->is_graded == 0 )
            $Final= self::get_feedback($request,$show_is_true, $user_quiz);
        else{
            $show_is_true=0;
            $Final= self::get_feedback($request,$show_is_true, $user_quiz);
            }
        return HelperController::api_response_format(200, $Final);
    }

    public function get_feedback($request,$show_is_true , $user_quiz){
        foreach($user_quiz as $UserQuiz){
            $total[]= quiz::where('id',$request->quiz_id)->first();
            foreach($total as $quest){
                foreach($quest->question as $q){
                    $Question_id =  $q->pivot->question_id;
                    $Ans_ID = userQuizAnswer::where('user_quiz_id',$UserQuiz->id)->where('question_id',$Question_id)->first();
                    if(isset($Ans_ID->answer_id)){
                        $q->user_grade =$Ans_ID->user_grade;
                    }
                }
            }
        }
        return $total;
    }
}
