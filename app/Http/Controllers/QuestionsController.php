<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Modules\QuestionBank\Entities\quiz;
// use Modules\QuestionBank\Entities\Questions;
use App\Repositories\ChainRepositoryInterface;
use App\Enroll;
use App\Paginate;
use App\Q_MCQ;
use App\Q_Match;
use App\Questions;
use Modules\QuestionBank\Entities\QuizQuestions;
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
            'Question_Category_id' => 'array',
            'Question_Category_id.*' => 'integer|exists:questions_categories,id',
            'type' => 'array',
            'type.*' => 'string|in:MCQ,Essay,T_F,Match,Comprehension',
            'search' => 'nullable|string',
            'quiz_id' => 'nullable|exists:quizzes,id',
        ]);
        $questtion=collect();
        $types=['MCQ','Essay','Comprehension','T_F','Match'];
        $shuffle=null;

        $quest=Questions::query();

        if($request->filled('courses'))
            $quest->whereIn('course_id',$request->courses);

        if($request->filled('Question_Category_id'))
            $quest->whereIn('q_cat_id',$request->Question_Category_id);
            
        if($request->filled('search'))
            $quest->where('text', 'LIKE' , "%$request->search%");

        if($request->filled('type'))
            $quest->whereIn('type' ,$request->type);

        if($request->filled('quiz_id')){    
            $shuffle = Quiz::where('id', $request->quiz_id)->pluck('shuffle')->first();

            $question_in_quiz= QuizQuestions::where('quiz_id',$request->quiz_id)->pluck('question_id');
            $quest->whereIn('id',$question_in_quiz);
        }

        foreach($types as $type){
            $current=clone $quest;

            if($type == 'Comprehension')
                $questtion=$questtion->merge($current->where('type',$type)->with('T_F_question','Essay_question','MCQ_question')->get());

            else
                $questtion=$questtion->merge($current->where('type',$type)->with($type.'_question')->get());
        }

        // quizzes/null/count
        if($question == 'count'){
            $counts = collect([]);
            $counts['essay'] = 0;
            $counts['tf'] = 0;
            $counts['mcq'] = 0;
            $counts['comprehension'] = 0;
            $counts['match'] = 0;

            $counts['tf'] = $questtion->where('type','T_F')->count();
            $counts['mcq'] = $questtion->where('type','MCQ')->count();
            $counts['essay'] = $questtion->where('type','Essay')->count();
            $counts['comprehension'] = $questtion->where('type','Comprehension')->count();
            $counts['match'] = $questtion->where('type','Match')->count();

            return response()->json(['message' => __('messages.question.count'), 'body' => $counts], 200);
        }

        if($shuffle == 'Questions'|| $shuffle == 'Questions and Answers')
            $questtion= $questtion->shuffle();
            
        // if($shuffle == 'Answers'|| $shuffle == 'Questions and Answers')
            foreach($questtion as $question)
                if($question->type == 'MCQ'){
                    $qq=$question->MCQ_question[0]->mcq_choices->toArray();
                    $question->MCQ_question[0]->mcq_choices = shuffle($qq);
                }

        return response()->json(['message' => __('messages.question.list'), 'body' => $questtion->paginate(Paginate::GetPaginate($request))], 200);
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
            'q_cat_id' => 'required|integer|exists:questions_categories,id',
            //for request of creation multi type questions
            'Question' => 'required|array',
            'Question.*.type' => 'required|in:MCQ,Essay,T_F,Match,Comprehension', 
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

        foreach ($request->Question as $question) {
            $quest=Questions::firstOrCreate([
                'course_id' => $request->course_id,
                'q_cat_id' => $request->q_cat_id,
                'created_by' => Auth::id(),
                'text' => $question['text'],
                'type' => $question['type']
            ]);
            $quests[]=$quest->id;
            $matches=collect();
            if($question['type'] == 'Comprehension')
                foreach($question['subQuestion'] as $sub){
                    if($sub['type'] == 'MCQ')
                        Q_MCQ::firstOrCreate([
                            'question_id' => $quest->id,
                            'text' => $sub['text'],
                            'choices' => json_encode($sub['MCQ_Choices']),
                        ]);
                    else
                        $q= $quest->{$sub['type'].'_question'}()->create($sub); //firstOrNew //insertOrIgnore //createOrFirst doen't work
                }

            elseif($question['type'] == 'MCQ')
                Q_MCQ::firstOrCreate([
                    'question_id' => $quest->id,
                    'text' => $question['text'],
                    'choices' => json_encode($question['MCQ_Choices']),
                ]);

            elseif($question['type'] == 'Match'){
                $matches['match_a']=$question['match_a'];
                $matches['match_b'] =$question['match_b'];
                Q_Match::firstOrCreate([
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
