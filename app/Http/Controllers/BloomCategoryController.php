<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\BloomCategory;
use Modules\QuestionBank\Entities\Questions;
use Modules\QuestionBank\Entities\QuizLesson;
use App\SecondaryChain;
use Modules\QuestionBank\Entities\UserQuiz;
use Modules\QuestionBank\Entities\Quiz;
use Modules\QuestionBank\Entities\UserQuizAnswer;
use Modules\QuestionBank\Entities\quiz;
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
            'student_id' => 'exists:users,id',
            'classes' => 'array|exists:classes,id'
        ]);

        $quiz=Quiz::find($request->quiz_id);
        $quizLessons=QuizLesson::where('quiz_id',$request->quiz_id)->get();
        if(isset($request->student_id))
            $attemptss=UserQuiz::where('user_id',$request->student_id)->whereIn('quiz_lesson_id',$quizLessons->pluck('id'));

        else if(isset($request->classes)){
            $users=SecondaryChain::select('user_id')->distinct()->whereIn('group_id',$request->classes)->pluck('user_id');
            $attemptss=UserQuiz::whereIn('user_id',$users)->whereIn('quiz_lesson_id',$quizLessons->pluck('id'));
        }

        else
            $attemptss=UserQuiz::whereIn('quiz_lesson_id',$quizLessons->pluck('id'));

        $questionAnswers=array();
        foreach( $attemptss->cursor() as $att)
        {
            $attempts=UserQuiz::whereIn('quiz_lesson_id',$quizLessons->pluck('id'))->where('user_id',$att->user_id);

            if($quizLessons[0]->grading_method_id[0] == 'Last')
                $attempt=$attempts->latest()->first();
                
            if($quizLessons[0]->grading_method_id[0] == 'First')
                $attempt=$attempts->first();
            
            if($quizLessons[0]->grading_method_id[0] == 'Highest')
                $attempt=$attempts->orderBy('grade','desc')->first();

            if($quizLessons[0]->grading_method_id[0] == 'Lowest')
                $attempt=$attempts->orderBy('grade','asc')->first();

            if($quizLessons[0]->grading_method_id[0] != 'Average'){
                $iteration=$attempt->UserQuizAnswer;
                foreach($iteration as $one)
                    array_push($questionAnswers,$one);
            }

            if($quizLessons[0]->grading_method_id[0] == 'Average'){
                $iteration = UserQuizAnswer::whereIn('user_quiz_id',$attempts->pluck('id'))->get();
                $iteration=$attempt->UserQuizAnswer;
                foreach($iteration as $one)
                    array_push($questionAnswers,$one);
            }
        }

        $a=[];
        $count=[];
        $cout=[];
        foreach($questionAnswers as $key => $UQA){
            if(!isset($UQA->Question->Bloom))
                continue;
            $count[$UQA->Question->Bloom->name][$key] =1;
        }

        foreach($count as $key=>$value)
            $cout[$key]=count($value);

        // $cout dh kam mara l so2al dah et7al(etfata7 mn kam attempt)

        $BloomCount=[];
        foreach($quiz->Question as $question){
            if(!isset($question->Bloom))
                continue;
            $BloomCount[$question->Bloom->name][$key] =1;
        }

        foreach($BloomCount as $key=>$value)
            $BloomCounts[$key]=count($value);


        foreach($cout as $key => $cc)
        {
            $daragat=0;
            foreach($questionAnswers as $answer)
            {
                if(!isset($UQA->Question->Bloom))
                    continue;
                
                if($answer->Question->Bloom->name == $key)
                // l daragat d kol l nesab beta3et l so2al
                    $daragat+=$answer->user_grade;
            }
            // $a l result
            $a[$key]=round($daragat/$cc,2);
        }
        $a['question_bloom_count']=$BloomCounts;

        return HelperController::api_response_format(200, $a, 'Statistices');
    }

    public function countQuestions(Request $request)
    {
        $request->validate([
            'quiz_id' => 'required|integer|exists:quizzes,id',
        ]);

        $quiz = quiz::whereId($request->quiz_id)->select('id as quiz_id')->withCount('Question as questions_count')
        ->withCount(['Question as bloom_questions_count' => function($query) use ($request){
            $query->whereNotNull('complexity');
        }])->first();

        return response()->json(['message' => null, 'body' => $quiz ], 200); 
    }

}

