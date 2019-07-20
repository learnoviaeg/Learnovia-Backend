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
        $arr = array();
        if (isset($Question['parent'])) {
            $arr = Questions::where('id', $Question['parent'])->where('question_type_id', 5)->pluck('id')->first();
        }
        if (!isset($arr)) {
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
        $arr = array();

        if (isset($Question['parent'])) {
            $arr = Questions::where('id', $Question['parent'])->where('question_type_id', 5)->pluck('id')->first();
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
        //dd($cat);
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
        return "success";
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
        return "success";
    }

    public function Match($Question)
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
       // dd($resultA == null && $resultB == null);
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
        return "success";
    }

    public function Essay($Question)
    {
        $cat = $this::CreateOrFirstQuestion($Question);
        return "success";
    }

    public function paragraph($Question)
    {
        $cat = $this::CreateOrFirstQuestion($Question);
        return "success";
    }

    public function store(Request $request)
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
        return HelperController::api_response_format(200, $re, 'null');
    }

    /*updateQuestion*/
    public function updateQuestion($request)
    {
        $request->validate([
            'question_id' => 'required|integer|exists:questions,id',
            'mark' => 'required|integer|min:1',
            //'text' => 'string|min:1',
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

    public function updateTrueFalse($request)
    {

        $request->validate([

            'answers' => 'required|array|distinct|min:2|max:2',
            'answers.*' => 'required|boolean|distinct',
            'Is_True' => 'required|boolean',
            'And_why' => 'integer|required',
            'And_why_mark' => 'integer|min:1|required_if:And_why,==,1'
        ]);


        $question = $this->updateQuestion($request);
        $answers = QuestionsAnswer::where('question_id', $request->question_id)->get();
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
        return "success";

    }

    public function updateMCQ($request)
    {
        $request->validate([
            'answers' => 'required|array|min:2|distinct',
            'answers.*' => 'required|string|min:1',
            'Is_True' => 'required|integer|min:0|max:{$count(answers)-1}',
        ]);

        $question = $this->updateQuestion($request);
        $answers = QuestionsAnswer::where('question_id', $request->question_id)->get();
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


        $question = $this->updateQuestion($request);
        $answers = QuestionsAnswer::where('question_id', $request->question_id)->get();
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
            // dd($answers);
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

    public function updateEssay($request)
    {
        $question = $this->updateQuestion($request);

        return "updated sucess";
    }

    public function updateparagraph($request)
    {
        $question = $this->updateQuestion($request);

        return "updated sucess";
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
            'is_true' => $request->is_true,
            'question_id' => $request->question_id
        ]);

        return HelperController::api_response_format(200, $answer, 'Question Added Successfully');
    }
}
