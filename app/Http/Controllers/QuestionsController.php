<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Modules\QuestionBank\Entities\quiz;
use Modules\QuestionBank\Entities\Questions;
use Modules\QuestionBank\Entities\QuestionsType;
use App\Repositories\ChainRepositoryInterface;
use App\Enroll;
use Validator;
use App\Paginate;
use App\Events\GradeItemEvent;
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
        // $this->middleware(['permission:question/get' , 'ParentCheck'],   ['only' => ['index']]);
        // $this->middleware(['permission:question/add' ],   ['only' => ['store']]);
        $this->middleware(['permission:question/delete'],   ['only' => ['destroy']]);
        $this->middleware(['permission:question/update'],   ['only' => ['update']]);
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
            'Question_Category_id' => 'array',
            'Question_Category_id.*' => 'integer|exists:questions_categories,id',
            'question_type' => 'array',
            'question_type.*' => 'integer|exists:questions_types,id',
            'search' => 'nullable|string'
        ]);
        //to get all questions in quiz id //quizzes/{quiz_id}/{questions}'
        if($question=='questions'){
            $quset=array();
            $quiz = Quiz::where('id',$quiz_id)->with('Question.children')->first();
            $questions = $quiz->Question;
            if($quiz->shuffle == 'Questions'|| $quiz->shuffle == 'Questions and Answers')
                $questions =$questions->shuffle();
            
            foreach($questions as $question){
                $children_mark = 0;
                self::mark_details_of_question_in_quiz($question ,$quiz);
                if(isset($question->children)){
                    foreach($question->children as $child){
                        $childd = self::mark_details_of_question_in_quiz($child ,$quiz);
                        $children_mark += $childd->mark;
                    }
                    $question->mark += $children_mark;
                }
            }
            
            if($quiz->shuffle == 'Answers'|| $quiz->shuffle == 'Questions and Answers'){
                foreach($questions as $question){
                    if($question['question_type_id'] == 2){ // MCQ
                        $re=collect($question['content'])->shuffle();
                        $question['content']= json_encode($re);
                    }
                }
            }
            
            return response()->json(['message' => __('messages.question.list'), 'body' => $questions], 200);
        }

        $user_course_segments = $this->chain->getCourseSegmentByChain($request);
        if(!$request->user()->can('site/show-all-courses'))//student
            $user_course_segments = $user_course_segments->where('user_id',Auth::id());

        $course_ides = $user_course_segments->with('courseSegment')->get()->pluck('courseSegment.course_id')->unique()->values();

        $questions = Questions::whereIn('course_id',$course_ides)->where('parent',null)->where('survey',0)->with(['course','question_category','question_type','children']);

        if($request->filled('search'))
           $questions->where('text', 'LIKE' , "%$request->search%");
        
        if (isset($request->Question_Category_id)) 
            $questions->whereIn('question_category_id', $request->Question_Category_id);
        
        if (isset($request->question_type)) 
            $questions->whereIn('question_type_id', $request->question_type);
        
        //using api quizzes/null/count 
        if($question == 'count'){
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
     * View mark details of questions while listing them in a quiz
     *
     * @return \Illuminate\Http\Response
     */
    public static function mark_details_of_question_in_quiz($question ,$quiz){
        $quiz_question=quiz_questions::where('quiz_id',$quiz->id)->where('question_id',$question->id)->first();
        if(isset($quiz_question->grade_details)){
        $question['grade_details']=$quiz_question->grade_details;
        if($question['question_type_id'] == 3){
            $questi['match_a']=collect($question['content']['match_a'])->shuffle();
            $questi['match_b']=collect($question['content']['match_b'])->shuffle();
            $question['content']= json_encode($questi);
            $question->mark = $quiz_question->grade_details->total_mark;
        }
        if($question['question_type_id'] == 1 || $question['question_type_id'] == 4){
            if(isset($quiz_question->grade_details->total_mark))
                $question->mark = $quiz_question->grade_details->total_mark;
            $combined_content =(object) array_merge((array) $quiz_question->grade_details, (array) $question->content);
            $question['content']= json_encode($combined_content);
        }
        if($question['question_type_id'] == 2 ){
                if(($quiz_question->grade_details != null)){
                    $question->content = json_encode($quiz_question->grade_details->details);
                    $question->mark = $quiz_question->grade_details->total_mark;
                    $question->mcq_type = $quiz_question->grade_details->type;
            }
        }
    }
        return $question;
    }

    /**
     * assign a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function assign(Request $request, $quiz_id=null, $question=null)
    {
        //to assign questions in quiz id //quizzes/{quiz_id}/{questions}'
        if($question=='questions'){
            $request->validate([
                'questions' => 'required|array',
                'questions.*.id' => 'exists:questions,id',
            ]);
            $quiz=Quiz::find($quiz_id);
            // $quiz->Question()->attach($request->questions); //attach repeat the raw
            foreach($request->questions as $question){
                $type = Questions::find($question['id'])->question_type_id;
                
                switch ($type) {
                    case 1: // True/false
                        $true_false = $this->Assign_TF($question,$quiz);
                        break;
    
                    case 2: // MCQ
                        $mcq = $this->Assign_MCQ($question,$quiz);
                        break;
    
                    case 3: // Match
                        $match = $this->Assign_Match($question,$quiz);
                        break;
    
                    case 4: // Essay
                        $essay = $this->Assign_Essay($question,$quiz);
                        break;
                    
                    case 5: // Paragraph
                        $comprehension=$this->Assign_Paragraph($question,$quiz);
                        break;    
                }
            }
            $quiz->draft=0;
            $quiz->save();
            return HelperController::api_response_format(200,null , __('messages.quiz.assign'));
        }
    }

    public function Assign_TF($question , $quiz){
        $validator = Validator::make($question, [
            'is_true' => 'required|boolean',
            'mark_tf' => 'required|between:0,99.99',
            'and_why' => 'required|boolean',
            'and_why_mark' => 'required|between:0,99.99',
        ]);
        if ($validator->fails())
            throw new \Exception(__('messages.error.data_invalid'));

        $mark_details['total_mark']  =$question['mark_tf'] + $question['and_why_mark'];
        $mark_details['is_true']  = $question['is_true'];
        $mark_details['mark']  = $question['mark_tf'];
        $mark_details['and_why']  = $question['and_why'];
        $mark_details['and_why_mark']  = $question['and_why_mark'];

        quiz_questions::updateOrCreate(
            ['question_id'=>$question['id'], 'quiz_id' => $quiz->id,],
            ['grade_details' => json_encode($mark_details)]
        );
    }

    public function Assign_MCQ($question , $quiz){
        $validator = Validator::make($question, [
            'mcq_type' => 'required|in:1,2,3',
            'MCQ_Choices' => 'required|array',
            'MCQ_Choices.*.is_true' => 'required|boolean',
            'MCQ_Choices.*.mark' => 'required|between:0,99.99',
        ]);
        if ($validator->fails())
            throw new \Exception(__('messages.error.data_invalid'));

       // types of mcq
        // 1 single
        // 2 multi
        // 3 partial
        $total_mark = 0;
        foreach($question['MCQ_Choices'] as $key=>$mcq)
        {
            $mcq['key']=++$key;
            $mark_details['type']=$question['mcq_type'];
            $mark_details['details'][]=$mcq;
            $total_mark += $mcq['mark'];
        }
        $mark_details['total_mark'] = $total_mark;

        quiz_questions::updateOrCreate(
            ['question_id'=>$question['id'], 'quiz_id' => $quiz->id,],
            ['grade_details' => json_encode($mark_details)]
        );
    }

    public function Assign_Match($question , $quiz){
        $validator = Validator::make($question, [
            'match_a' => 'required|array|min:1|distinct',
            'match_b' => 'required|array|min:1|distinct',
            'mark_match' => 'required|array',
        ]);
        if ($validator->fails())
            throw new \Exception(__('messages.error.data_invalid'));

            foreach($question['match_a'] as $key=>$mat_a){
                $matA[]=[++$key=>$mat_a];
                $match['match_a']=$matA;
            }
            foreach($question['match_b'] as $key=>$mat_b){
                $matB[]=[++$key=>$mat_b];
                $match['match_b']=$matB;
            }
            foreach($question['mark_match'] as $key=>$mark_match){
                $marks_matchh[]=[++$key=>$mark_match];
                $match['mark']=$marks_matchh;
            }
            $match['total_mark']=array_sum($question['mark_match']);
            $mark_details = $match;

        quiz_questions::updateOrCreate(
            ['question_id'=>$question['id'], 'quiz_id' => $quiz->id,],
            ['grade_details' => json_encode($mark_details)]
        );
      
    }

    public function Assign_Essay($question , $quiz){
        $validator = Validator::make($question, [
            'mark_essay' => 'required|between:0,99.99',
        ]);
        if ($validator->fails())
            throw new \Exception(__('messages.error.data_invalid'));

        $mark_details['total_mark']  = $question['mark_essay'];

        quiz_questions::updateOrCreate(
            ['question_id'=>$question['id'], 'quiz_id' => $quiz->id,],
            ['grade_details' => json_encode($mark_details)]
        );
    }

    public function Assign_Paragraph($question , $quiz){
        quiz_questions::updateOrCreate(
            ['question_id'=>$question['id'], 'quiz_id' => $quiz->id,],
            ['grade_details' => null]
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, $quiz_id=null, $question=null)
    {
        $request->validate([
            //for request of creation multi type questions
            'Question' => 'required|array',
            'Question.*.course_id' => 'required|integer|exists:courses,id', // because every question has course_id
            'Question.*.question_category_id' => 'required|integer|exists:questions_categories,id',
            'Question.*.question_type_id' => 'required|exists:questions_types,id', 
            'Question.*.parent_id' => 'exists:questions,id',
            'Question.*.text' => 'required|string', //need in every type_question
        ]);
        
        $all=collect([]);
        foreach ($request->Question as $index => $question) {
            $parent = null;
            if(isset($question['parent_id']))
                $parent = $question['parent_id'];

            switch ($question['question_type_id']) {
                case 1: // True/false
                    $true_false = $this->T_F($question,$parent);
                    $all->push($true_false);
                    break;

                case 2: // MCQ
                    $mcq = $this->MCQ($question,$parent);
                    $all->push($mcq);
                    break;

                case 3: // Match
                    $match = $this->Match($question,$parent);
                    $all->push($match);
                    break;

                case 4: // Essay
                    $essay = $this->Essay($question,$parent);
                    $all->push($essay); //essay not have special answer
                    break;

                case 5: // Comprehension
                    $comprehension=$this->Comprehension($question);
                    // $comprehension->children;
                    $all->push($comprehension);
                    break;
            }
        }

        return HelperController::api_response_format(200, $all , __('messages.question.add'));
    }

    public function T_F($question,$parent)
    {
        $validator = Validator::make($question, [
            'and_why' => 'required|boolean',
            'is_true' => 'required|boolean',
        ]);

        if ($validator->fails())
            return HelperController::api_response_format(400, $validator->errors(), __('messages.error.data_invalid'));
        
        $t_f=array();
        $data = [
            'course_id' => $question['course_id'],
            'question_category_id' => $question['question_category_id'],
            'question_type_id' => $question['question_type_id'],
            'text' => $question['text'],
            'parent' => isset($parent) ? $parent : null,
            'created_by' => Auth::id(),
        ];
        $t_f['is_true'] = ($question['is_true']==0) ? False : True;
        $t_f['and_why'] = ($question['and_why']==0) ? False : True;
        $data['content'] = json_encode($t_f);

        $added=Questions::firstOrCreate($data); //firstOrCreate doesn't work because it has json_encode

        return $added;
    }

    public function MCQ($question,$parent)
    {
        $validator = Validator::make($question, [
            'MCQ_Choices' => 'required|array',
            'MCQ_Choices.*.is_true' => 'required|boolean',
            'MCQ_Choices.*.content' => 'required|string',
        ]);

        if ($validator->fails())
            return HelperController::api_response_format(400, $validator->errors(), __('messages.error.data_invalid'));
        
        $data = [
            'course_id' => $question['course_id'],
            'question_category_id' => $question['question_category_id'],
            'question_type_id' => $question['question_type_id'],
            'text' => $question['text'],
            'parent' => isset($parent) ? $parent : null,
            'created_by' => Auth::id(),
            // 'content' => json_encode(($question['MCQ_Choices'])),
        ];
        foreach($question['MCQ_Choices'] as $key=>$mcq)
        {
            $mcq['key']=++$key;
            unset($mcq['mark']);
            $arr[]=$mcq;
        }

        $data['content'] = json_encode($arr);

        $added=Questions::firstOrCreate($data); //firstOrCreate doesn't work because it has json_encode

        return $added;
    }

    public function Match($question,$parent)
    {
        $validator = Validator::make($question, [
            'match_a' => 'required|array|min:1|distinct',
            'match_b' => 'required|array|min:1|distinct',
        ]);

        if ($validator->fails())
            return HelperController::api_response_format(400, $validator->errors(), __('messages.error.data_invalid'));
        
        $match=array();
        $matA=array();
        $matB=array();
        $data = [
            'course_id' => $question['course_id'], 
            'question_category_id' => $question['question_category_id'],
            'question_type_id' => $question['question_type_id'],
            'text' => $question['text'],
            'parent' => isset($parent) ? $parent : null,
            'created_by' => Auth::id(),
        ];
        foreach($question['match_a'] as $key=>$mat_a){
            $matA[]=[++$key=>$mat_a];
            $match['match_a']=$matA;
        }
        foreach($question['match_b'] as $key=>$mat_b){
            $matB[]=[++$key=>$mat_b];
            $match['match_b']=$matB;
        }
        $data['content'] = json_encode($match);

        $added=Questions::firstOrCreate($data); //firstOrCreate doesn't work because it has json_encode

        return $added;
    }

    public function Essay($question,$parent)
    {
        $data = [
            'course_id' => $question['course_id'],
            'question_category_id' => $question['question_category_id'],
            'question_type_id' => $question['question_type_id'],
            'text' => $question['text'],
            'parent' => isset($parent) ? $parent : null,
            'created_by' => Auth::id(),
            'content' => null //not have specific|model answer
        ];

        $added=Questions::firstOrCreate($data); //firstOrCreate doesn't work because it has json_encode

        return $added;
    }

    public function Comprehension($question)
    {
        $added=self::Essay($question,null); //same data saved of Essay Question
        return $added;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $question=Questions::find($id);
        return HelperController::api_response_format(200, $question,null);
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
        $request->validate([
            //for interface model
            'course_id' => 'integer|exists:courses,id',
            'question_category_id' => 'integer|exists:questions_categories,id',
            'question_type_id' => 'integer|exists:questions_types,id',
            //for request of creation multi type questions
            'text' => 'string', //need in every type_question
        ]);
        
        $data=array();
        $question = Questions::find($id);

        $quest=$question->update([
            'course_id' => isset($request->course_id) ? $request->course_id : $question->course_id,
            'question_category_id' => isset($request->question_category_id) ? $request->question_category_id : $question->question_category_id,
            'question_type_id' => isset($request->question_type_id) ? $request->question_type_id : $question->question_type_id,
            'created_by' => Auth::id(),
            'text' => isset($request->text) ? $request->text : $question->text,
        ]);
        $question->save();
        switch ($question->question_type_id) {
            case 1: // True_false
                # code...
                $t_f['is_true'] = ($request->is_true==0) ? False : True;
                $t_f['and_why'] = ($request->and_why==0) ? False : True;
                $data['content'] = json_encode($t_f);
                break;

            case 2: // MCQ
                $data['content'] = isset($request->MCQ_Choices) ? json_encode($request->MCQ_Choices) : $question->MCQ_Choices;
                break;

            case 3: // Match
                $match['match_a']=isset($request->match_a) ? $request->match_a : $question->content->match_a;
                $match['match_b']=isset($request->match_b) ? $request->match_b : $question->content->match_b;
                $data['content'] = json_encode($match);
                break;

            case 4: // Essay
                $data['content'] = $question->content; //essay not have special answer
                break;

            case 5: // Paragraph
                $data['content'] = $question->content; 
                $question->children;
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
