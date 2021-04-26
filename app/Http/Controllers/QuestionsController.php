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
        $this->middleware(['permission:question/add' ],   ['only' => ['store']]);
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
            $quiz = Quiz::find($quiz_id);
            $questions = $quiz->Question;
            if($quiz->shuffle == 'Questions'|| $quiz->shuffle == 'Questions and Answers')
                $questions =$questions->shuffle();
            
            if($quiz->shuffle == 'Answers'|| $quiz->shuffle == 'Questions and Answers'){
                foreach($questions as $question){
                    if($question['question_type_id'] == 2){ // MCQ
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
    public function store(Request $request, $quiz_id=null, $question=null)
    {
        $request->validate([
            //for request of creation multi type questions
            'Question' => 'required|array',
            'Question.*.course_id' => 'required|integer|exists:courses,id', // because every question has course_id
            'Question.*.question_category_id' => 'required|integer|exists:questions_categories,id',
            'Question.*.question_type_id' => 'required|exists:questions_types,id', 
            'Question.*.text' => 'required|string', //need in every type_question
        ]);

        //to assign questions in quiz id //quizzes/{quiz_id}/{questions}'
        if($question=='questions'){
            $request->validate([
                'questions' => 'required|array',
                'questions.*' => 'exists:questions,id'
            ]);
            $quiz=Quiz::find($quiz_id);
            // $quiz->Question()->attach($request->questions); //attach repeat the raw
            foreach($request->questions as $question)
                quiz_questions::firstOrCreate([
                    'question_id'=>$question,
                    'quiz_id' => $request->quiz_id,
                ]);
                
            $quiz->draft=0;
            $quiz->save();
    
            return HelperController::api_response_format(200,null , __('messages.quiz.assign'));
        }
        
        $all=collect([]);
        foreach ($request->Question as $index => $question) {

            switch ($question['question_type_id']) {
                case 1: // True/false
                    $true_false = $this->T_F($question,null);
                    $all->push($true_false);
                    break;

                case 2: // MCQ
                    $mcq = $this->MCQ($question,null);
                    $all->push($mcq);
                    break;

                case 3: // Match
                    $match = $this->Match($question,null);
                    $all->push($match);
                    break;

                case 4: // Essay
                    $essay = $this->Essay($question,null);
                    $all->push($essay); //essay not have special answer
                    break;

                case 5: // Comprehension
                    $comprehension=$this->Comprehension($question);
                    $comprehension->children;
                    $all->push($comprehension);
                    break;
            }
        }

        return HelperController::api_response_format(200, $all , __('messages.question.add'));
    }

    public function T_F($question,$parent)
    {
        $validator = Validator::make($question, [
            'and_why' => 'required|in:0,1|integer',
            'is_true' => 'required|in:0,1|boolean',
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
        $t_f['is_true'] = $question['is_true'];
        $t_f['and_why'] = $question['and_why'];
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
            'content' => json_encode(array_values($question['MCQ_Choices'])),
        ];

        $added=Questions::firstOrCreate($data); //firstOrCreate doesn't work because it has json_encode

        return $added;
    }

    public function Match($question,$parent)
    {
        $validator = Validator::make($question, [
            'match_a' => 'required|array|min:2|distinct',
            'match_b' => 'required|array|distinct',
        ]);

        if ($validator->fails())
            return HelperController::api_response_format(400, $validator->errors(), __('messages.error.data_invalid'));
        
        $match=array();
        $data = [
            'course_id' => $question['course_id'],
            'question_category_id' => $question['question_category_id'],
            'question_type_id' => $question['question_type_id'],
            'text' => $question['text'],
            'parent' => isset($parent) ? $parent : null,
            'created_by' => Auth::id(),
        ];
        $match['match_a']=$question['match_a'];
        $match['match_b'] =$question['match_b'];
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
        $validator = Validator::make($question, [
            'subQuestions' => 'required|array|distinct'/*|min:2*/,
            'subQuestions.*.question_type_id' => 'required|integer|exists:questions_types,id',
            'subQuestions.*.text' => 'required',
            'subQuestions.*.course_id' => 'required',
            'subQuestions.*.question_category_id' => 'required',
        ]);
        if ($validator->fails()) 
            return HelperController::api_response_format(400, $validator->errors(), __('messages.error.data_invalid'));
        
        $data = [
            'course_id' => $question['course_id'],
            'question_category_id' => $question['question_category_id'],
            'question_type_id' => $question['question_type_id'],
            'text' => $question['text'],
            'parent' => null,
            'created_by' => Auth::id(),
            'content' => null //not have specific|model answer
        ];

        $added=Questions::firstOrCreate($data); //firstOrCreate doesn't work because it has json_encode

        $quest = collect([]);
        foreach ($question['subQuestions'] as $subQuestion) {
            switch ($subQuestion['question_type_id']) {
                case 1: // True/false
                    $true_false = $this->T_F($subQuestion, $added->id);
                    $quest->push($true_false);
                    break;
                case 2: // MCQ
                    $mcq = $this->MCQ($subQuestion, $added->id);
                    $quest->push($mcq);
                    break;
                case 3: // Match
                    $match = $this->Match($subQuestion, $added->id);
                    $quest->push($match);
                    break;
                case 4: // Essay
                    $essay = $this->Essay($subQuestion, $added->id);
                    $quest->push($essay);
                    break;
            }
        }
        return $added;
    }

    public function Assign(Request $request)
    {
        $request->validate([
            'questions' => 'required|array',
            'questions.*' => 'exists:questions,id',
            'quiz_id' => 'required|integer|exists:quizzes,id',
        ]);
        $quiz=Quiz::find($request->quiz_id);
        // $quiz->Question()->attach($request->questions); //attach repeat the raw
        foreach($request->questions as $question)
            quiz_questions::firstOrCreate([
                'question_id'=>$question,
                'quiz_id' => $request->quiz_id,
            ]);
        // $quiz->Question;
        $quiz->draft=0;
        $quiz->save();

        return HelperController::api_response_format(200,null , __('messages.quiz.assign'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $َquestion=Questions::find($id);
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
            //for request of creation multi type questions
            'text' => 'string', //need in every type_question
        ]);
        
        $data=array();
        $question = Questions::find($id);

        $quest=$question->update([
            'course_id' => isset($request->course_id) ? $request->course_id : $question->course_id,
            'question_category_id' => isset($request->question_category_id) ? $request->question_category_id : $question->question_category_id,
            'created_by' => Auth::id(),
            'text' => isset($request->text) ? $request->text : $question->text,
        ]);
        switch ($question->question_type_id) {
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
                $data['content'] = $question->content; //essay not have special answer
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
