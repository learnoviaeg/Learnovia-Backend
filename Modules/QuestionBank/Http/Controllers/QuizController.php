<?php

namespace Modules\QuestionBank\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Http\Controllers\HelperController;
use Modules\QuestionBank\Entities\quiz;
use Modules\QuestionBank\Entities\Questions;
use Modules\QuestionBank\Entities\QuestionsAnswer;

class QuizController extends Controller
{
    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|min:3',
            'course_id' => 'required|integer|exists:courses,id',
            'type' => 'required|in:0,1,2',
              /**
                * type 0 => new Question OR OLD
                * type 1 => random Questions
                * type 2 => Without Question
                */
        ]);

        if($request->type == 0 ){ // new or new
            $newQuestionsIDs = $this->storeWithNewQuestions($request);
            $oldQuestionsIDs = $this->storeWithOldQuestions($request);
            $questionsIDs = $newQuestionsIDs->merge($oldQuestionsIDs);
        }
        else if($request->type == 1){ // random
            $questionsIDs = $this->storeWithRandomQuestions($request);
        }
        else{ // create Quiz without Question
            $quiz = quiz::create([
                'name' => $request->name,
                'course_id' => $request->course_id
            ]);
            return HelperController::api_response_format(200, $quiz,'Quiz added Successfully');
        }

        if($questionsIDs != null){
            $quiz = quiz::create([
                'name' => $request->name,
                'course_id' => $request->course_id
            ]);

            $quiz->Question()->attach($questionsIDs);

            $quiz->Question;

            foreach($quiz->Question as $question){
                unset($question->pivot);
                $question->category;
                $question->question_type;
                $question->question_category;
                $question->question_course;
                $question->question_answer;
            }

            return HelperController::api_response_format(200, $quiz,'Quiz added Successfully');
        }

        return HelperController::api_response_format(200, null,'There\'s no Questions for this course in Question Bank');
    }

    // New Questions
    public function storeWithNewQuestions(Request $request){
        $request->validate([
            'Question' => 'nullable|array',
            'Question.*.text' => 'required',
            'Question.*.mark' => 'required|integer',
            'Question.*.Question_Category_id' => 'required|exists:questions_categories,id',
            'Question.*.Category_id' => 'required|exists:categories,id',
            'Question.*.Question_Type_id' => 'required|integer|exists:questions_types,id',
          //  'Question.*.Course_ID' => 'required|exists:courses,id',
        ]);

        $questionsIDs = collect([]);
        if(isset($request->Question)){
            foreach ($request->Question as $question) {
                switch ($question['Question_Type_id']) {
                    case 1:
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
                    'course_id' => $request->course_id,
                ]);

                $questionsIDs->push($cat->id);
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
        }
        return $questionsIDs;
    }

    // Old Questions
    public function storeWithOldQuestions(Request $request){
        $request->validate([
            'oldQuestion' => 'nullable|array',
            'oldQuestion.*' => 'required|integer|exists:questions,id',
        ]);

        return $request->oldQuestion;
    }

    // Random Questions
    public function storeWithRandomQuestions(Request $request){
        $request->validate([
            'randomNumber' => 'required|integer|min:1'
        ]);

        $questionIDs = Questions::inRandomOrder()
            ->where('course_id',$request->course_id)
            ->limit($request->randomNumber)
            ->get();

        if(count($questionIDs) != 0){
            $questionIDs = $questionIDs->pluck('id');
            return $questionIDs;
        }

        return null;
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
            'quiz_id' => 'required|integer|exists:quizzes,id',
            'name' => 'required|string|min:3',
            'course_id' => 'required|integer|exists:courses,id',
        ]);

        $quiz = quiz::find($request->quiz_id);

        $newQuestionsIDs = $this->storeWithNewQuestions($request);
        $oldQuestionsIDs = $this->storeWithOldQuestions($request);

        $questionsIDs = $newQuestionsIDs->merge($oldQuestionsIDs);

        if(count($questionsIDs) == 0){ // In case of delete all questions

            $quiz->update([
                'name' => $request->name,
            ]);

            $quiz->Question()->detach();

            return HelperController::api_response_format(200, $quiz,'Quiz Updated Successfully');
        }

        $quiz->update([
            'name' => $request->name,
        ]);

        $quiz->Question()->detach();
        $quiz->Question()->attach($questionsIDs);

        $quiz->Question;

        foreach($quiz->Question as $question){
            unset($question->pivot);
            $question->category;
            $question->question_type;
            $question->question_category;
            $question->question_course;
            $question->question_answer;
        }

        return HelperController::api_response_format(200, $quiz,'Quiz Updated Successfully');
    }

    public function destroy(Request $request)
    {
        $request->validate([
            'quiz_id' => 'required|integer|exists:quizzes,id'
        ]);

        quiz::destroy($request->quiz_id);

        return HelperController::api_response_format(200, [],'Quiz deleted Successfully');
    }
    public function getQuizwithRandomQuestion(Request $request){
        $request->validate([
            'quiz_id' => 'required|integer|exists:quizzes,id',
        ]);

        $quiz = quiz::find($request->quiz_id);
        $shuffledQuestion = $quiz->Question->shuffle();
        foreach($shuffledQuestion as $question){
            $answers = $question->question_answer->shuffle();
            $question->answers = $answers;
            unset($question->question_answer);
            unset($question->pivot);
        }
        $quiz->shuffledQuestion = $shuffledQuestion;
        unset($quiz->Question);

        return HelperController::api_response_format(200, $quiz);
    }

    /*public function store(Request $request)
    {
        $r = $request->validate([
            'Question' => 'required|array',
            'Question.*.mark' => 'required|integer|min:1',
            'Question.*.Question_Category_id' => 'required|exists:questions_categories,id',
            'Question.*.Category_id' => 'required|exists:categories,id',
            'Question.*.Question_Type_id' => 'required|integer|exists:questions_types,id',
            'Question.*.text' => 'required_if:Question.*.Question_Type_id,==,!3',
            'Question.*.Course_ID' => 'required|exists:courses,id',
        ]);
        $Questions = collect([]);
        foreach ($request->Question as $question) {
            $validator = null ;
            switch ($question['Question_Type_id']) {
                case 1: // True/false
                    $validator =Validator::make($question , [
                        'answers' => 'required|array|min:2|max:2|distinct',
                        'answers.*' => 'required|boolean|distinct',
                        'And_why' => 'integer|required',
                        'And_why_mark' => 'integer|min:1|required_if:And_why,==,1',
                        'Is_True' => 'required|array|distinct',
                        'Is_True.*' => 'required|boolean',
                    ]);
                    break;
                case 2 ://MCQ
                    $validator = Validator::make($question , [
                        'contents' => 'required|array|min:2|distinct',
                        'contents.*' => 'required|string|min:1',
                        'Is_True' => 'required|array|min:2',
                        'Is_True.*' => 'required|boolean',
                    ]);
                    break;
                case 3 ://Essay
                    $validator = Validator::make($question , [
                        'match_A' => 'required|array|min:2|distinct',
                        'match_A.*' => 'required|distinct',
                        'match_B' => 'required|array|distinct',
                        'match_B.*' => 'required|distinct'
                    ]);
                    break;
            }
            if ($validator->fails())
                return HelperController::api_response_format(400 , $validator->errors() , $question);
            if ($question['Question_Type_id'] == 3 && count($question['match_A']) > count($question['match_B'])) {
                return HelperController::api_response_format(400, null, '  number of Questions is greater than numbers of answers ');
            }
            if ($question['Question_Type_id'] == 2 || $question['Question_Type_id'] == 1) {
                $Trues = array();
                foreach ($question['Is_True'] as $ele) {
                    if ($ele == 1) {
                        array_push($Trues, $ele);
                    }
                }
            }

            if ((($question['Question_Type_id'] == 2 && count($question['Is_True']) != count($question['contents']))
                || ($question['Question_Type_id'] == 1 && count($question['Is_True']) != count($question['answers'])))
            ) {
                return HelperController::api_response_format(400, null, ' length of IS_True not equal  length of answers');
            }
            if ($question['Question_Type_id'] == 2 && (!(in_array(1, $question['Is_True']) && count($Trues) == 1))) {
                return HelperController::api_response_format(400, null, '  Please choose only one to be the right answer');
            }
            if ($question['Question_Type_id'] == 1 && !(in_array(1, $question['Is_True']) && in_array(0, $question['Is_True']))) {
                return HelperController::api_response_format(400, null, '  Please choose only one to be the right answer');

            }
            $cat = Questions::firstOrCreate([
                'text' => ($question['text'] == null) ? "Match the correct Answer" : $question['text'],
                'mark' => $question['mark'],
                'And_why' => ($question['Question_Type_id'] == 1) ? $question['And_why'] : null,
                'And_why_mark' => ($question['Question_Type_id'] == 1 && $question['And_why'] == 1) ? $question['And_why_mark'] : null,
                'category_id' => $question['Category_id'],
                'question_type_id' => $question['Question_Type_id'],
                'question_category_id' => $question['Question_Category_id'],
                'course_id' => $question['Course_ID'],
            ]);

            $Questions->push($cat);

            switch ($question['Question_Type_id']) {
                case 1 :
                    if (in_array(1, $question['Is_True']) && in_array(0, $question['Is_True'])) {

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
                    } else {
                        return HelperController::api_response_format(400, null, '  Please choose only one to be the right answer');

                    }
                    break;
                case 2 :
                    $Trues = array();
                    foreach ($question['Is_True'] as $ele) {
                        if ($ele == 1) {
                            array_push($Trues, $ele);
                        }
                    }
                    if (in_array(1, $question['Is_True']) && count($Trues) == 1) {
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
                    } else {
                        return HelperController::api_response_format(400, null, ' Please choose one to be the right answer');

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
                                // 'And_why_answer' => null,
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
        return HelperController::api_response_format(201, $Questions, 'Question Created Successfully');
    }*/
}
