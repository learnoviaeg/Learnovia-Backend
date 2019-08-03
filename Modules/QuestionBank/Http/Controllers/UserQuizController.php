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
use Modules\QuestionBank\Entities\Questions;
use Modules\QuestionBank\Entities\userQuizAnswer;
use function Opis\Closure\serialize;

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


   public function quiz_answer(Request $request){
        $request->validate([
            'user_quiz_id' => 'required|integer|exists:user_quizzes,id',
            'question_id' => 'required|integer|exists:questions,id',
        ]);

        // check that question exist in the Quiz
        $user_quiz = userQuiz::find($request->user_quiz_id);
        $questions_ids = $user_quiz->quiz_lesson->quiz->Question->pluck('id');
        if(!$questions_ids->contains($request->question_id)){
            return HelperController::api_response_format(400, null, 'This Question didn\'t exists in the quiz');
        }

        $question = Questions::find($request->question_id);
        $question_type_id = $question->question_type->id;
        $question_answers = $question->question_answer->pluck('id');

        $data = [
            'user_quiz_id' => $request->user_quiz_id,
            'question_id' => $request->question_id
        ];

        if(isset($question_type_id)){
            switch ($question_type_id) {
                case 1: // True_false
                    # code...
                    $request->validate([
                        'answer_id' => 'required|integer|exists:questions_answers,id',
                        'and_why' => 'required|string',
                    ]);

                    if(!$question_answers->contains($request->answer_id)){
                        return HelperController::api_response_format(400, null, 'This answer didn\'t belongs to this question');
                    }

                    $data['answer_id'] = $request->answer_id;
                    $data['and_why'] = $request->and_why;
                    break;

                case 2: // MCQ
                    # code...
                    $request->validate([
                        'mcq_answers_array' => 'required|array',
                        'mcq_answers_array.*' => 'required|integer|exists:questions_answers,id'
                    ]);

                    foreach($request->mcq_answers_array as $mcq_answer){
                        if(!$question_answers->contains($mcq_answer)){
                            return HelperController::api_response_format(400, null, 'This answer didn\'t belongs to this question');
                        }
                    }
                    $data['mcq_answers_array'] = serialize($request->mcq_answers_array);
                    break;

                case 3: // Match
                    # code...
                    $request->validate([
                        'choices_array' => 'required|array',
                        'choices_array.*' => 'required|array|min:2|max:2',
                        'choices_array.*.*' => 'required|integer|exists:questions_answers,id',
                    ]);

                    foreach($request->choices_array as $choices_array){
                        foreach($choices_array as $choice){
                            if(!$question_answers->contains($choice)){
                                return HelperController::api_response_format(400, null, 'This answer didn\'t belongs to this question');
                            }
                        }
                    }

                    $data['choices_array'] = serialize($request->choices_array);
                    break;

                case 4: // Essay
                    # code...
                    $request->validate([
                        'content' => 'required|string',
                    ]);
                    $data['content'] = $request->content;
                    break;

                case 5: // Paragraph
                    # code...
                    $request->validate([
                        'content' => 'required|string',
                    ]);
                    $data['content'] = $request->content;
                    break;

            }

            $userQuiz = userQuizAnswer::create($data);

            return HelperController::api_response_format(200, $userQuiz, 'Quiz Answer Registered Successfully');

        }
        else{
            return HelperController::api_response_format(400, null, 'Something went wrong');
        }
   }
}
