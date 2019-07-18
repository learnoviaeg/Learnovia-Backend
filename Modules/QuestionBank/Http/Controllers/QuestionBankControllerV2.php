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




    public function updateTrueFalse($Question)
    {

        $validator = Validator::make($Question, [
            'question_id' => 'required|integer|exists:questions,id',
            'text' => 'required|string|min:1',
            'mark' => 'required|integer|min:1',
            'category_id' => 'required|integer|exists:categories,id',
            'question_category_id' => 'required|integer|exists:questions_categories,id',
            'answers' => 'required|array|distinct|min:2|max:2',
            'answers.*' => 'required|boolean|distinct',
            'Is_True' => 'required|boolean',
            'parent' => 'integer|exists:questions,id',
            'And_why' => 'integer|required',
            'And_why_mark' => 'integer|min:1|required_if:And_why,==,1'

        ]);

        if ($validator->fails()) {
            return HelperController::api_response_format(400, $validator->errors(), 'Something went wrong');
        }
        if ($Question['parent']) {
            $arr = Questions::where('id',$Question['parent'])->where('question_type_id', 5)->pluck('id')->first();
        }
        if (!$arr) {
            return HelperController::api_response_format(400, null, 'this is not valid parent');
        }

        $question = Questions::find($Question->question_id);
        $question->update([
            'text' => $Question->text,
            'mark' => $Question->mark,
            'category_id' => $Question->category_id,
            'parent'=> (isset($Question['parent']) && $Question['Question_Type_id'] != 5) ? $Question['parent'] : null,
            'question_category_id' => $Question->question_category_id,
            'And_why' => ($Question->question_type_id == 1) ? $Question->And_why : null,
            'And_why_mark' => ($Question->And_why == 1) ? $Question->And_why_mark : null,]);
            $answers = QuestionsAnswer::where('question_id', $Question->question_id)->get();

        $is_true = 0;
        $Trues = null;

       /* foreach ($Question['answers'] as $answer) {
            if ($is_true == $Question['Is_True']) {
                $Trues = 1;
            } else {
                $Trues = 0;
            }
            QuestionsAnswer::firstOrCreate([
                'question_id' => $cat->id,
                'true_false' => $answer,
                'content' => null,
                'match_a' => null,
                'match_b' => null,
                'is_true' => $Trues
            ]);
            $is_true += 1;
        }*/
        return $Question;




    }

    public function updateMCQ($Question)
    {

        return $Question;
    }

    public function updateMatch($Question)
    {
        return $Question;
    }

    public function updateEssay($Question)
    {
        return ;
    }

    public
    function updateparagraph($Question)
    {
        $cat = $this::CreateOrFirstQuestion($Question);
        return $cat;
    }

    public
    function update(Request $request)
    {
        foreach ($request->Question as $question) {
            switch ($question['Question_Type_id']) {
                case 1: // True/false
                    $re[] = $this->updateTrueFalse($question);
                    break;
                case 2: // MCQ
                    $re[] = $this->updateMCQ($question);
                    break;
                case 3: // Match
                    $re[] = $this->updateMatch($question);
                    break;
                case 4: // Essay
                    $re[] = $this->updateEssay($question);
                    break;
                case 5: // para
                    $re[] = $this->updateparagraph($question);
                    break;
            }
        }
        return $re;
    }
}
