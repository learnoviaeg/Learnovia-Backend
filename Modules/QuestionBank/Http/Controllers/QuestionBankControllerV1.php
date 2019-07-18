<?php

namespace Modules\QuestionBank\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use Modules\QuestionBank\Entities\Questions;
use Modules\QuestionBank\Entities\QuestionsAnswer;
use Validator;
use App\Http\Controllers\HelperController;

class QuestionBankControllerV1 extends Controller
{
    public static function CreateOrFirstQuestion($Question)
    {
        Validator::make($Question, [
            'Question' => 'required|array',
            'Question.*.mark' => 'required|integer|min:1',
            'Question.*.Question_Category_id' => 'required|exists:questions_categories,id',
            'Question.*.Category_id' => 'required|exists:categories,id',
            'Question.*.Question_Type_id' => 'required|integer|exists:questions_types,id',
            'Question.*.text' => 'required_if:Question.*.Question_Type_id,==,!3',
            'Question.*.Course_ID' => 'required|exists:courses,id',
            'Question.*.parent' => 'integer|exists:questions,id',

        ]);
        if ($Question['parent']) {
            $arr = Questions::where('id',$Question['parent'])->where('question_type_id', 5)->pluck('id')->first();
        }
        if (!$arr) {
            return HelperController::api_response_format(400, null, 'this is not valid parent');
        }
        $Questions = collect([]);
        $cat = Questions::firstOrCreate([
            'text' => ($Question['text'] == null) ? "Match the correct Answer" : $Question['text'],
            'mark' => $Question['mark'],
            'And_why' => ($Question['Question_Type_id'] == 1) ? $Question['And_why'] : null,
            'And_why_mark' => ($Question['Question_Type_id'] == 1 && $Question['And_why'] == 1) ? $Question['And_why_mark'] : null,
            'category_id' => $Question['Category_id'],
            'parent' => (isset($Question['parent']) && $Question['Question_Type_id'] != 5) ? $Question['parent'] : null,
            'question_type_id' => $Question['Question_Type_id'],
            'question_category_id' => $Question['Question_Category_id'],
            'course_id' => $Question['Course_ID'],
        ]);
        $Questions->push($cat);
        return $cat;

    }

    public static function CreateQuestion($Question)
    {
        Validator::make($Question, [
            'Question' => 'required|array',
            'Question.*.mark' => 'required|integer|min:1',
            'Question.*.Question_Category_id' => 'required|exists:questions_categories,id',
            'Question.*.Category_id' => 'required|exists:categories,id',
            'Question.*.Question_Type_id' => 'required|integer|exists:questions_types,id',
            'Question.*.text' => 'required_if:Question.*.Question_Type_id,==,!3',
            'Question.*.Course_ID' => 'required|exists:courses,id',
            'Question.*.parent' => 'integer|exists:questions,id',
        ]);
        $Questions = collect([]);
        if (isset($Question['parent'])) {
            $arr = Questions::find($Question['parent'])->where('question_type_id', 5)->get();
        }
        if (!isset($arr)) {
            return HelperController::api_response_format(400, null, 'this is not valid parent');
        }
            $cat = Questions::Create([
                'text' => ($Question['text'] == null) ? "Match the correct Answer" : $Question['text'],
                'mark' => $Question['mark'],
                'parent' => (isset($Question['parent']) && $Question['Question_Type_id'] != 5) ? $Question['parent'] : null,
                'And_why' => ($Question['Question_Type_id'] == 1) ? $Question['And_why'] : null,
                'And_why_mark' => ($Question['Question_Type_id'] == 1 && $Question['And_why'] == 1) ? $Question['And_why_mark'] : null,
                'category_id' => $Question['Category_id'],
                'question_type_id' => $Question['Question_Type_id'],
                'question_category_id' => $Question['Question_Category_id'],
                'course_id' => $Question['Course_ID'],
            ]);
            $Questions->push($cat);
            return $cat;

        }

        public function TrueFalse($Question)
        {
            $validator = Validator::make($Question, [
                'answers' => 'required|array|distinct|min:2|max:2',
                'answers.*' => 'required|boolean|distinct',
                'And_why' => 'integer|required',
                'And_why_mark' => 'integer|min:1|required_if:And_why,==,1',
                'Is_True' => 'required|boolean',
            ]);
            if ($validator->fails()) {
                return HelperController::api_response_format(400, $validator->errors(), 'Something went wrong');
            }

            $cat = $this::CreateOrFirstQuestion($Question);
            $is_true = 0;
            $Trues = null;

            foreach ($Question['answers'] as $answer) {
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
            }
            return $Question;
        }

        public
        function MCQ($Question)
        {
            $validator = Validator::make($Question, [
                'answers' => 'required|array|distinct|min:2',
                'answers.*' => 'required|string|distinct',
                'Is_True' => 'required|integer',

            ]);
            if ($validator->fails()) {
                return HelperController::api_response_format(400, $validator->errors(), 'Something went wrong');
            }
            $id = Questions:: where('text', $Question['text'])->pluck('id')->first();
            $ansA = QuestionsAnswer::where('question_id', $id)->pluck('content')->toArray();
            $result = array_diff($Question['answers'], $ansA);
            if ($result == null) {
                return HelperController::api_response_format(400, null, ' Sorry this Question is exist');
            }
            $cat = $this::CreateQuestion($Question);
            $is_true = 0;
            $Trues = null;
            foreach ($Question['answers'] as $answer) {
                if ($is_true == $Question['Is_True']) {
                    $Trues = 1;
                } else {
                    $Trues = 0;
                }
                $answer = QuestionsAnswer::firstOrCreate([
                    'question_id' => $cat->id,
                    'true_false' => null,
                    'content' => $answer,
                    'match_a' => null,
                    'match_b' => null,
                    'is_true' => $Trues,
                ]);
                $is_true += 1;
            }
            return $Question;
        }

        public
        function Match($Question)
        {
            $validator = Validator::make($Question, [
                'match_A' => 'required|array|min:2|distinct',
                'match_A.*' => 'required|distinct',
                'match_B' => 'required|array|distinct',
                'match_B.*' => 'required|distinct',
            ]);
            if ($validator->fails()) {
                return HelperController::api_response_format(400, $validator->errors(), 'Something went wrong');
            }
            if (count($Question['match_A']) > count($Question['match_B'])) {
                return HelperController::api_response_format(400, null, '  number of Questions is greater than numbers of answers ');
            }
            $id = Questions:: where('text', $Question['text'])->pluck('id')->first();
            $ansA = QuestionsAnswer::where('question_id', $id)->pluck('match_A')->toArray();
            $resultA = array_diff($Question['match_A'], $ansA);
            $ansB = QuestionsAnswer::where('question_id', $id)->pluck('match_B')->toArray();
            $resultB = array_diff($Question['match_B'], $ansB);
            if ($resultA == null && $resultB == null) {
                return HelperController::api_response_format(400, null, ' Sorry this Question is exist');
            }
            $cat = $this::CreateQuestion($Question);
            $is_true = 0;
            foreach ($Question['match_A'] as $index => $MA) {
                foreach ($Question['match_B'] as $Secindex => $MP) {
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
            return $Question;
        }

        public
        function Essay($Question)
        {
            $cat = $this::CreateOrFirstQuestion($Question);
            return $cat;
        }

        public
        function paragraph($Question)
        {
            $cat = $this::CreateOrFirstQuestion($Question);
            return $cat;
        }

        public
        function store(Request $request)
        {
            foreach ($request->Question as $question) {
                switch ($question['Question_Type_id']) {
                    case 1: // True/false
                        $re[] = $this->TrueFalse($question);
                        break;
                    case 2: // MCQ
                        $re[] = $this->MCQ($question);
                        break;
                    case 3: // Match
                        $re[] = $this->Match($question);
                        break;
                    case 4: // Essay
                        $re[] = $this->Essay($question);
                        break;
                    case 5: // para
                        $re[] = $this->paragraph($question);
                        break;
                }
            }
            return $re;
        }
    }
