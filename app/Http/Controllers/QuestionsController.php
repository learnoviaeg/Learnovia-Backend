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
            'search' => 'nullable|string',

        ]);
        //to get all questions in quiz id //quizzes/{quiz_id}/{questions}'
        if($question=='questions'){
            $quiz_shuffle = Quiz::where('id', $quiz_id)->pluck('shuffle')->first();
            // $quiz = Quiz::find( $quiz_id);
            $questions = quiz_questions::where('quiz_id',$quiz_id)
                    ->with(['Question.question_answer','Question.question_category','Question.question_type'])->get()
                    ->pluck('Question.*')->collapse();
            if($quiz_shuffle == 'Questions'|| $quiz_shuffle == 'Questions and Answers'){
                $questions =$questions->shuffle();
            }
            if($quiz_shuffle == 'Answers'|| $quiz_shuffle == 'Questions and Answers'){
                foreach($questions as $question){
                $answers = $question->question_answer->shuffle();
                unset($question->question_answer);
                $question['question_answer'] =$answers;
                }
            }
            
            return response()->json(['message' => __('messages.question.list'), 'body' => $questions->paginate(Paginate::GetPaginate($request))], 200);
        }

        $user_course_segments = $this->chain->getCourseSegmentByChain($request);
        if(!$request->user()->can('site/show-all-courses')){//student
                $user_course_segments = $user_course_segments->where('user_id',Auth::id());
            }

        $course_ides = $user_course_segments->with('courseSegment')->get()->pluck('courseSegment.course_id')->unique()->values();

        $questions = Questions::whereIn('course_id',$course_ides)->where('survey',0)->with(['course','question_answer','question_category','question_type']);

        if($request->filled('search'))
        {
           $questions->where('text', 'LIKE' , "%$request->search%");
        }
        if (isset($request->Question_Category_id)) {
            $questions->whereIn('question_category_id', $request->Question_Category_id);
        }
        if (isset($request->question_type)) {
            $questions->whereIn('question_type_id', $request->question_type);
        }
        
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
    public function store(Request $request,$type=null)
    {
        $request->validate([
            //for interface model
            'course_id' => 'required|integer|exists:courses,id',
            'question_category_id' => 'required|integer|exists:questions_categories,id',
            //for request of creation multi type questions
            'Question' => 'required|array',
            'Question.*.question_type_id' => 'required|exists:questions_types,id', 
            'Question.*.text' => 'required|string', //need in every type_question
            'Question.*.is_true' => 'required_if:Question.*.type,==,T_F|boolean', //for true-false
            'Question.*.and_why' => 'boolean', //if question t-f and have and_why question
            //MCQ validation
            'Question.*.MCQ_Choices' => 'required_if:Question.*.type,==,MCQ|array',
            'Question.*.MCQ_Choices.*.is_true' => 'required_if:Question.*.type,==,MCQ|boolean',
            'Question.*.MCQ_Choices.*.content' => 'required_if:Question.*.type,==,MCQ|string',
            //Comprehension 
            'Question.*.subQuestion' => 'array|required_if:Question.*.type,==,Comprehension',
            'Question.*.subQuestion.*.type' => 'required_if:Question.*.type,==,Comprehension|in:MCQ,Essay,T_F,Match',
            'Question.*.subQuestion.*.text' => 'required_if:Question.*.type,==,Comprehension|string',
            'Question.*.subQuestion.*.is_true' => 'required_if:Question.*.subQuestion.*.type,==,T_F|boolean', //for true-false
            'Question.*.subQuestion.*.and_why' => 'boolean', //if question t-f and have and_why question
            'Question.*.subQuestion.*.MCQ_Choices' => 'required_if:Question.*.subQuestion.*.type,==,MCQ|array',
            'Question.*.subQuestion.*.MCQ_Choices.*.is_true' => 'required_if:Question.*.subQuestion.*.type,==,MCQ|boolean',
            'Question.*.subQuestion.*.MCQ_Choices.*.content' => 'required_if:Question.*.subQuestion.*.type,==,MCQ|string',
            //Match
            // 'Question.*.matches' => 'required_if:Question.*.type,==,Match|array',
            'Question.*.match_a' => 'required_if:Question.*.type,==,Match|array',
            'Question.*.match_b' => 'required_if:Question.*.type,==,Match|array'
        ]);

        $data=array();
        $t_f=array();
        foreach ($request->Question as $index => $question) {
            switch ($question['question_type_id']) {
                case 1: // True_false
                    # code...
                    $t_f = [
                        'is_true' => $question['is_true'],
                    ];
                    if (isset($question['and_why']))
                        $t_f['and_why'] = $question['and_why'];
                    
                    $data['content'] = json_encode($t_f);
                    break;

                case 2: // MCQ
                    # code...
                    $request->validate([
                        'Questions.' . $index . '.MCQ_Choices' => 'required|array',
                    ]);
                    // $data['user_grade'] = ($flag == 1) ? $currentQuestion->mark : 0;

                    $data['content'] = json_encode($question['MCQ_Choices']);
                    break;

                case 3: // Match
                    $answers=collect();
                    # code...
                    $request->validate([
                        'Questions.' . $index . '.match_a' => 'required|array',
                        'Questions.' . $index . '.match_b' => 'required|array',
                    ]);
                    $answers['match_a']=$question['match_a'];
                    $answers['match_b'] =$question['match_b'];

                    $data['user_answer'] = json_encode($answers);
                    // $data['user_grade'] = $grade;

                    break;

                case 'Essay': // Essay
                    # code...
                    $request->validate([
                        'Questions.' . $index . '.content' => 'required|string',
                    ]);
                    $data['user_answer'] = json_encode($question['content']);
                    break;

                case 'Comprehension': // Paragraph
                    # code...
                    $request->validate([
                        'Questions.*.question' => 'required|array',
                        'Questions.*.question.*.id' => 'required',
                        'Questions.*.question.*.type' => 'required|in:MCQ,Essay,T_F,Match',
                        // if essay
                        'Questions.*.question.*.content' => 'required_if:Questions.*.question.*.type,==,Essay|string',
                        // if match
                        'Questions.*.question.*.match_a' => 'required_if:Questions.*.question.*.type,==,Match|array',
                        'Questions.*.question.*.match_b' => 'required_if:Questions.*.question.*.type,==,Match|array',
                        // if mcq
                        'Questions.*.question.*.MCQ_Choices' => 'required_if:Questions.*.question.*.type,==,MCQ|array',
                        //if t/f
                        'Questions.*.question.*.is_true' => 'required_if:Questions.*.question.*.type,==,T_F|boolean',
                        'Questions.*.question.*.and_why' => 'nullable|string',              
                    ]);

                    foreach($question['question'] as $one)
                    {
                        if($one['type'] == 'Essay')
                            $data['user_answer'] = json_encode($one['content']);

                        if($one['type'] == 'MCQ')
                            $data['user_answer'] = json_encode($one['MCQ_Choices']);

                        if($one['type'] == 'T_F'){
                            $t_f = [
                                'is_true' => $one['is_true'],
                            ];
                            if (isset($one['and_why']))
                                $t_f['and_why'] = $one['and_why'];
                            
                            $data['user_answer'] = json_encode($t_f);
                        }
                        if($one['type'] == 'Match'){
                            $answers['match_a']=$one['match_a'];
                            $answers['match_b'] =$one['match_b'];
    
                            $data['user_answer'] = json_encode($answers);
                        }
                    }

                    break;
            }
            $allData->push($data);
        }foreach ($request->Question as $question) {
            $quest=Questions::create([
                'course_id' => $request->course_id,
                'question_category_id' => $request->question_category_id,
                'created_by' => Auth::id(),
                'text' => $question['text'],
                'question_type_id' => $$question['question_type_id']
            ]);
            $quests[]=$quest->id;
            $matches=collect();
            if($question['type'] == 'Comprehension')
                foreach($question['subQuestion'] as $sub){
                    if($sub['type'] == 'MCQ')
                        Q_MCQ::create([
                            'question_id' => $quest->id,
                            'text' => $sub['text'],
                            'choices' => json_encode($sub['MCQ_Choices']),
                        ]);
                    else
                        $q= $quest->{$sub['type'].'_question'}()->create($sub); //firstOrNew //insertOrIgnore //createOrFirst doen't work
                }

            elseif($question['type'] == 'MCQ')
                $data=[
                    'question_id' => $quest->id,
                    'text' => $question['text'],
                    'choices' => json_encode($question['MCQ_Choices']),
                ];

            elseif($question['type'] == 'Match'){
                $matches['match_a']=$question['match_a'];
                $matches['match_b'] =$question['match_b'];
                Q_Match::create([
                    'question_id' => $quest->id,
                    'text' => $question['text'],
                    'matches' => json_encode($matches),
                ]);
            }
            else
                $q= $quest->{$question['type'].'_question'}()->create($question);
        }
        if($type == 1)  
            return $quests;

        return HelperController::api_response_format(200, [], __('messages.question.add'));
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
}
