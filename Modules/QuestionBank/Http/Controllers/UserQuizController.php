<?php

namespace Modules\QuestionBank\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Auth;
use Browser;
use Carbon\Carbon;
use Modules\QuestionBank\Entities\userQuiz;
use Modules\QuestionBank\Entities\QuizLesson;
use App\Http\Controllers\HelperController;
use Modules\QuestionBank\Entities\Questions;
use Modules\QuestionBank\Entities\QuestionsAnswer;

use Modules\QuestionBank\Entities\userQuizAnswer;
use function Opis\Closure\serialize;

class UserQuizController extends Controller
{

   public function store_user_quiz(Request $request){

        $request->validate([
            'quiz_id' => 'required|integer|exists:quizzes,id',
            'lesson_id' => 'required|integer|exists:lessons,id',
        ]);

        $quiz_lesson = QuizLesson::where('quiz_id',$request->quiz_id)
                ->where('lesson_id',$request->lesson_id)->first();

        if(!isset($quiz_lesson)){
            return HelperController::api_response_format(400, null, 'No quiz assign to this lesson');
        }

        $max_attempt_index = userQuiz::where('quiz_lesson_id', $quiz_lesson->id)
            ->where('user_id', Auth::user()->id)
            ->get()->max('attempt_index');

        $userQuiz = userQuiz::where('quiz_lesson_id', $quiz_lesson->id)
                    ->where('user_id', Auth::user()->id)
                    ->first();

        $attempt_index = 0;
        if(!isset($max_attempt_index)){
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
            'quiz_lesson_id' => $quiz_lesson->id,
            'status_id' => 2,
            'feedback' => null,
            'grade' => null,
            'attempt_index' => $attempt_index,
            'ip' => $request->ip(),
            'device_data' => $deviceData,
            'browser_data' => $browserData,
            'open_time' => Carbon::now()
        ]);

        return HelperController::api_response_format(200, $userQuiz);

   }


   public function quiz_answer(Request $request){
        $request->validate([
            'user_quiz_id' => 'required|integer|exists:user_quizzes,id',
            'Questions' => 'required|array',
            'Questions.*.id' => 'required|integer|exists:questions,id',
        ]);

        // check that question exist in the Quiz
        $user_quiz = userQuiz::find($request->user_quiz_id);
        $questions_ids = $user_quiz->quiz_lesson->quiz->Question->pluck('id');

        $allData = collect([]);
        foreach ($request->Questions as $index => $question) {

            if(!$questions_ids->contains($question['id'])){

                $check_question = Questions::find($question['id']);

                if(isset($check_question->parent)){
                    if(!$questions_ids->contains($check_question->parent)){
                        return HelperController::api_response_format(400, null, 'This Question didn\'t exists in the quiz');
                    }
                }
                else{
                    return HelperController::api_response_format(400, null, 'This Question didn\'t exists in the quiz');
                }
            }

            $currentQuestion = Questions::find($question['id']);
            $question_type_id = $currentQuestion->question_type->id;
            $question_answers = $currentQuestion->question_answer->pluck('id');

            $data = [
                'user_quiz_id' => $request->user_quiz_id,
                'question_id' => $question['id']
            ];

            if(isset($question_type_id)){
                switch ($question_type_id) {
                    case 1: // True_false
                        # code...
                        $request->validate([
                            'Questions.'.$index.'.answer_id' => 'required|integer|exists:questions_answers,id',
                            'Questions.'.$index.'.and_why' => 'nullable|string',
                        ]);

                        if(!$question_answers->contains($question['answer_id'])){
                            return HelperController::api_response_format(400, $question['answer_id'], 'This answer didn\'t belongs to this question');
                        }

                        $answer = QuestionsAnswer::find($question['answer_id']);
                        $data['answer_id'] = $question['answer_id'];
                        $data['user_grade'] = ($answer->is_true == 1) ? $currentQuestion->mark : 0;
                        if(isset($question['and_why']))
                            $data['and_why'] = $question['and_why'];
                        break;

                    case 2: // MCQ
                        # code...
                        $request->validate([
                            'Questions.'.$index.'.mcq_answers_array' => 'required|array',
                            'Questions.'.$index.'.mcq_answers_array.*' => 'required|integer|exists:questions_answers,id'
                        ]);

                        $flag = 1;
                        foreach($question['mcq_answers_array'] as $mcq_answer){
                            if(!$question_answers->contains($mcq_answer)){
                                return HelperController::api_response_format(400, null, 'This answer didn\'t belongs to this question');
                            }
                            $answer = QuestionsAnswer::find($mcq_answer);
                            if($answer->is_true == 0){
                                $flag = 0;
                            }
                        }
                        $data['user_grade'] = ($flag == 1) ? $currentQuestion->mark : 0;

                        $data['mcq_answers_array'] = serialize($question['mcq_answers_array']);
                        break;

                    case 3: // Match
                        # code...
                        $request->validate([
                            'Questions.'.$index.'.choices_array' => 'required|array',
                            'Questions.'.$index.'.choices_array.*' => 'required|integer|exists:questions_answers,id',
                        ]);

                        $trueAnswer = $currentQuestion->question_answer->where('is_true',1)->pluck('id');

                        if(count($question['choices_array']) != count($trueAnswer)){
                            return HelperController::api_response_format(400, null, 'Please submit all choices');
                        }
                        $true_counter = 0;
                        $false_counter = 0;
                        foreach($question['choices_array'] as $choice){
                            if(!$question_answers->contains($choice)){
                                return HelperController::api_response_format(400, null, 'This answer didn\'t belongs to this question');
                            }
                            $answer = QuestionsAnswer::find($choice);
                            if($answer->is_true == 0){
                                $false_counter++;
                            }
                            else{
                                $true_counter++;
                            }
                        }
                        $question_mark = (float) $currentQuestion->mark;
                        $multiple = (float) $true_counter * (float) $question_mark;
                        $grade = (float) $multiple / (float) count($trueAnswer);
                        $data['user_grade'] = $grade;

                        $data['choices_array'] = serialize($question['choices_array']);
                        break;

                    case 4: // Essay
                        # code...
                        $request->validate([
                            'Questions.'.$index.'.content' => 'required|string',
                        ]);
                        $data['content'] = $question['content'];
                        break;

                    case 5: // Paragraph
                        # code...
                        break;
                }

                $allData->push($data);
            }
            else{
                return HelperController::api_response_format(400, null, 'No type determine to this question');
            }

        }
        foreach($allData as $data){
            userQuizAnswer::create($data);
        }

        return HelperController::api_response_format(200, $allData, 'Quiz Answer Registered Successfully');

   }

   public function estimateEssayandAndWhy(Request $request){
        $request->validate([
            'user_quiz_id' => 'required|integer|exists:user_quizzes,id',
            'Questions' => 'required|array',
            'Questions.*.id' => 'required|integer|exists:questions,id',
            'Questions.*.mark' => 'required|numeric',
        ]);

        // check that question exist in the Quiz
        $user_quiz = userQuiz::find($request->user_quiz_id);
        $questions_ids = $user_quiz->quiz_lesson->quiz->Question->pluck('id');

        $allData = collect([]);
        foreach ($request->Questions as $question) {

            if(!$questions_ids->contains($question['id'])){

                $check_question = Questions::find($question['id']);

                if(isset($check_question->parent)){
                    if(!$questions_ids->contains($check_question->parent)){
                        return HelperController::api_response_format(400, null, 'This Question didn\'t exists in the quiz');
                    }
                }
                else{
                    return HelperController::api_response_format(400, null, 'This Question didn\'t exists in the quiz');
                }
            }

            $currentQuestion = Questions::find($question['id']);
            $question_type_id = $currentQuestion->question_type->id;

            $data = [
                'question_id' => $question['id']
            ];

            $userQuizAnswer = userQuizAnswer::where('user_quiz_id',$request->user_quiz_id)
                    ->where('question_id',$question['id'])->first();

            if(!isset($userQuizAnswer)){
                return HelperController::api_response_format(400, null, 'No User Answer found to this Question');
            }

            if(isset($question_type_id)){
                switch ($question_type_id) {
                    case 1: // True_false
                        # code...

                        if(isset($currentQuestion->And_why)){
                            if($currentQuestion->And_why_mark < $question['mark']){
                                return HelperController::api_response_format(400, null, 'Mark should be less than or equal the Question mark');
                            }

                            $user_grade = userQuizAnswer::where('user_quiz_id',$request->user_quiz_id)
                                ->where('question_id',$question['id'])->pluck('user_grade')->first();

                            $data['user_grade'] = (float) $user_grade + (float) $question['mark'];
                        }
                        else{
                            $data = null;
                        }
                        break;


                    case 4: // Essay
                        # code...

                        if($currentQuestion->mark < $question['mark']){
                            return HelperController::api_response_format(400, null, 'Mark should be less than or equal the Question mark');
                        }

                        $data['user_grade'] = $question['mark'];
                        break;

                    default:
                        return HelperController::api_response_format(400, null, 'This Question isn\'t Essay or true and false');
                        break;

                }

                if($data != null){
                    $allData->push($data);
                }
            }
            else{
                return HelperController::api_response_format(400, null, 'No type determine to this question');
            }

        }
        foreach($allData as $data){
            $userAnswer = userQuizAnswer::where('user_quiz_id',$request->user_quiz_id)
                            ->where('question_id',$data['question_id'])->first();

            $userAnswer->user_grade = $data['user_grade'];
            $userAnswer->save();
        }

        return HelperController::api_response_format(200, $allData, 'Quiz Answer Registered Successfully');
}

}
