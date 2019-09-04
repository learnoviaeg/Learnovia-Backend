<?php

namespace Modules\QuestionBank\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Auth;
use Browser;
use Carbon\Carbon;
use Modules\QuestionBank\Entities\QuestionsAnswer;
use Modules\QuestionBank\Entities\userQuiz;
use Modules\QuestionBank\Entities\QuizLesson;
use App\Http\Controllers\HelperController;
use Modules\QuestionBank\Entities\Questions;
use Modules\QuestionBank\Entities\userQuizAnswer;
use function Opis\Closure\serialize;

class UserQuizController extends Controller
{

    public function store_user_quiz(Request $request)
    {

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


    public function quiz_answer(Request $request)
    {
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
                'question_id' => $question['id']
            ];

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

                        $data['answer_id'] = $question['answer_id'];
                        if (isset($question['and_why']))
                            $data['and_why'] = $question['and_why'];
                        break;

                    case 2: // MCQ
                        # code...
                        $request->validate([
                            'Questions.' . $index . '.mcq_answers_array' => 'required|array',
                            'Questions.' . $index . '.mcq_answers_array.*' => 'required|integer|exists:questions_answers,id'
                        ]);

                        foreach ($question['mcq_answers_array'] as $mcq_answer) {
                            if (!$question_answers->contains($mcq_answer)) {
                                return HelperController::api_response_format(400, null, 'This answer didn\'t belongs to this question');
                            }
                        }
                        $data['mcq_answers_array'] = serialize($question['mcq_answers_array']);
                        break;

                    case 3: // Match
                        # code...
                        $request->validate([
                            'Questions.' . $index . '.choices_array' => 'required|array',
                            'Questions.' . $index . '.choices_array.*' => 'required|integer|exists:questions_answers,id',
                        ]);

                        foreach ($question['choices_array'] as $choice) {
                            if (!$question_answers->contains($choice)) {
                                return HelperController::api_response_format(400, null, 'This answer didn\'t belongs to this question');
                            }
                        }

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
        foreach ($allData as $data) {
            userQuizAnswer::create($data);
        }

        return HelperController::api_response_format(200, $allData, 'Quiz Answer Registered Successfully');

    }

    public function user_Attemp(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'lesson_id' => 'required|integer|exists:lessons,id',
            'quiz_id' => 'required|integer|exists:quizzes,id',
        ]);

        $quiz_lesson = QuizLesson::where('quiz_id', $request->quiz_id)->where('lesson_id', $request->lesson_id)->pluck('id')->first();
        $user_quizzes = userQuiz::where('user_id', $request->user_id)->where('quiz_lesson_id', $quiz_lesson)->get();
        foreach ($user_quizzes as $user_quiz) {
            $user_quiz['user_quiz_answer'] = userQuizAnswer::where('user_quiz_id', $user_quiz->id)->orderBy('question_id')->get();
            $temp = userQuizAnswer::where('user_quiz_id', $user_quiz->id)->orderBy('question_id')->get();
            $user_quiz['user_quiz_answer']['questions'] = Questions::with('question_answer')->whereIn('id', $temp->pluck('question_id')->toArray())->get();
            //  dd($user_quiz['user_quiz_answer']['questions'][0]->question_type_id);
            $count = 0;

            if (count($user_quiz['user_quiz_answer']) > 0) {
               // return $user_quiz['user_quiz_answer'];

                foreach ($temp as $user_quiz_answer) {
                    $type = Questions::find($user_quiz_answer->question_id)->question_type_id;
                       // return $user_quiz_answer->question_id ;
                    print_r($type);
                    switch ($type) {
                        case 1 :
                            $user_quiz['user_quiz_answer']['questions'][$count]['user_answer'] = QuestionsAnswer::find($user_quiz_answer->answer_id);
                            break;
                        case 2 :
                            $answers = null;
                            $answers = \Opis\Closure\unserialize($user_quiz_answer->mcq_answers_array);
                            $ANS = array();
                            foreach ($answers as $answer) {
                                array_push($ANS, QuestionsAnswer::find($answer));
                            }
                            $user_quiz['user_quiz_answer']['questions'][$count]['user_answer'] = $ANS;
                            break;
                        case 3 :
                            $answers = null;
                            $answers = \Opis\Closure\unserialize($user_quiz_answer->choices_array);
                            $ANS = array();
                            foreach ($answers as $answer) {
                                array_push($ANS, QuestionsAnswer::find($answer));
                            }
                            $user_quiz['user_quiz_answer']['questions'][$count]['user_answer'] = $ANS;
                            break;
                    }
                    $count += 1;
                }
            }
        }
        return $user_quizzes;

    }
}
