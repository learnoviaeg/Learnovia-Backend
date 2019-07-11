<?php

namespace Modules\QuestionBank\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use Modules\QuestionBank\Entities\Questions;
use Modules\QuestionBank\Entities\QuestionsAnswer;

use App\Http\Controllers\HelperController;

class QuestionBankController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index()
    {
        $questions = Questions::all();

        $questions = $this->QuestionData($questions);

        return HelperController::api_response_format(200, $questions);
    }

    public function QuestionData($questions,$type = 0){

        if($type == 0){
            foreach ($questions as $question) {
                $question->category;
                $question->question_type;
                $question->question_category;
                $question->question_course;
                $question->question_answer;
            }
            $data = $questions;
        }
        else{
            $question = $questions;
            $question->category;
            $question->question_type;
            $question->question_category;
            $question->question_course;
            $question->question_answer;

            $data = $question;
        }
        return $data;
    }

    /**
     * Show the form for creating a new resource.
     * @return Response
     */
    public function getRandomQuestion(Request $request)
    {
        $request->validate([
            'course_id' => 'required|integer|exists:courses,id',
            'randomNumber' => 'required|integer|min:1'
        ]);

        $questions = Questions::inRandomOrder()
            ->where('course_id',$request->course_id)
            ->limit($request->randomNumber)
            ->get();

        $questions = $this->QuestionData($questions);

        return HelperController::api_response_format(200, $questions);
    }

    /**
     * @Description: create  multi Questions
     * @param : Request to access Question[0][text] and type if type 1 (True/False)
     *          access Question[0][answers][0] , Question[0][Is_True][0] and so on
     * @return: MSG => Question Created Successfully
     */
    public function store(Request $request)
    {
        $r = $request->validate([
            'Question' => 'required|array',
            'Question.*.text' => 'required',
            'Question.*.mark' => 'required|integer',
            'Question.*.Question_Category_id' => 'required|exists:questions_categories,id',
            'Question.*.Category_id' => 'required|exists:categories,id',
            'Question.*.Question_Type_id' => 'required|integer|exists:questions_types,id',
            'Question.*.Course_ID' => 'required|exists:courses,id',

        ]);

        $categoryies = collect([]);

        foreach ($request->Question as $question) {
            switch ($question['Question_Type_id']) {
                case 1: // True/false
                    $request->validate([
                        'Question.*.answers' => 'required|array',
                        'Question.*.answers.*' => 'required|boolean',
                        'Question.*.Is_True' => 'required|array',
                        'Question.*.Is_True.*' => 'required|boolean',
                    ]);
                    break;
                case 2 :
                    $request->validate([
                        'Question.*.contents' => 'required|array',
                        'Question.*.contents.*' => 'required|string|min:1',
                        'Question.*.Is_True' => 'required|array',
                        'Question.*.Is_True.*' => 'required|boolean',
                    ]);
                    break;
                case 3 :
                    $request->validate([
                        'Question.*.match_A' => 'required|array',
                        'Question.*.match_A.*' => 'required',
                        'Question.*.match_B' => 'required|array',
                        'Question.*.match_B.*' => 'required'
                    ]);
                    break;
            }
            $cat = Questions::firstOrCreate([
                'text' => $question['text'],
                'mark' => $question['mark'],
                'category_id' => $question['Category_id'],
                'question_type_id' => $question['Question_Type_id'],
                'question_category_id' => $question['Question_Category_id'],
                'course_id' => $question['Course_ID'],
            ]);

            $categoryies->push($cat);

            switch ($question['Question_Type_id']) {
                case 1 :
                    $is_true = 0;
                    foreach ($question['answers'] as $answer) {
                        QuestionsAnswer::firstOrCreate([
                            'question_id' => $cat->id,
                            'true_false' => $answer,
                            'content' => null,
                            'match_a' => null,
                            'match_b' => null,
                            'is_true' => $question['Is_True'][$is_true]
                        ]);
                        $is_true += 1;
                    }
                    break;
                case 2 :
                    $is_true = 0;
                    foreach ($question['contents'] as $con) {
                        $answer = QuestionsAnswer::firstOrCreate([
                            'question_id' => $cat->id,
                            'true_false' => null,
                            'content' => $con,
                            'match_a' => null,
                            'match_b' => null,
                            'is_true' => $question['Is_True'][$is_true]
                        ]);
                        $is_true += 1;
                    }
                    break;

                case 3:
                    $is_true = 0;
                    foreach ($question['match_A'] as $index => $MA) {
                        foreach ($question['match_B'] as $Secindex => $MP) {
                            $answer = QuestionsAnswer::firstOrCreate([
                                'question_id' => $cat->id,
                                'true_false' => null,
                                'content' => null,
                                'match_a' => $MA,
                                'match_b' => $MP,
                                'is_true' => ($index == $Secindex) ? 1 : 0
                            ]);
                            $is_true += 1;
                        }
                    }
                    break;
            }
        }
        return HelperController::api_response_format(201, $categoryies, 'Question Created Successfully');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(Request $request)
    {
        $request->validate([
            'question_id' => 'required|integer|exists:questions,id',

            'text' => 'required|string|min:1',
            'mark' => 'required|integer|min:1',
            'category_id' => 'required|integer|exists:categories,id',
            'question_type_id' => 'required|integer|exists:questions_types,id',
            'question_category_id' => 'required|integer|exists:questions_categories,id',
            'course_id' => 'required|integer|exists:courses,id',

            'answer' => 'required|array',
            'answer.*.id'=> 'required|integer|exists:questions_answers,id',
            'answer.*.content'=> 'required|string|min:1',
            'answer.*.true_false'=> 'nullable|boolean',
            'answer.*.match_a'=> 'nullable|string|max:10',
            'answer.*.match_b'=> 'nullable|string|max:10',
            'answer.*.is_true'=> 'required|boolean',
        ]);

        $question = Questions::find($request->question_id);

        $question->update([
            'text' => $request->text,
            'mark' => $request->mark,
            'category_id' => $request->category_id,
            'question_type_id' => $request->question_type_id,
            'question_category_id' => $request->question_category_id,
            'course_id' => $request->course_id,
        ]);

        foreach ($request->answer as $answer) {
            $singleAnswer = QuestionsAnswer::find($answer['id']);

            $singleAnswer->update([
                'content'    => $answer['content'],
                'true_false' => $answer['true_false'],
                'match_a'    => $answer['match_a'],
                'match_b'    => $answer['match_b'],
                'is_true'    => $answer['is_true'],
            ]);
        }

        $question = $this->QuestionData($question,1);

        return HelperController::api_response_format(200, $question,'updated Successfully');

    }

    public function destroy(Request $request)
    {
        $request->validate([
            'question_id' => 'required|integer|exists:questions,id'
        ]);

        $check = Questions::destroy($request->question_id);

        return HelperController::api_response_format(200, [],'Question deleted Successfully');
    }

    public function deleteAnswer(Request $request)
    {
        $request->validate([
            'answer_id' => 'required|integer|exists:questions_answers,id'
        ]);

        $check = QuestionsAnswer::destroy($request->answer_id);

        return HelperController::api_response_format(200, [],'Answer deleted Successfully');
    }

    public function addAnswer(Request $request)
    {
        $request->validate([
            'question_id' => 'required|integer|exists:questions,id',
            'content'=> 'required|string|min:1',
            'true_false'=> 'nullable|boolean',
            'match_a'=> 'nullable|string|max:10',
            'match_b'=> 'nullable|string|max:10',
            'is_true'=> 'required|boolean',
        ]);

        $answer = QuestionsAnswer::create([
            'content'    => $request->content,
            'true_false' => $request->true_false,
            'match_a'    => $request->match_a,
            'match_b'    => $request->match_b,
            'is_true'    => $request->is_true,
            'is_true'    => $request->is_true,
            'question_id' => $request->question_id
        ]);

        return HelperController::api_response_format(200, $answer,'Question Added Successfully');
    }
}
