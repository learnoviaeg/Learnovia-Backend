<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Modules\QuestionBank\Entities\quiz;
use Modules\QuestionBank\Entities\Questions;
use Modules\QuestionBank\Entities\QuestionsType;
use App\Repositories\ChainRepositoryInterface;
use App\Enroll;
use App\Paginate;
use Modules\QuestionBank\Entities\quiz_questions;
use App\CourseSegment;
use Illuminate\Support\Facades\Auth;
use DB;

class QuestionsController extends Controller
{
    public function __construct(ChainRepositoryInterface $chain)
    {
        $this->chain = $chain;
        $this->middleware('auth');
        $this->middleware(['permission:question/get' , 'ParentCheck'],   ['only' => ['index']]);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request,$quiz_id=null,$question=null)
    {
        $request->validate([
            'courses'    => 'nullable|array',
            'courses.*'  => 'nullable|integer|exists:courses,id',
            'class' => 'nullable|integer|exists:classes,id',
            'Question_Category_id' => 'array',
            'Question_Category_id.*' => 'integer|exists:questions_categories,id',
            'question_type' => 'array',
            'question_type.*' => 'integer|exists:questions_types,id',
            'search' => 'nullable|string'
        ]);
        //to get all questions in quiz id //quizzes/{quiz_id}/{questions}'
        if($question=='questions'){
            $quiz = Quiz::find($quiz_id);
            $questions = $quiz->Question;
            if($quiz->shuffle == 'Questions'|| $quiz->shuffle == 'Questions and Answers')
                $questions =$questions->shuffle();
            
            if($quiz->shuffle == 'Answers'|| $quiz->shuffle == 'Questions and Answers'){
                foreach($questions as $question){
                    if($question['question_type_id'] == 2){
                        $re=collect($question['content']);
                        $question['content']=$re->shuffle();
                    }
                }
            }
            
            return response()->json(['message' => __('messages.question.list'), 'body' => $questions->paginate(Paginate::GetPaginate($request))], 200);
        }

        $user_course_segments = $this->chain->getCourseSegmentByChain($request);
        if(!$request->user()->can('site/show-all-courses'))//student
            $user_course_segments = $user_course_segments->where('user_id',Auth::id());

        $course_ides = $user_course_segments->with('courseSegment')->get()->pluck('courseSegment.course_id')->unique()->values();

        $questions = Questions::whereIn('course_id',$course_ides)->where('survey',0)->with(['course','question_category','question_type']);

        if($request->filled('search'))
           $questions->where('text', 'LIKE' , "%$request->search%");
        
        if (isset($request->Question_Category_id)) 
            $questions->whereIn('question_category_id', $request->Question_Category_id);
        
        if (isset($request->question_type)) 
            $questions->whereIn('question_type_id', $request->question_type);
        
        //using api quizzes/null/count 
        if($question == 'count'){
            if(isset($quiz_id))
                $counts = $questions->whereselect(DB::raw
                (  "COUNT(case `question_type_id` when 4 then 1 else null end) as essay ,
                    COUNT(case `question_type_id` when 1 then 1 else null end) as tf ,
                    COUNT(case `question_type_id` when 2 then 1 else null end) as mcq" 
                ))->first()->only(['essay','tf','mcq']);
            $counts = $questions->select(DB::raw
                (  "COUNT(case `question_type_id` when 4 then 1 else null end) as essay ,
                    COUNT(case `question_type_id` when 1 then 1 else null end) as tf ,
                    COUNT(case `question_type_id` when 2 then 1 else null end) as mcq" 
                ))->first()->only(['essay','tf','mcq']);

            return response()->json(['message' => __('messages.question.count'), 'body' => $counts], 200);
        }

        return response()->json(['message' => __('messages.question.list'), 'body' => $questions->get()->paginate(Paginate::GetPaginate($request))], 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request,$type=null)
    {
        $request->validate([
            //for interface model
            'course_id' => 'required|integer|exists:courses,id',
            'question_category_id' => 'required|integer|exists:questions_categories,id',

            //for request of creation multi type questions
            'Question' => 'required|array',
            'Question.*.is_comp' => 'required|in:0,1', 
            // 0 if this question not comprehension  
            // 1 if this question belong to comprehension_question
            'Question.*.question_type_id' => 'required_if:Question.*.question_type_id,==,2|exists:questions_types,id', 
            'Question.*.text' => 'required|string', //need in every type_question
            'Question.*.is_true' => 'required_if:Question.*.question_type_id,==,1|boolean', //for true-false
            'Question.*.and_why' => 'boolean', //if question t-f and have and_why question

            //MCQ validation
            'Question.*.MCQ_Choices' => 'required_if:Question.*.question_type_id,==,2|array',
            'Question.*.MCQ_Choices.*.is_true' => 'required_if:Question.*.question_type_id,==,2|boolean',
            'Question.*.MCQ_Choices.*.content' => 'required_if:Question.*.question_type_id,==,2|string',

            //Comprehension 
            'Question.*.parent_id' => 'required_if:Question.*.is_comp,==,1|exists:questions,id',

            //Match
            'Question.*.match_a' => 'required_if:Question.*.question_type_id,==,3|array',
            'Question.*.match_b' => 'required_if:Question.*.question_type_id,==,3|array'
        ]);

        $allData=collect();
        $data=array();
        $t_f=array();
        $match=array();
        foreach ($request->Question as $index => $question) {
            $data = [
                'course_id' => $request->course_id,
                'question_category_id' => $request->question_category_id,
                'question_type_id' => $question['question_type_id'],
                'text' => $question['text'],
                'parent' => isset($question['parent_id']) ? $question['parent_id'] : null,
                'created_by' => Auth::id(),
            ];
            switch ($question['question_type_id']) {
                case 1: // True_false
                    # code...
                    $t_f['is_true'] = $question['is_true'];
                    $t_f['and_why'] = isset($question['and_why']) ? $question['and_why'] : null;
                    $data['content'] = json_encode($t_f);
                    break;

                case 2: // MCQ
                    $data['content'] = json_encode(array_values($question['MCQ_Choices']));
                    break;

                case 3: // Match
                    $match['match_a']=$question['match_a'];
                    $match['match_b'] =$question['match_b'];
                    $data['content'] = json_encode($match);
                    break;

                case 4: // Essay
                    $data['content'] = json_encode($question['content']); //essay not have special answer
                    break;
            }
            $question[]=Questions::firstOrCreate($data);
            $ids[]=$question[0]->id;
        }
        if($type == 1)
            return $ids;

        return HelperController::api_response_format(200, $allData, __('messages.question.add'));
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
    public function update(Request $request, $id, $type)
    {
        $request->validate([
            //for interface model
            'course_id' => 'integer|exists:courses,id',
            'question_category_id' => 'integer|exists:questions_categories,id',
            //for request of creation multi type questions
            'text' => 'string', //need in every type_question
        ]);
        
        $data=array();
        $question = Questions::findOrFail($id);

        $quest=$question->update([
            'course_id' => isset($request->course_id) ? $request->course_id : $question->course_id,
            'question_category_id' => isset($request->question_category_id) ? $request->question_category_id : $question->question_category_id,
            'created_by' => Auth::id(),
            'text' => isset($request->text) ? $request->text : $question->text,
        ]);
        switch ($type) {
            case 1: // True_false
                # code...
                $t_f['is_true'] = isset($request->is_true) ? $request->is_true : $question->is_true;
                $t_f['and_why'] = isset($rquest->and_why) ? $request->and_why : $question->and_why;
                $data['content'] = json_encode($t_f);
                break;

            case 2: // MCQ
                $data['content'] = isset($request->MCQ_Choices) ? json_encode($request->MCQ_Choices) : $question->MCQ_Choices;
                break;

            case 3: // Match
                $match['match_a']=isset($request->match_a) ? $request->match_a : $question->match_a;
                $match['match_b']=isset($qrequest->match_b) ? $request->match_b : $question->match_b;
                $data['content'] = json_encode($match);
                break;

            case 4: // Essay
                $data['content'] = isset($request->content) ? $request->content : $question->content; //essay not have special answer
                break;
        }
        $question->content=$data['content'];
        $question->save();

        return HelperController::api_response_format(200, $question, __('messages.question.update'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $question = Questions::whereId($id)->delete();
        return HelperController::api_response_format(200, $question, __('messages.question.delete'));
    }
}
