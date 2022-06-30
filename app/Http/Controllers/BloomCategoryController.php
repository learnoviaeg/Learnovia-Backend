<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\BloomCategory;
use Modules\QuestionBank\Entities\Questions;
use Modules\QuestionBank\Entities\QuizLesson;
use Modules\QuestionBank\Entities\UserQuiz;
use Modules\QuestionBank\Entities\UserQuizAnswer;
use DB;

class BloomCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $bloomCategories=BloomCategory::where('current',1);
        if(isset($request->default))
            $bloomCategories=BloomCategory::where('default',$request->default);

        return response()->json(['message' => __('messages.bloom_category.get'), 'body' => $bloomCategories->get() ], 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // dd($request->complex);
        foreach($request->complex as $complexity){
            $blooms=BloomCategory::updateOrCreate(['name' => $complexity],['current' => 1]);
            $ids[] = $blooms->id; 
        }

        BloomCategory::whereNotIn('id',$ids)->update(['current'=>0]);

        $job = (new \App\Jobs\MapComplexityJob($request->map,$ids));
        dispatch($job);

        return response()->json(['message' => __('messages.bloom_category.add'), 'body' => null ], 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function singleReport(Request $request)
    {
        $request->validate([
            'quiz_id' => 'required|integer|exists:quizzes,id',
            'lesson_id' => 'required|integer|exists:lessons,id',
            'student_id' => 'exists:users,id',
        ]);
        $quizLesson=QuizLesson::where('quiz_id',$request->quiz_id)->where('lesson_id',$request->lesson_id)->first();
        $attempts=UserQuiz::where('quiz_lesson_id',$quizLesson->id)->where('user_id',$request->student_id);

        if($quizLesson->grading_method_id[0] == 'Last')
            $attempt=$attempts->latest()->first();
            
        if($quizLesson->grading_method_id[0] == 'First')
            $attempt=$attempts->first();
        
        if($quizLesson->grading_method_id[0] == 'Highest')
            $attempt=$attempts->orderBy('grade','desc')->first();

        if($quizLesson->grading_method_id[0] == 'Lowest')
            $attempt=$attempts->orderBy('grade','asc')->first();

        if($quizLesson->grading_method_id[0] != 'Average')
            $questionAnswers=$attempt->UserQuizAnswer;

        if($quizLesson->grading_method_id[0] == 'Average')
            $questionAnswers=UserQuizAnswer::whereIn('user_quiz_id',$attempts->pluck('id'))->get();

        foreach($questionAnswers as $key => $UQA)
        {
            $count[$UQA->Question->Bloom->name][$key] =1;
            // $bloom[]=BloomCategory::select(DB::raw
            // (  "COUNT(case `id` when ".$UQA->Question->Bloom->id . " then 1 else null end) as " . $UQA->Question->Bloom->name .""))->first()->only($UQA->Question->Bloom->name);
        }
        foreach($count as $key=>$value)
            $cout[$key]=count($value);

        $a=[];
        foreach($cout as $key => $cc)
        {
            $daragat=0;
            foreach($questionAnswers as $answer)
            {
                if($answer->Question->Bloom->name == $key)
                    $daragat+=$answer->user_grade;
            }
            $a[$key]=$daragat/$cc;
        }

        return HelperController::api_response_format(200, $a, 'Statistices');
    }
}
