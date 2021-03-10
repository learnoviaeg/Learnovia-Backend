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
use function Opis\Closure\serialize;

class UserQuizController extends Controller
{
    public function store_user_quiz(Request $request)
    {
        $user = Auth::User();

        $request->validate([
            'quiz_id' => 'required|integer|exists:quizzes,id',
            'lesson_id' => 'required|integer|exists:lessons,id',
        ]);

        $quiz_lesson = QuizLesson::where('quiz_id', $request->quiz_id)
            ->where('lesson_id', $request->lesson_id)->first();
        
        $quiz =Quiz::find($request->quiz_id);
        $quiz_duration=$quiz->duration;
        
        // return $quiz_lesson->due_date;
        if (!isset($quiz_lesson)) {
            return HelperController::api_response_format(400, null, __('messages.quiz.quiz_not_belong'));
        }
        // if($quiz_lesson->due_date < Carbon::now()->format('Y-m-d H:i:s'))
        //     return HelperController::api_response_format(400, null, 'Time is out');
        $lesson = Lesson::find($request->lesson_id);
        LastAction::lastActionInCourse( $lesson->courseSegment->course_id);
        $max_attempt_index = userQuiz::where('quiz_lesson_id', $quiz_lesson->id)
            ->where('user_id', Auth::user()->id)
            ->get()->max('attempt_index');

        $max_id = userQuiz::where('quiz_lesson_id', $quiz_lesson->id)
            ->where('user_id', Auth::user()->id)
            ->get()->max('id');
        $userQuiz=userQuiz::find($max_id);
        $override_flag = false;
        $attempt_index = 0;
        $override = QuizOverride::where('user_id',Auth::user()->id)->where('quiz_lesson_id',$quiz_lesson->id)->first();
        if(isset($override)){
            $override_flag = true;
            if($override->attemps <= $quiz_lesson->max_attemp &&  $override->attemps >= 0  ){
                $max_attempt_index  =  $quiz_lesson->max_attemp - $override->attemps ; 
                if($max_attempt_index==0)
                {    
                    $max_attempt_index=null;
                }                    
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
                if(Carbon::parse($userQuiz->open_time)->addSeconds($quiz_duration)->format('Y-m-d H:i:s') <= Carbon::now()->format('Y-m-d H:i:s'))
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
            } else {  
                $answ=UserQuizAnswer::where('user_quiz_id',$max_id)->whereNull('force_submit')->get()->count();
                if($answ > 0){
                    return HelperController::api_response_format(200, $userQuiz, __('messages.quiz.continue_quiz'));
                }
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
            'user_id' => Auth::user()->id,
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
        
        $end_date = Carbon::parse($userQuiz->open_time)->addSeconds($quiz_duration);
        $seconds = $end_date->diffInSeconds(Carbon::now());
        if($seconds < 0) {
            $seconds = 0;
        }
        $job = (new \App\Jobs\CloseQuizAttempt($userQuiz))->delay($seconds);
        dispatch($job);

        foreach($quiz_lesson->quiz->Question as $question){
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
        $Q_IDS= array();
        // check that question exist in the Quiz
        $user_quiz = userQuiz::find($request->user_quiz_id);
        $questions_ids = $user_quiz->quiz_lesson->quiz->Question->pluck('id');
        // if(count($request->Questions)<0){
        //     $answer2=userQuizAnswer::where('user_quiz_id',$request->user_quiz_id)->whereIn('question_id',$questions_ids)->get();

        // foreach($answer2 as $ans)
        //     $ans->update(['answered'=>'1']);

        // return HelperController::api_response_format(200, $answer2, 'Quiz Answers are Registered Successfully(forced)');
        // }
        
        //to last action in course
        $Quizlesson = QuizLesson::find($user_quiz->quiz_lesson_id);
        $lesson = Lesson :: find($Quizlesson->lesson_id);
        LastAction::lastActionInCourse($lesson->courseSegment->course_id);
        $allData = collect([]);
        foreach ($request->Questions as $index => $question) {
            if(isset($question['id'])){
            // if (!$questions_ids->contains($question['id'])) {

            //     $check_question = Questions::find($question['id']);

                // if (isset($check_question->parent)) {
                //     if (!$questions_ids->contains($check_question->parent)) {
                //         return HelperController::api_response_format(400, null, 'This Question didn\'t exists in the quiz');
                //     }
                // } else {
                //     return HelperController::api_response_format(400, null, 'This Question didn\'t exists in the quiz');
                // }
            // }

            $currentQuestion = Questions::find($question['id']);
            $question_type_id = $currentQuestion->question_type->id;
            $question_answers = $currentQuestion->question_answer->pluck('id');

            $data = [
                'user_quiz_id' => $request->user_quiz_id,
                'question_id' => $question['id'],
                'answered' => 1,
                // 'force_submit' => 1,
            ];
            array_push($Q_IDS, $question['id']);
            if (isset($question_type_id)) {
                switch ($question_type_id) {
                    case 1: // True_false
                        # code...
                        $request->validate([
                            'Questions.' . $index . '.answer_id' => 'required|integer|exists:questions_answers,id',
                            'Questions.' . $index . '.and_why' => 'nullable|string',
                        ]);

                        if (!$question_answers->contains($question['answer_id'])) {
                            return HelperController::api_response_format(400, $question['answer_id'], __('messages.answer.not_belong_to_question'));
                        }

                        $answer = QuestionsAnswer::find($question['answer_id']);
                        $data['answer_id'] = $question['answer_id'];
                        $data['user_grade'] = ($answer->is_true == 1) ? $currentQuestion->mark : 0;
                        if (isset($question['and_why']))
                            $data['and_why'] = $question['and_why'];
                        break;

                    case 2: // MCQ
                        # code...
                        $request->validate([
                            'Questions.' . $index . '.mcq_answers_array' => 'required|array',
                            'Questions.' . $index . '.mcq_answers_array.*' => 'required|integer|exists:questions_answers,id'
                        ]);

                        $flag = 1;
                        foreach ($question['mcq_answers_array'] as $mcq_answer) {
                            if (!$question_answers->contains($mcq_answer)) {
                                return HelperController::api_response_format(400, null, __('messages.answer.not_belong_to_question'));
                            }
                            $answer = QuestionsAnswer::find($mcq_answer);
                            if ($answer->is_true == 0) {
                                $flag = 0;
                            }
                        }
                        $data['user_grade'] = ($flag == 1) ? $currentQuestion->mark : 0;

                        $data['mcq_answers_array'] = serialize($question['mcq_answers_array']);
                        break;

                    case 3: // Match
                        # code...
                        $request->validate([
                            'Questions.' . $index . '.choices_array' => 'required|array',
                            'Questions.' . $index . '.choices_array.*' => 'required|integer|exists:questions_answers,id',
                        ]);

                        $trueAnswer = $currentQuestion->question_answer->where('is_true', 1)->pluck('id');

                        if (count($question['choices_array']) != count($trueAnswer)) {//must submit all choices
                            return HelperController::api_response_format(400, null, __('messages.error.incomplete_data'));
                        }
                        $true_counter = 0;
                        $false_counter = 0;
                        foreach ($question['choices_array'] as $choice) {
                            if (!$question_answers->contains($choice)) {
                                return HelperController::api_response_format(400, null,__('messages.answer.not_belong_to_question'));
                            }
                            $answer = QuestionsAnswer::find($choice);
                            if ($answer->is_true == 0) {
                                $false_counter++;
                            } else {
                                $true_counter++;
                            }
                        }
                        $question_mark = (float)$currentQuestion->mark;
                        $multiple = (float)$true_counter * (float)$question_mark;
                        $grade = (float)$multiple / (float)count($trueAnswer);
                        $data['user_grade'] = $grade;

                        $data['choices_array'] = serialize($question['choices_array']);
                        break;

                    case 4: // Essay
                        # code...
                        $request->validate([
                            'Questions.' . $index . '.content' => 'required|string',
                        ]);
                        $data['content'] = $question['content'];
                        break;

                    case 5: // Paragraph
                        # code...
                        break;
                }

                $allData->push($data);
            } else {//must enter question type
                return HelperController::api_response_format(400, null, __('messages.error.incomplete_data'));
            }
        }
        $answer1= userQuizAnswer::where('user_quiz_id',$request->user_quiz_id)->where('question_id',$question['id'])->first();
        if(isset($answer1))
            $answer1->update($data);
        }

        if($request->forced)
        {
            // $answer2=userQuizAnswer::where('user_quiz_id',$request->user_quiz_id)->whereNotIn('question_id',$Q_IDS)->get();
            $answer2=userQuizAnswer::where('user_quiz_id',$request->user_quiz_id)->get();

            foreach($answer2 as $ans)
            {
                $ans->update(['answered'=>'1']);
                $ans->update(['force_submit'=>'1']);
            }
            
            $user_quiz->submit_time=Carbon::now()->format('Y-m-d H:i:s');
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
            'Questions.*.answer' => 'boolean',
            'Questions.*.right' => 'boolean',
        ]);

        // check that question exist in the Quiz
        $user_quiz = userQuiz::find($request->user_quiz_id);
        $questions_ids = $user_quiz->quiz_lesson->quiz->Question->pluck('id');

        $allData = collect([]);
        foreach ($request->Questions as $question) {

            if (!$questions_ids->contains($question['id'])) {

                $check_question = Questions::find($question['id']);

                if (isset($check_question->parent)) {
                    if (!$questions_ids->contains($check_question->parent)) {
                        return HelperController::api_response_format(400, null, __('messages.error.not_found'));
                    }
                } else {
                    return HelperController::api_response_format(400, null, __('messages.error.not_found'));
                }
            }

            $currentQuestion = Questions::find($question['id']);
            $question_type_id = $currentQuestion->question_type->id;

            $data = [
                'question_id' => $question['id']
            ];

            $userQuizAnswer = userQuizAnswer::where('user_quiz_id', $request->user_quiz_id)
                ->where('question_id', $question['id'])->first();

            if (!isset($userQuizAnswer)) {
                return HelperController::api_response_format(400, null, __('messages.error.not_found'));
            }

            if (isset($question_type_id)) {
                switch ($question_type_id) {
                    case 1: // True_false
                        # code...

                        if (isset($currentQuestion->And_why)) {
                            if ($currentQuestion->And_why_mark < $question['mark']) {
                                return HelperController::api_response_format(400, null, __('messages.error.grade_less_than').$question['mark']);
                            }

                            $user_grade = userQuizAnswer::where('user_quiz_id', $request->user_quiz_id)
                                ->where('question_id', $question['id'])->pluck('user_grade')->first();

                            $data['user_grade'] = (float)$user_grade + (float)$question['mark'];
                            $data['feedback'] = isset($question['feedback']) ? $question['feedback'] : 'and_why question was corrected';
                        } else {
                            $data = null;
                        }
                        break;


                    case 4: // Essay
                        # code...

                        if ($currentQuestion->mark < $question['mark']) {
                            return HelperController::api_response_format(400, null, __('messages.error.grade_less_than').$question['mark']);
                        }

                        $data['user_grade'] = $question['mark'];
                        $data['correct'] = isset($question['right']) ? 1 : null;//isset($question['answer']) ? $question['answer'] : null;
                        $data['feedback'] = isset($question['feedback']) ? $question['feedback'] : null;
                        //to know if teacher refers to that answer as right or wrong
                        $data['right'] = isset($question['right']) ? $question['right'] : null;

                        break;

                    default:
                        return HelperController::api_response_format(400, null, __('messages.question.question_type_error'));
                        break;

                }

                if ($data != null) {
                    $allData->push($data);
                }
            } else {
                return HelperController::api_response_format(400, null, __('messages.error.incomplete_data'));
            }

        }
        foreach ($allData as $data) {
            $userAnswer = userQuizAnswer::where('user_quiz_id', $request->user_quiz_id)
                ->where('question_id', $data['question_id'])->first();

            $userAnswer->user_grade = $data['user_grade'];
            if(isset($data['feedback']))
                $userAnswer->feedback = $data['feedback'];
            if(isset($data['correct']))
                $userAnswer->correct = $data['correct'];
            if(isset($data['right']))
                $userAnswer->right = $data['right'];

            $userAnswer->save();
        }

        return HelperController::api_response_format(200, $allData, __('messages.grade.graded'));
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
        if ($quiz_lesson->grade < $request->grade) {
            return HelperController::api_response_format(400, $body = [], $message = __('messages.error.grade_less_than') . $quiz_lesson->grade);
        }

        $userQuiz->grade = $request->grade;
        $userQuiz->status_id = 1;
        $userQuiz->save();
        $grade = userQuiz::calculate_grade_of_attempts_with_method($quiz_lesson->id);
         $grade_item= GradeItems::where('item_type',1)->where('item_Entity',$quiz_lesson->id)->first();
        if (isset($request->feedback)) {
            $userQuiz->feedback = $request->feedback;
        }
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

    public function get_all_users_quiz_attempts(Request $request)
    {
        $request->validate([
            'quiz_id' => 'required|integer|exists:quizzes,id',
            'lesson_id' => 'required|integer|exists:lessons,id',
            'user_id' => 'integer|exists:users,id',
        ]);

        $final= collect([]);
        $all_users = array();
        $user_attempts = array();
        $quiz_questions = quiz_questions::where('quiz_id',$request->quiz_id)->pluck('question_id');
        $essayQues = Questions::whereIn('id',$quiz_questions)->where('question_type_id',4)->pluck('id');
        if(count($essayQues) > 0)
            $essay = 1;
        else
            $essay = 0;
        
        $quiz_lesson = QuizLesson::where('quiz_id', $request->quiz_id)->where('lesson_id', $request->lesson_id)->first();
        if(!$quiz_lesson)
            return HelperController::api_response_format(200, null, __('messages.error.not_found'));

        $quiz_duration_ended=false;
        if(Carbon::parse($quiz_lesson->due_date)->format('Y-m-d H:i:s') <= Carbon::now()->format('Y-m-d H:i:s')){
            $quiz_duration_ended=true;
        }
        $users=Enroll::where('course_segment',Lesson::find($request->lesson_id)->course_segment_id)->where('role_id',3)->pluck('user_id')->toArray();

        // $Submitted_users = userQuiz::where('quiz_lesson_id', $quiz_lesson->id)->distinct('user_id')->pluck('id')->count();

        if (!isset($quiz_lesson))
            return HelperController::api_response_format(400, null, __('messages.quiz.quiz_not_belong'));

        if($request->filled('user_id')){
            unset($users);
            $users = userQuiz::where('quiz_lesson_id', $quiz_lesson->id)->where('user_id',$request->user_id)->pluck('user_id')->unique();
            if(count ($users) == 0)
                return HelperController::api_response_format(200, __('messages.error.user_not_assign'));
        }
        $allUserQuizzes = userQuiz::whereIn('user_id', $users)->where('quiz_lesson_id', $quiz_lesson->id)->pluck('id')->unique();
        // return ($allUserQuizzes);

        //count attempts NotGraded
        $userEssayCheckAnswer=UserQuizAnswer::whereIn('user_quiz_id',$allUserQuizzes)->whereIn('question_id',$essayQues)
                                                ->whereNull('correct')->where('answered',1)->where('force_submit',1)->pluck('user_quiz_id');
        // $countOfNotGraded = userQuizAnswer::whereIn('user_quiz_id',$allUserQuizzes)->whereIn('question_id',$essayQues)->where('answered',1)->where('user_grade', null)->count();
        $countOfNotGraded = count($userEssayCheckAnswer);
        
        $Submitted_users=0;
        foreach ($users as $user_id){
            $i=0;
            $All_attemp=[];
            $user = User::find($user_id);
            if($user == null)
            {
                unset($user);
                continue;
            }
            // if (!$user->can('site/course/student')) {
            //     continue;
            // }
            if( !$user->can('site/quiz/store_user_quiz'))
                    continue;
            
            $attems=userQuiz::where('user_id', $user_id)->where('quiz_lesson_id', $quiz_lesson->id)->orderBy('submit_time', 'desc')->get();
            // if(count($attems) > 0)
            //     return $attems;

            foreach($attems as $attem)
            {
                $req=new Request([
                    'attempt_id' => $attem->id,
                    'user_id' => $user->id,
                    'quiz_id' => $request->quiz_id,
                ]);
                // $All_attemp[]=self::get_fully_detailed_attempt($req);
                $user_Attemp['id']= $attem->id;
                $user_Attemp["submit_time"]= $attem->submit_time;
                $useranswer = userQuizAnswer::where('user_quiz_id',$attem->id)->whereIn('question_id',$essayQues)->where('answered',1)->where('user_grade', null)->count();
                if($useranswer > 0){
                    $user_Attemp["grade"]= null;
                    $user_Attemp["feedback"] =null;
                } 
                else{
                    // $user_Attemp["grade"]= $attem->user_grade; //user_grade is an accesor on UserQuiz

                    //withput wieght
                    $gradeNotWeight=0;
                    $user_quiz_answers=UserQuizAnswer::where('user_quiz_id',$attem->id)->where('force_submit',1)->get();
                    foreach($user_quiz_answers as $user_quiz_answer)
                        $gradeNotWeight+= $user_quiz_answer->user_grade;
                        
                    $user_Attemp["grade"]=$gradeNotWeight;
                    $user_Attemp["feedback"] =$attem->feedback;
                }
                $useranswerSubmitted = userQuizAnswer::where('user_quiz_id',$attem->id)->where('force_submit',null)->count();
                if( $useranswerSubmitted>0){
                    if($quiz_duration_ended)
                            continue;
                }
                else{
                    array_push($All_attemp, $user_Attemp);
                    $i++;
                }
            }

            $attemps['id'] = $user->id;
            $attemps['username'] = $user->username;
            $attemps['fullname'] =ucfirst($user->firstname) . ' ' . ucfirst($user->lastname);
            $attemps['picture'] = $user->attachment;
            $attemps['Attempts'] = $All_attemp;
            array_push($user_attempts, $attemps);
            if($i>0)
            {
                $Submitted_users++;
            }
        }
        $all_users['essay']=$essay;
        $all_users['unsubmitted_users'] = count($users) - $Submitted_users ;
        $all_users['submitted_users'] = $Submitted_users ;
        $all_users['notGraded'] = $countOfNotGraded ;
        $final->put('submittedAndNotSub',$all_users);
        $final->put('users',$user_attempts);
        $lesson = Lesson::find($request->lesson_id);
        LastAction::lastActionInCourse($lesson->courseSegment->course_id);

        return HelperController::api_response_format(200, $final, __('messages.quiz.students_attempts_list'));
    }

    public function get_fully_detailed_attempt(Request $request){
        $request->validate([
            'attempt_id' => 'required|integer|exists:user_quizzes,id',
            'user_id' => 'required|integer|exists:users,id',
        ]);
        $user_quiz = userQuiz::where('user_id', $request->user_id)->where('id',$request->attempt_id)->first();
        $total= quiz::where('id',$user_quiz->quiz_lesson->quiz_id)->with(['Question.question_answer'])->get();
        $quiz = quiz::where('id',$request->quiz_id)->first();

        // if($quiz->feedback == 1 )
        //     $show_is_true=1;
        // elseif($quiz->feedback == 2 && Carbon::now() < $Due_date->due_date )
        //     $show_is_true=1;
        // else
        //     $show_is_true=0;
            
        foreach($total as $quest){

            foreach($quest->question as $q){
                $q->question_answer;
                $Question_id =  $q->pivot->question_id;
                $Ans_ID = userQuizAnswer::where('user_quiz_id',$user_quiz->id)->where('question_id',$Question_id)->first();
                if(isset($Ans_ID->answer_id)){
                    $q->student_answer = QuestionsAnswer::find($Ans_ID->answer_id);
                    $q->user_grade =$Ans_ID->user_grade;
                    // if($show_is_true == 0){
                    //     unset($q->student_answer['is_true']);
                    // }
                }
                // if($show_is_true == 0)
                //     foreach($q->question_answer as $ans){
                //         unset( $q->question_answer);
                //         unset($ans['is_true']);
                //     }
                if(!isset($q->student_answer))
                $q->student_answer = Null;
                
            }
    
        }
       return $total;
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
            $total[]= quiz::where('id',$request->quiz_id)->with(['Question.question_answer'])->first();
            foreach($total as $quest){
                foreach($quest->question as $q){
                    $q->question_answer;
                    $Question_id =  $q->pivot->question_id;
                    $Ans_ID = userQuizAnswer::where('user_quiz_id',$UserQuiz->id)->where('question_id',$Question_id)->first();
                    if(isset($Ans_ID->answer_id)){
                        $q->student_answer = QuestionsAnswer::find($Ans_ID->answer_id);
                        $q->user_grade =$Ans_ID->user_grade;
                        if($show_is_true == 0){
                            unset($q->student_answer['is_true']);
                        }
                    }
                    if($show_is_true == 0)
                        foreach($q->question_answer as $ans)
                            unset($ans['is_true']);
                }
            }
        }
        return $total;
    }
}
