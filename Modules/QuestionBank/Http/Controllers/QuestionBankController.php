<?php

namespace Modules\QuestionBank\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use Modules\QuestionBank\Entities\Questions;
use Modules\QuestionBank\Entities\QuestionsAnswer;
use Validator;
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

    public function QuestionData($questions, $type = 0)
    {

        if ($type == 0) {
            foreach ($questions as $question) {
                $question->category;
                $question->question_type;
                $question->question_category;
                $question->question_course;
                $question->question_answer;
            }
            $data = $questions;
        } else {
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
            ->where('course_id', $request->course_id)
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
            'question_category_id' => 'required|integer|exists:questions_categories,id',
        ]);
        $Question = Questions::find($request->question_id);

        if ($Question->question_type_id == 1) {
            $request->validate([
                'And_why' => 'integer|required',
                'And_why_mark' => 'integer|min:1|required_if:And_why,==,1'
            ]);
        }

        switch ($Question->question_type_id) {
            case 1 :
                $request->validate([
                    'answers' => 'required|array||min:2|max:2|distinct',
                    'answers.*' => 'required|boolean|distinct',
                    'Is_True' => 'required|array|distinct',
                    'Is_True.*' => 'required|boolean',

                ]);

                break;
            case 2 : // MCQ
                $request->validate([
                    'contents' => 'required|array|min:2|distinct',
                    'contents.*' => 'required|string|min:1',
                    'Is_True' => 'required|array|min:2',
                    'Is_True.*' => 'required|boolean',
                ]);

                break;
            case 3 :
                $request->validate([
                    'match_A' => 'required|array|min:2|distinct',
                    'match_A.*' => 'required|distinct',
                    'match_B' => 'required|array|distinct',
                    'match_B.*' => 'required|distinct'
                ]);
                break;
        }
        $Question->update([
            'text' => $request->text,
            'mark' => $request->mark,
            'category_id' => $request->category_id,
            'question_category_id' => $request->question_category_id,
            'And_why' => ($Question->question_type_id == 1) ? $request->And_why : null,
            'And_why_mark' => ($request->And_why == 1) ? $request->And_why_mark : null,
        ]);
        switch ($Question->question_type_id) {
            case 1 :
                if (in_array(1, $request->Is_True) && in_array(0, $request->Is_True)) {

                    $count = 0;
                    $answers = QuestionsAnswer::where('question_id', $request->question_id)->get();
                    foreach ($answers as $answer) {

                        if ($request->Is_True[$count] == 1) {
                            $AnswerWithTrue = QuestionsAnswer::where('question_id', $Question->id)->where('is_true', 1)->first();
                            if ($AnswerWithTrue->id != $answer->id) {
                                if ($AnswerWithTrue) {
                                    $AnswerWithTrue->update([
                                        'is_true' => 0,
                                    ]);
                                }
                            }
                            $answer->update([
                                'is_true' => $request->Is_True[$count]
                            ]);
                        } else {
                            $AnswerWithFalse = QuestionsAnswer::where('question_id', $Question->id)->where('is_true', 0)->first();
                            if ($AnswerWithFalse->id != $answer->id) {
                                $AnswerWithFalse->update([
                                    'is_true' => 1,
                                ]);
                                $answer->update([
                                    'is_true' => $request->Is_True
                                ]);
                            }
                        }
                        $count = $count + 1;
                    }
                } else {
                    return HelperController::api_response_format(400, null, ' Please choose one to be the right answer');

                }
                break;
            case 2 :
                $Trues = array();
                foreach ($request->Is_True as $ele) {
                    if ($ele == 1) {
                        array_push($Trues, $ele);
                    }
                }
                if (in_array(1, $request->Is_True) && count($Trues) == 1) {

                    $count = 0;
                    $answers = QuestionsAnswer::where('question_id', $request->question_id)->get();
                    foreach ($answers as $answer) {
                        if (!isset($request->contents[$count])) {
                            $answer->delete();
                            continue;
                        }
                        if ($request->Is_True[$count] == 1) {
                            $AnswerWithTrue = QuestionsAnswer::where('question_id', $Question->id)->where('is_true', 1)->first();

                            if ($request->Is_True[$count]) {
                                if ($AnswerWithTrue->id != $answer->id) {
                                    $AnswerWithTrue->update([
                                        'is_true' => 0,
                                    ]);
                                }
                                $answer->update([
                                    'is_true' => $request->Is_True[$count],
                                    'content' => $request->contents[$count]]);
                            }
                        } else {
                            $AnswerWithTrue = QuestionsAnswer::where('question_id', $Question->id)->where('is_true', 1)->first();
                            if ($AnswerWithTrue->id != $answer->id) {

                                $answer->update([
                                    'is_true' => $request->Is_True[$count],
                                    'content' => $request->contents[$count]
                                ]);
                            } else {

                                return HelperController::api_response_format(200, null, 'there is no True answer , Please choose one to be the right answer');

                            }

                        }
                        $count = $count + 1;
                    }
                } else {
                    return HelperController::api_response_format(200, null, ' Please choose one to be the right answer');

                }
                break;
            case 3 :
                $answers = QuestionsAnswer::where('question_id', $request->question_id)->get();
                $count = 0;

                foreach ($request->match_A as $index => $MA) {
                    foreach ($request->match_B as $Secindex => $MP) {
                        $answers[$count]->update([
                            'question_id' => $Question->id,
                            'true_false' => null,
                            'content' => null,
                            'match_a' => $MA,
                            'match_b' => $MP,
                            'is_true' => ($index == $Secindex) ? 1 : 0
                        ]);
                        $count += 1;
                    }
                }

                break;


        }


        $question = $this->QuestionData($Question, 1);

        return HelperController::api_response_format(200, $question, 'updated Successfully');

    }

//    public function updateAnswers(Request $req)
//    {
//        $req->validate([
//            'id' => 'required|integer|min:1',
//        ]);
//        $answer = QuestionsAnswer::find($req->id);
//        $Question = Questions::where('id', $answer->question_id)->first();
//        switch ($Question->question_type_id) {
//            case 1 :
//                $req->validate([
//                    'And_why_answer' => 'string|required_if:$Question->And_why,==,1',
//                    'Is_True' => 'required|boolean',
//                ]);
//                if ($Question->And_why == 1) {
//                    $req->validate([
//                        'And_why_answer' => 'string|required',
//                    ]);
//                }
//                break;
//            case 2 : // MCQ
//                $req->validate([
//                    'contents' => 'required|string|min:1',
//                    'Is_True' => 'required|boolean',
//                ]);
//
//                break;
//            case 3 :
//                $req->validate([
//                    'match_A' => 'required',
//                    'match_B' => 'required',
//                ]);
//                break;
//        }
//        switch ($Question->question_type_id) {
//            case 1 :
//                if ($req->Is_True) {
//                    $AnswerWithTrue = QuestionsAnswer::where('question_id', $Question->id)->where('is_true', 1)->first();
//                    if ($AnswerWithTrue->id != $answer->id) {
//
//                        if ($AnswerWithTrue) {
//                            $AnswerWithTrue->update([
//                                'is_true' => 0,
//                                'And_why_answer' => null
//                            ]);
//                        }
//                    }
//                    $answer->update([
//                        'And_why_answer' => ($Question->And_why == 0) ? null : $req->And_why_answer,
//                        'is_true' => $req->Is_True
//                    ]);
//                } else {
//                    $AnswerWithFalse = QuestionsAnswer::where('question_id', $Question->id)->where('is_true', 0)->first();
//                    if ($AnswerWithFalse->id != $answer->id) {
//                        $AnswerWithFalse->update([
//                            'is_true' => 1,
//                            'And_why_answer' => ($Question->And_why == 0) ? null : $req->And_why_answer]);
//                        $answer->update([
//                            'And_why_answer' => null,
//                            'is_true' => $req->Is_True
//                        ]);
//                    }
//                }
//                break;
//            case 2 :
//                $AnswerWithTrue = QuestionsAnswer::where('question_id', $Question->id)->where('is_true', 1)->first();
//
//                if ($req->Is_True) {
//                    if ($AnswerWithTrue->id != $answer->id) {
//                        $AnswerWithTrue->update([
//                            'is_true' => 0,
//                        ]);
//                    }
//                    $answer->update([
//                        'is_true' => $req->Is_True,
//                        'content' => $req->contents]);
//                } else {
//                    if ($AnswerWithTrue->id != $answer->id) {
//
//                        $answer->update([
//                            'is_true' => $req->Is_True,
//                            'content' => $req->contents]);
//                    } else {
//                        $answer->is_true = $req->Is_True;
//                        $answer->content = $req->contents;
//                        return HelperController::api_response_format(400, null, 'there is no True answer , Please choose one to be the right answer');
//
//                    }
//
//                }
//
//                break;
//            case 3 :
//                $answer->update([
//                    'true_false' => null,
//                    'And_why_answer' => null,
//                    'content' => null,
//                    'match_a' => $req->match_A,
//                    'match_b' => $req->match_B,
//                    'is_true' => 1
//                ]);
//                break;
//
//
//        }
//
//    }
////            switch ($request->Question_Type_id) {
//                case 1: // True/false
//                    $request->validate([
//                        'answers' => 'required|array',
//                        'answers.*' => 'required|boolean',
//                        'And_why' => 'integer|required',
//                        'And_why_mark'=> 'integer|min:1|required_if:And_why,==,1',
//                        'And_why_answer' => 'string|required_if:Question.*.And_why,==,1',
//                        'Is_True' => 'required|array',
//                        'Is_True.*' => 'required|boolean',
//                    ]);
//                    break;
//                case 2 :
//                    $request->validate([
//                        'contents' => 'required|array',
//                        'contents.*' => 'required|string|min:1',
//                        'Is_True' => 'required|array',
//                        'Is_True.*' => 'required|boolean',
//                    ]);
//                    break;
//                case 3 :
//                    $request->validate([
//                        'match_A' => 'required|array',
//                        'match_A.*' => 'required',
//                        'match_B' => 'required|array',
//                        'match_B.*' => 'required'
//                    ]);
//                    break;
//            }


//        $answers=QuestionsAnswer::where('question_id',$request->question_id)->get();
//        $count=0;
//        foreach ($answers as $answer){
//            switch ($request->Question_Type_id){
//                case 1 :
//                    $answer->true_false=$request->answers[$count];
//                    $answer->And_why_answer= ( $request->And_why==0)?null:$request->And_why_answer;
//                    $answer->content = null;
//                    $answer->match_a = null;
//                    $answer->match_b = null;
//                    $answer->is_true = $request->Is_True[$count];
//                    break;
//


    public function destroy(Request $request)
    {
        $request->validate([
            'question_id' => 'required|integer|exists:questions,id'
        ]);

        $check = Questions::destroy($request->question_id);

        return HelperController::api_response_format(200, [], 'Question deleted Successfully');
    }

    public function deleteAnswer(Request $request)
    {
        $request->validate([
            'answer_id' => 'required|integer|exists:questions_answers,id'
        ]);

        $check = QuestionsAnswer::destroy($request->answer_id);

        return HelperController::api_response_format(200, [], 'Answer deleted Successfully');
    }

    public function addAnswer(Request $request)
    {
        $request->validate([
            'question_id' => 'required|integer|exists:questions,id',
            'contents' => 'required|string|min:1',
            'true_false' => 'nullable|boolean',
            'match_a' => 'nullable|string|max:10',
            'match_b' => 'nullable|string|max:10',
            'is_true' => 'required|boolean',
        ]);

        $answer = QuestionsAnswer::create([
            'content' => $request->contents,
            'true_false' => $request->true_false,
            'match_a' => $request->match_a,
            'match_b' => $request->match_b,
            'is_true' => $request->is_true,
            'is_true' => $request->is_true,
            'question_id' => $request->question_id
        ]);

        return HelperController::api_response_format(200, $answer, 'Question Added Successfully');
    }
}
