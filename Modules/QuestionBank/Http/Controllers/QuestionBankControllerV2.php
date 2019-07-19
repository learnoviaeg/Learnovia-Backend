<?php

namespace Modules\QuestionBank\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use Modules\QuestionBank\Entities\Questions;
use Modules\QuestionBank\Entities\QuestionsAnswer;
use Validator;
use App\Http\Controllers\HelperController;

class QuestionBankControllerV2 extends Controller
{
     public function updateQuestion($request){
         $request->validate([
             'question_id' => 'required|integer|exists:questions,id',
             'text' => 'required|string|min:1',
             'mark' => 'required|integer|min:1',
             'category_id' => 'required|integer|exists:categories,id',
             'question_category_id' => 'required|integer|exists:questions_categories,id',
             'parent' => 'integer|exists:questions,id',
]);
         $arr=array();
         if ($request->parent) {
             $arr = Questions::where('id', $request->parent)->where('question_type_id', 5)->pluck('id')->first();
         }
         if (!isset($arr)) {
             return HelperController::api_response_format(400, null, 'this is not valid parent');
         }
         $question = Questions::find($request->question_id);
         $question->update([
             'text' => $request->text,
             'mark' => $request->mark,
             'category_id' => $request->category_id,
             'parent'=> (isset($request->parent) && $request->Question_Type_id!= 5) ? $request->parent : null,
             'question_category_id' => $request->question_category_id,
             'And_why' => ($request->question_type_id == 1) ? $request->And_why : null,
             'And_why_mark' => ($request->And_why == 1) ? $request->And_why_mark : null,
         ]);

        return $question;
     }
    public function updateTrueFalse($request)
    {

        $request->validate([

            'answers' => 'required|array|distinct|min:2|max:2',
            'answers.*' => 'required|boolean|distinct',
            'Is_True' => 'required|boolean',
            'And_why' => 'integer|required',
            'And_why_mark' => 'integer|min:1|required_if:And_why,==,1'
        ]);


        $question =$this->updateQuestion($request);
        $answers = QuestionsAnswer::where('question_id', $request->question_id)->get();
        $is_true = 0;
        $Trues = null;
        foreach ($answers as $answer) {
            if ($is_true == $request->Is_True) {
                $Trues = 1;
            } else {
                $Trues = 0;
            }
            $answer-> update  ([
                'question_id' => $question->id,
                'true_false' => $request->answers[$is_true],
                'is_true' => $Trues
            ]);
            $is_true += 1;
        }
        return $answers;

    }

    public function updateMCQ($request)
    {
        $request->validate([
            'answers' => 'required|array|min:2|distinct',
            'answers.*' => 'required|string|min:1',
            'Is_True' => 'required|integer|min:0|max:{$count(answers.*)-1}',
        ]);

        $question =$this->updateQuestion($request);
        $answers = QuestionsAnswer::where('question_id', $request->question_id)->get();
        if(count($request->answers ) >= count($answers)){
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
            }else {
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
        }else{
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
        return $answers;
    }

    public function updateMatch($request)
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
        $question =$this->updateQuestion($request);
        $answers = QuestionsAnswer::where('question_id', $request->question_id)->get();
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

    return $answers;

    }

    public function updateEssay($request)
    {
        $question =$this->updateQuestion($request);

        return $question ;
    }

    public
    function updateparagraph($request)
    {
        $question =$this->updateQuestion($request);

        return $question ;
    }

    public
    function update(Request $request)
    {
        $Question = Questions::find($request->question_id);
        switch ($Question->question_type_id) {
                case 1: // True/false
                    $re[] = $this->updateTrueFalse($request);
                    break;
                case 2: // MCQ
                    $re[] = $this->updateMCQ($request);
                    break;
                case 3: // Match
                    $re[] = $this->updateMatch($request);
                    break;
                case 4: // Essay
                    $re[] = $this->updateEssay($request);
                    break;
                case 5: // para
                    $re[] = $this->updateparagraph($request);
                    break;

        }
        return HelperController::api_response_format(200, $re, 'updated Successfully');
    }
}
