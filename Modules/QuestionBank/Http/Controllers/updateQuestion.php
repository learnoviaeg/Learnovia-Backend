<?php

namespace Modules\QuestionBank\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use Modules\QuestionBank\Entities\Questions;
use Modules\QuestionBank\Entities\QuestionsAnswer;
use Symfony\Component\Console\Question\Question;
use Validator;
use App\Http\Controllers\HelperController;
use Modules\QuestionBank\Entities\QuestionsCategory;
use Modules\QuestionBank\Entities\QuestionsType;

class updateQuestion extends Controller
{


    /*updateQuestion*/
    public function updateQuestion($request)
    {
        $request->validate([
            'question_id' => 'required|integer|exists:questions,id',
            'mark' => 'required|integer|min:1',
            'category_id' => 'required|integer|exists:categories,id',
            'question_category_id' => 'required|integer|exists:questions_categories,id',
            'parent' => 'integer|exists:questions,id',
        ]);
        $arr = array();
        if ($request->parent) {
            $arr = Questions::where('id', $request->parent)->where('question_type_id', 5)->pluck('id')->first();
        }
        if (!isset($arr)) {
            return HelperController::api_response_format(400, null, 'this is not valid parent');
        }
        $question = Questions::find($request->question_id);
        //dd($question);

       if ($question->question_type_id != 3) {
            $request->validate([
                'text' => 'required|string|min:1',
            ]);
        }

        $question->update([
            'text' => ($request->text == null) ? "Match the correct Answer" : $request->text,
            'mark' => $request->mark,
            'category_id' => $request->category_id,
            'parent' => (isset($request->parent) && $request->Question_Type_id != 5) ? $request->parent : null,
            'question_category_id' => $request->question_category_id,
            'And_why' => ($request->question_type_id == 1) ? $request->And_why : null,
            'And_why_mark' => ($request->And_why == 1) ? $request->And_why_mark : null,
        ]);

        return $question;
    }

    public function updatesubQuestion($squestion, $parent=null,$Question_Type_id=null)
    {
        $validator = Validator::make($squestion->all(), [
            'mark' => 'required|integer|min:1',
            'category_id' => 'required|integer|exists:categories,id',
            'question_category_id' => 'required|integer|exists:questions_categories,id',
        ]);
        if ($validator->fails()) {
            return HelperController::api_response_format(400, $validator->errors());
        }


        $question_id = Questions::where('parent', $parent)->where('question_type_id', $Question_Type_id)->pluck('id')->first();
        $question = Questions::find($question_id);

        if ($question->question_type_id != 3) {
            $squestion->validate([
                'text' => 'required|string|min:1',
            ]);
        }
        $question->update([
            'text' => ($squestion['text'] == null) ? "Match the correct Answer" : $squestion['text'],
            'mark' =>$squestion['mark'],
            'category_id' => $squestion['category_id'],
            'parent' => $parent,
            'question_category_id' => $squestion['question_category_id'],
            'And_why' => ($Question_Type_id== 1) ? $squestion['And_why'] : null,
            'And_why_mark' => Questions::CheckAndWhy($squestion),
        ]);

        return $question;
    }

    public function updateTrueFalse($request, $parent,$Question_Type_id)
    {
        $request->validate([
            'answers' => 'required|array|distinct|min:2|max:2',
            'answers.*' => 'required|boolean|distinct',
            'Is_True' => 'required|boolean',
            'And_why' => 'integer|required',
            'And_why_mark' => 'integer|min:1|required_if:And_why,==,1'
        ]);



        if ($parent==null){
            $question = $this->updateQuestion($request,$parent);
            $answers = QuestionsAnswer::where('question_id', $request->question_id)->get();

        }
        else {
            $question = $this->updatesubQuestion($request,$parent,$Question_Type_id);
           // dd($question);
            $answers = QuestionsAnswer::where('question_id', $question->id)->get();

        }
        $is_true = 0;
        $Trues = null;
        foreach ($answers as $answer) {
            if ($is_true == $request->Is_True) {
                $Trues = 1;
            } else {
                $Trues = 0;
            }
            $answer->update([
                'question_id' => $question->id,
                'true_false' => $request->answers[$is_true],
                'is_true' => $Trues
            ]);
            $is_true += 1;
        }
       // $question = $this->updateQuestion($request,$parent);

        return "success";
    }

    public function updateMCQ($request,$parent,$Question_Type_id)
    {
        $request->validate([
            'answers' => 'required|array|min:2|distinct',
            'answers.*' => 'required|string|min:1',
            'Is_True' => 'required|integer|min:0|max:{$count(answers)-1}',
        ]);
        if ($parent==null){
            $question = $this->updateQuestion($request,$parent);
            $answers = QuestionsAnswer::where('question_id', $request->question_id)->get();

        }
        else {
            $question = $this->updatesubQuestion($request,$parent,$Question_Type_id);
            // dd($question);
            $answers = QuestionsAnswer::where('question_id', $question->id)->get();

        }
        if (count($request->answers) >= count($answers)) {
            $is_true = 0;
            $Trues = null;

            foreach ($request->answers as $answer) {
                if ($is_true == $request->Is_True) {
                    $Trues = 1;
                } else {
                    $Trues = 0;
                }
                if (!isset($answers[$is_true])) {
                    QuestionsAnswer::firstOrCreate([
                        'question_id' => $question->id,
                        'true_false' => null,
                        'content' => $answer,
                        'match_a' => null,
                        'match_b' => null,
                        'is_true' => $Trues,
                    ]);
                } else {
                    $answers[$is_true]->update([
                        'question_id' => $question->id,
                        'true_false' => null,
                        'content' => $answer,
                        'match_a' => null,
                        'match_b' => null,
                        'is_true' => $Trues,
                    ]);
                    $is_true += 1;
                }
            }
        } else {
            $is_true = 0;
            $Trues = null;
            foreach ($answers as $answer) {
                if (!isset($request->answers[$is_true])) {
                    $answer->delete();
                    continue;
                }
                if ($is_true == $request->Is_True) {
                    $Trues = 1;
                } else {
                    $Trues = 0;
                }
                $answer->update([
                    'question_id' => $question->id,
                    'true_false' => null,
                    'content' => $request->answers[$is_true],
                    'match_a' => null,
                    'match_b' => null,
                    'is_true' => $Trues,
                ]);
                $is_true += 1;

            }
        }
        return "success";
    }

    public function updateMatch($request,$parent,$Question_Type_id)
    {
        $request->validate([
            'match_A' => 'required|array|min:2|distinct',
            'match_A.*' => 'required|distinct',
            'match_B' => 'required|array|distinct',
            'match_B.*' => 'required|distinct'
        ]);
        if (count($request->match_A) > count($request->match_B)) {
            return HelperController::api_response_format(400, null, '  number of Questions is greater than numbers of answers ');
        }

        if ($parent==null){
            $question = $this->updateQuestion($request,$parent);
            $answers = QuestionsAnswer::where('question_id', $request->question_id)->get();

        }
        else {
            $question = $this->updatesubQuestion($request,$parent,$Question_Type_id);
            // dd($question);
            $answers = QuestionsAnswer::where('question_id', $question->id)->get();

        }


        //dd(count($answers));
        if (count($request->match_A) * count($request->match_B) == count($answers)) {
            $count = 0;

            foreach ($request->match_A as $index => $MA) {
                foreach ($request->match_B as $Secindex => $MP) {
                    $answers[$count]->update([
                        'question_id' => $question->id,
                        'true_false' => null,
                        'content' => null,
                        'match_a' => $MA,
                        'match_b' => $MP,
                        'is_true' => ($index == $Secindex) ? 1 : 0
                    ]);
                    $count += 1;
                }
            }
        } elseif (count($request->match_A) * count($request->match_B) > count($answers)) {
            $count = 0;

            foreach ($request->match_A as $index => $MA) {
                foreach ($request->match_B as $Secindex => $MP) {
                    if (!isset($answers[$count])) {
                        QuestionsAnswer::firstOrCreate([
                            'question_id' => $question->id,
                            'true_false' => null,
                            'content' => null,
                            'match_a' => $MA,
                            'match_b' => $MP,
                            'is_true' => ($index == $Secindex) ? 1 : 0]);

                    } else {
                        $answers[$count]->update([
                            'question_id' => $question->id,
                            'true_false' => null,
                            'content' => null,
                            'match_a' => $MA,
                            'match_b' => $MP,
                            'is_true' => ($index == $Secindex) ? 1 : 0
                        ]);
                    }
                    $count += 1;

                }
            }
        } elseif (count($request->match_A) * count($request->match_B) < count($answers)) {
            $diff = count($answers) - (count($request->match_a) * count($request->match_B));
            for ($x = 0; $x < $diff; $x++) {
                $answers[$x]->delete();
            }
            $count = $diff;
            foreach ($request->match_A as $index => $MA) {
                foreach ($request->match_B as $Secindex => $MP) {
                    $answers[$count]->update([
                        'question_id' => $question->id,
                        'true_false' => null,
                        'content' => null,
                        'match_a' => $MA,
                        'match_b' => $MP,
                        'is_true' => ($index == $Secindex) ? 1 : 0
                    ]);
                    $count += 1;
                }
            }

        }


        return "updated sucess";

    }

    public function updateEssay($request,$parent,$Question_Type_id)
    {
        if ($parent==null){
        $question = $this->updateQuestion($request,$parent, $Question_Type_id);}
       else {
            $question = $this->updatesubQuestion($request,$parent,$Question_Type_id);
           // dd($question);
        }

        return "updated sucess";
    }

    public function updateparagraph($request)
    {
        $request->validate([
            'subQuestions' => 'required|array|distinct',//|min:2',
            'subQuestions.*' => 'required|distinct',
            'subQuestions.*.Question_Type_id' => 'required|integer|exists:questions_types,id',

        ]);
        $question = $this->updateQuestion($request);
        foreach ($request->subQuestions as $subQuestion) {
           // print_r($subQuestion);
            $subQuestion = new Request($subQuestion);
            switch ($subQuestion->Question_Type_id) {
                case 1: // True/false
                    //dd($subQuestion->Question_Type_id);
                    $re[] = $this->updateTrueFalse($subQuestion,$question->id,$subQuestion->Question_Type_id);
                    break;
                case 2: // MCQ
                    $re[] = $this->updateMCQ($subQuestion,$question->id,$subQuestion->Question_Type_id);
                    break;
                case 3: // Match
                    $re[] = $this->updateMatch($subQuestion,$question->id,$subQuestion['Question_Type_id']);
                    break;
                case 4: // Essay
                    $re[] = $this->updateEssay($subQuestion,$question->id,$subQuestion->Question_Type_id);
                    break;
            }
        }
        return "updated sucess";
    }
    public function update(Request $request)
    {
    $request->validate([
         'question_id' => 'required|integer|exists:questions,id',]);
        $Question = Questions::find($request->question_id);
        switch ($Question->question_type_id) {
            case 1: // True/false
                $re[] = $this->updateTrueFalse($request,null,null);
                break;
            case 2: // MCQ
                $re[] = $this->updateMCQ($request,null);
                break;
            case 3: // Match
                $re[] = $this->updateMatch($request,null);
                break;
            case 4: // Essay
                $re[] = $this->updateEssay($request,null , null);
                break;
            case 5: // para
                $re[] = $this->updateparagraph($request);
                break;

        }
        return HelperController::api_response_format(200, $re, null);
    }

    /*end update*/


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
            'question_id' => $request->question_id
        ]);

        return HelperController::api_response_format(200, $answer, 'Question Added Successfully');
    }

    public function getAllTypes(Request $request){
        return HelperController::api_response_format(200 , QuestionsType::all(['name' , 'id']));
    }

    public function getAllCategories(Request $request){
        return HelperController::api_response_format(200 , QuestionsCategory::all(['name' , 'id']));
    }
}
