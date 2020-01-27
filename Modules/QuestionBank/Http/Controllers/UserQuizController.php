<?php

namespace Modules\QuestionBank\Http\Controllers;

use App\GradeCategory;
use App\GradeItems;
use App\User;
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
use App\Http\Controllers\HelperController;
use Modules\QuestionBank\Entities\Questions;
use Modules\QuestionBank\Entities\QuestionsAnswer;

use Modules\QuestionBank\Entities\userQuizAnswer;
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

        if (!isset($quiz_lesson)) {
            return HelperController::api_response_format(400, null, 'No quiz assign to this lesson');
        }

        $max_attempt_index = userQuiz::where('quiz_lesson_id', $quiz_lesson->id)
            ->where('user_id', Auth::user()->id)
            ->get()->max('attempt_index');

        $userQuiz = userQuiz::where('quiz_lesson_id', $quiz_lesson->id)
            ->where('user_id', Auth::user()->id)
            ->first();

        $attempt_index = 0;
        if ($max_attempt_index == null) {
            $attempt_index = 1;
        } else if (isset($userQuiz)) {
            if ($max_attempt_index < $userQuiz->quiz_lesson->max_attemp) {
                $attempt_index = ++$max_attempt_index;
            } else {
                return HelperController::api_response_format(400, null, 'Max Attempt number reached');
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
            'open_time' => Carbon::now()
        ]);

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
            'Questions.*.id' => 'integer|exists:questions,id',
        ]);
        return $request;
        $Q_IDS= array();
        // check that question exist in the Quiz
        $user_quiz = userQuiz::find($request->user_quiz_id);
        $questions_ids = $user_quiz->quiz_lesson->quiz->Question->pluck('id');

        $allData = collect([]);
        foreach ($request->Questions as $index => $question) {
            if(isset($question['id'])){
            if (!$questions_ids->contains($question['id'])) {

                $check_question = Questions::find($question['id']);

                if (isset($check_question->parent)) {
                    if (!$questions_ids->contains($check_question->parent)) {
                        return HelperController::api_response_format(400, null, 'This Question didn\'t exists in the quiz');
                    }
                } else {
                    return HelperController::api_response_format(400, null, 'This Question didn\'t exists in the quiz');
                }
            }

            $currentQuestion = Questions::find($question['id']);
            $question_type_id = $currentQuestion->question_type->id;
            $question_answers = $currentQuestion->question_answer->pluck('id');

            $data = [
                'user_quiz_id' => $request->user_quiz_id,
                'question_id' => $question['id'],
                'answered' => 1
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
                            return HelperController::api_response_format(400, $question['answer_id'], 'This answer didn\'t belongs to this question');
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
                                return HelperController::api_response_format(400, null, 'This answer didn\'t belongs to this question');
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

                        if (count($question['choices_array']) != count($trueAnswer)) {
                            return HelperController::api_response_format(400, null, 'Please submit all choices');
                        }
                        $true_counter = 0;
                        $false_counter = 0;
                        foreach ($question['choices_array'] as $choice) {
                            if (!$question_answers->contains($choice)) {
                                return HelperController::api_response_format(400, null, 'This answer didn\'t belongs to this question');
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
            } else {
                return HelperController::api_response_format(400, null, 'No type determine to this question');
            }
        }
        userQuizAnswer::where('user_quiz_id',$request->user_quiz_id)->where('question_id',$question['id'])->update($data);

        }

        $restOfAns =  userQuizAnswer::where('user_quiz_id',$request->user_quiz_id)->whereNotIn('question_id',$Q_IDS)->update(['answered'=>'1']);

        return HelperController::api_response_format(200, $allData, 'Quiz Answer Registered Successfully');

    }

    public function estimateEssayandAndWhy(Request $request)
    {
        $request->validate([
            'user_quiz_id' => 'required|integer|exists:user_quizzes,id',
            'Questions' => 'required|array',
            'Questions.*.id' => 'required|integer|exists:questions,id',
            'Questions.*.mark' => 'required|numeric',
            'Questions.*.feedback' => 'string',

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
                        return HelperController::api_response_format(400, null, 'This Question didn\'t exists in the quiz');
                    }
                } else {
                    return HelperController::api_response_format(400, null, 'This Question didn\'t exists in the quiz');
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
                return HelperController::api_response_format(400, null, 'No User Answer found to this Question');
            }

            if (isset($question_type_id)) {
                switch ($question_type_id) {
                    case 1: // True_false
                        # code...

                        if (isset($currentQuestion->And_why)) {
                            if ($currentQuestion->And_why_mark < $question['mark']) {
                                return HelperController::api_response_format(400, null, 'Mark should be less than or equal the Question mark');
                            }

                            $user_grade = userQuizAnswer::where('user_quiz_id', $request->user_quiz_id)
                                ->where('question_id', $question['id'])->pluck('user_grade')->first();

                            $data['user_grade'] = (float)$user_grade + (float)$question['mark'];
                        } else {
                            $data = null;
                        }
                        break;


                    case 4: // Essay
                        # code...

                        if ($currentQuestion->mark < $question['mark']) {
                            return HelperController::api_response_format(400, null, 'Mark should be less than or equal the Question mark');
                        }

                        $data['user_grade'] = $question['mark'];
                        $data['feedback'] = $question['feedback'];

                        break;

                    default:
                        return HelperController::api_response_format(400, null, 'This Question isn\'t Essay or true and false');
                        break;

                }

                if ($data != null) {
                    $allData->push($data);
                }
            } else {
                return HelperController::api_response_format(400, null, 'No type determine to this question');
            }

        }
        foreach ($allData as $data) {
            $userAnswer = userQuizAnswer::where('user_quiz_id', $request->user_quiz_id)
                ->where('question_id', $data['question_id'])->first();

            $userAnswer->user_grade = $data['user_grade'];
            if(isset($data['feedback']))
            $userAnswer->feedback = $data['feedback'];
            $userAnswer->save();
        }

        return HelperController::api_response_format(200, $allData, 'Quiz Answer Registered Successfully');
    }

    public function get_user_quiz(Request $request)
    {
        $user_id = Auth::User()->id;

        $request->validate([
            'quiz_id' => 'required|integer|exists:quizzes,id',
            'lesson_id' => 'required|integer|exists:lessons,id',
            'user_id' => 'integer|exists:users,id',
        ]);
        if (isset($request->user_id))
            $user_id = $request->user_id;

        $quiz_lesson = QuizLesson::where('quiz_id', $request->quiz_id)
            ->where('lesson_id', $request->lesson_id)->first();

        if (!isset($quiz_lesson)) {
            return HelperController::api_response_format(400, null, 'No quiz assign to this lesson');
        }
        $attemps = userQuiz::where('user_id', $user_id)->where('quiz_lesson_id', $quiz_lesson->id)->get();
        return HelperController::api_response_format(200, $attemps, 'your attempts are ...');

    }

    public function gradeUserQuiz(Request $request)
    {
        $request->validate([
            'user_quiz_id' =>'required|integer|exists:user_quizzes,id',
            'grade' => 'required|integer',
            'feedback' => 'string'
        ]);
        $userQuiz = userQuiz::find($request->user_quiz_id);
        $quiz_lesson = QuizLesson::find($userQuiz->quiz_lesson_id);
        if ($quiz_lesson->grade < $request->grade) {
            return HelperController::api_response_format(400, $body = [], $message = 'please put grade less than ' . $quiz_lesson->grade);
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

        return HelperController::api_response_format(200, $body = $user_grade, $message = 'Quiz graded sucess');
    }
    public function get_all_users_quiz_attempts(Request $request)
    {
        $request->validate([
            'quiz_id' => 'required|integer|exists:quizzes,id',
            'lesson_id' => 'required|integer|exists:lessons,id',
        ]);
        $final= collect([]);
        $quiz_lesson = QuizLesson::where('quiz_id', $request->quiz_id)->where('lesson_id', $request->lesson_id)->first();
        if (!isset($quiz_lesson))
            return HelperController::api_response_format(400, null, 'No quiz assign to this lesson');

        $users = userQuiz::where('quiz_lesson_id', $quiz_lesson->id)->pluck('user_id')->unique();

        foreach ($users as $user_id){
            $user = User::where('id',$user_id)->first();
            $attem=userQuiz::where('user_id', $user_id)->where('quiz_lesson_id', $quiz_lesson->id)->first();
            $req=new Request([
                'attempt_id' => $attem->id,
                'user_id' => $user->id
            ]);
            $attemps['id'] = $user->id;
            $attemps['username'] = $user->username;
            $attemps['Attempts'] = self::get_fully_detailed_attempt($req);
            $final->push($attemps);
        }
      return HelperController::api_response_format(200, $final, 'Students attempts are ...');
    }

    public function get_fully_detailed_attempt(Request $request){
        $request->validate([
            'attempt_id' => 'required|integer|exists:user_quizzes,id',
            'user_id' => 'required|integer|exists:users,id',
        ]);
        $user_quiz = userQuiz::where('user_id', $request->user_id)->where('id',$request->attempt_id)->first();
        $total= quiz::where('id',$user_quiz->quiz_lesson->quiz_id)->with(['Question.question_answer'])->get();
        return($total);
        foreach($total as $quest){
            foreach($quest->question as $q){
                $q->question_answer;
                $Question_id =  $q->pivot->question_id;
                $Ans_ID = userQuizAnswer::where('user_quiz_id',$user_quiz->id)->where('question_id',$Question_id)->first();
                if(isset($Ans_ID->answer_id)){
                    $q->student_answer = QuestionsAnswer::find($Ans_ID->answer_id);
                    $q->user_grade =$Ans_ID->user_grade;
                }
                if(!isset($q->student_answer))
                    $q->student_answer = Null;
                if(!isset($q->user_grade))
                    $q->user_grade = Null;
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
            return HelperController::api_response_format(200, 'No Submitted quizzes to be shown');}
        $Due_date= QuizLesson::find($quiz_lesson->id);
        $show_is_true=1;
        if($quiz->feedback == 3)
            return HelperController::api_response_format(200, Null , 'You are not allowed to view feedback!');
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
            if($show_is_true == 0){
                foreach($q->question_answer as $ans)
                {
                    unset($ans['is_true']);
                }
                }
        }}
    }
    return $total;
    }

}
