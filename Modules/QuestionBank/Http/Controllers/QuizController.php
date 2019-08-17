<?php

namespace Modules\QuestionBank\Http\Controllers;

use App\Course;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Http\Controllers\HelperController;
use Modules\QuestionBank\Entities\quiz;
use Modules\QuestionBank\Entities\Questions;
use Modules\QuestionBank\Entities\QuestionsAnswer;
use Auth;
use TXPDF;

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
            'is_graded' => 'required|boolean',
            'duration' => 'required|integer',
            'shuffle' => 'boolean'
        ]);
        $index=Quiz::whereCourse_id($request->course_id)->get()->max('index');
        $Next_index=$index+1;
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
                'course_id' => $request->course_id,
                'is_graded' => $request->is_graded,
                'duration' => $request->duration,
                'created_by' => Auth::user()->id,
                'Shuffle' => quiz::checkSuffle($request),
                'index' => $Next_index
            ]);
            return HelperController::api_response_format(200, $quiz,'Quiz added Successfully');
        }

        if($questionsIDs != null){
            $quiz = quiz::create([
                'name' => $request->name,
                'course_id' => $request->course_id,
                'is_graded' => $request->is_graded,
                'duration' => $request->duration,
                'created_by' => Auth::user()->id,
                'Shuffle' => quiz::checkSuffle($request),
                'index' => $Next_index
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
        $questionsIDs = app('Modules\QuestionBank\Http\Controllers\QuestionBankController')->store($request,1);
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
            'is_graded' => 'required|boolean',
            'duration' => 'required|integer',
        ]);

        $quiz = quiz::find($request->quiz_id);

        $newQuestionsIDs = $this->storeWithNewQuestions($request);
        $oldQuestionsIDs = $this->storeWithOldQuestions($request);

        $questionsIDs = $newQuestionsIDs->merge($oldQuestionsIDs);

        if(count($questionsIDs) == 0){ // In case of delete all questions

            $quiz->update([
                'name' => $request->name,
                'is_graded' => $request->is_graded,
                'duration' => $request->duration,
                'index' => $request->index,
            ]);

            $quiz->Question()->detach();

            return HelperController::api_response_format(200, $quiz,'Quiz Updated Successfully');
        }

        $quiz->update([
            'name' => $request->name,
            'is_graded' => $request->is_graded,
            'duration' => $request->duration,
            'index' => $request->index,
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

    public function get(Request $request){
        $request->validate([
            'quiz_id' => 'required|integer|exists:quizzes,id'
        ]);
        $quiz = Quiz::where('id',$request->quiz_id)->pluck('shuffle')->first();
        if($quiz == 0)
        {
            $quiz = quiz::find($request->quiz_id);
            $Questions = $quiz->Question;
            foreach($Questions as $Question){
                $Question->question_answer->shuffle();
            }
            return HelperController::api_response_format(200, $Questions);
        }
        else {
            $quiz = quiz::find($request->quiz_id);
            $shuffledQuestion = $quiz->Question->shuffle();
            foreach($shuffledQuestion as $question){
                if(count($question->childeren) > 0){
                    $shuffledChildQuestion = $question->childeren->shuffle();
                    unset($question->childeren);
                    foreach($shuffledChildQuestion as $single){
                        $single->question_type;
                    }
                    $question->childeren = $shuffledChildQuestion;
                    foreach($shuffledChildQuestion as $childQuestion){
                        $answers = $childQuestion->question_answer->shuffle();
                        $childQuestion->answers = $answers;
                        unset($childQuestion->question_answer);
                        unset($childQuestion->pivot);
                    }
                }
                else{
                    unset($question->childeren);
                }
                $answers = $question->question_answer->shuffle();
                $question->answers = $answers;
                $question->question_category;
                $question->question_type;
                foreach($question->answers as $answer){
                    unset($answer->is_true);
                }
                unset($question->question_answer);
                unset($question->pivot);
            }
            $quiz->shuffledQuestion = $shuffledQuestion;
            unset($quiz->Question);

            // TXPDF::AddPage();
            // TXPDF::Write(0, $quiz);
            // TXPDF::Output(Storage_path('app\public\PDF\\Quiz '.$request->quiz_id.'.pdf'), 'F');

            return HelperController::api_response_format(200, $quiz);
        }
    }
    public function sortDown($quiz_id,$index){

        $course_id=Quiz::where('id',$quiz_id)->pluck('course_id')->first();
        $quiz_index=Quiz::where('id',$quiz_id)->pluck('index')->first();

        $quizes= Quiz::where('course_id',$course_id)->get();
        foreach ($quizes as $quiz ){
            if($quiz->index > $quiz_index || $quiz->index < $index){
                continue;
            }
            if ($quiz->index  !=  $quiz_index){
                $quiz->update([
                    'index'=>$quiz->index+1
                ]);
            }else{
                $quiz->update([
                    'index'=>$index
                ]);
            }
        }
        return $quizes ;

    }

    public function SortUp($quiz_id,$index){
        $course_id=Quiz::where('id',$quiz_id)->pluck('course_id');
        $quiz_index=Quiz::where('id',$quiz_id)->pluck('index')->first();
        $quizes= Quiz::where('course_id',$course_id)->get();
        foreach ($quizes as $quiz ){
            if($quiz->index > $index || $quiz->index < $quiz_index ){
                continue;
            }
            elseif ($quiz->index  !=  $quiz_index){
                $quiz->update([
                    'index'=>$quiz->index-1
                ]);
            }else{
                $quiz->update([
                    'index'=>$index
                ]);
            }
        }
        return $quizes ;
    }

    public function sort(Request $request){
        $request->validate([
            'quiz_id' => 'required|integer|exists:quizzes,id',
            'index'=>'required|integer'
        ]);
        $quiz_index=Quiz::where('id',$request->quiz_id)->pluck('index')->first();

        if($quiz_index>$request->index){
            $quizes=$this->sortDown($request->quiz_id,$request->index);
        }
        else{
            $quizes = $this->SortUp($request->quiz_id,$request->index);
        }
        return HelperController::api_response_format(200, $quizes,' Successfully');
    }
}
