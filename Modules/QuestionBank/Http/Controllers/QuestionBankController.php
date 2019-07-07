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
                unset($question->category->created_at);
                unset($question->category->updated_at);

                $question->question_type;
                unset($question->question_type->created_at);
                unset($question->question_type->updated_at);

                $question->question_category;
                unset($question->question_category->created_at);
                unset($question->question_category->updated_at);

                $question->question_course;
                unset($question->question_course->created_at);
                unset($question->question_course->updated_at);

                $question->question_answer;

                foreach ($question->question_answer as $answer) {
                    unset($answer->created_at);
                    unset($answer->updated_at);
                    unset($answer->question_id);
                }

                unset($question->category_id);
                unset($question->question_type_id);
                unset($question->question_category_id);
                unset($question->course_id);
                unset($question->created_at);
                unset($question->updated_at);
            }
            $data = $questions;
        }
        else{
            $question = $questions;

            $question->category;
            unset($question->category->created_at);
            unset($question->category->updated_at);

            $question->question_type;
            unset($question->question_type->created_at);
            unset($question->question_type->updated_at);

            $question->question_category;
            unset($question->question_category->created_at);
            unset($question->question_category->updated_at);

            $question->question_course;
            unset($question->question_course->created_at);
            unset($question->question_course->updated_at);

            $question->question_answer;

            foreach ($question->question_answer as $answer) {
                unset($answer->created_at);
                unset($answer->updated_at);
                unset($answer->question_id);
            }

            unset($question->category_id);
            unset($question->question_type_id);
            unset($question->question_category_id);
            unset($question->course_id);
            unset($question->created_at);
            unset($question->updated_at);

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
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Response
     */
    public function show($id)
    {
        return view('questionbank::show');
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
