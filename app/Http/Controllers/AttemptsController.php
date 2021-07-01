<?php

namespace App\Http\Controllers;

use App\GradeCategory;
use App\GradeItems;
use App\User;
use App\Enroll;
use App\Grader\GraderInterface;
use App\Lesson;
use App\UserGrade;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Auth;
use Carbon\Carbon;
use Modules\QuestionBank\Entities\userQuiz;
use Modules\QuestionBank\Entities\quiz;
use App\Grader\QuizGrader;
use Modules\QuestionBank\Entities\QuizLesson;
use Modules\QuestionBank\Entities\QuizOverride;
use App\Http\Controllers\HelperController;
use Modules\QuestionBank\Entities\Questions;
use Modules\QuestionBank\Entities\userQuizAnswer;
use App\Events\QuizAttemptEvent;
use App\Events\GradeAttemptEvent;
use App\LastAction;

class AttemptsController extends Controller
{
    // protected $gradeInterface;

    public function __construct(GraderInterface $gradeInterface)
    {
        $this->gradeInterface = $gradeInterface;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $request->validate([
            'quiz_id' => 'required|integer|exists:quizzes,id',
            'lesson_id' => 'required|integer|exists:lessons,id',
            'attempt_index'=>'integer|exists:user_quizzes,id',
            'user_id' => 'integer|exists:users,id',
        ]);
        $user_id=($request->user_id) ? $request->user_id : Auth::id();
        $quiz=Quiz::find($request->quiz_id);
        $attempts=UserQuiz::where('user_id',$user_id)->where('quiz_lesson_id',$quiz->quizLesson[0]->id);

        if(isset($request->attempt_index))
            $attempts->whereId($request->attempt_index);

        return HelperController::api_response_format(200, $attempts->with('UserQuizAnswer','user','quiz_lesson')->get());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'quiz_id' => 'required|integer|exists:quizzes,id',
            'lesson_id' => 'required|integer|exists:lessons,id',
        ]);
        $quiz_lesson = QuizLesson::where('quiz_id', $request->quiz_id)->where('lesson_id', $request->lesson_id)->first();
        LastAction::lastActionInCourse($quiz_lesson->lesson->courseSegment->course_id);
        $user_quiz = UserQuiz::where('user_id',Auth::id())->where('quiz_lesson_id',$quiz_lesson->id);
        
        $last_attempt=$user_quiz->latest()->first();
        $index=0;
            
        if(isset($last_attempt)) // first attempt
        {
            $index=$last_attempt->attempt_index;
            $last_attempt->left_time=$quiz_lesson->quiz->duration;  

            if($last_attempt->attempt_index == $quiz_lesson->max_attemp)
                return HelperController::api_response_format(400, null, __('messages.error.submit_limit'));

            if(Carbon::parse($last_attempt->open_time)->addSeconds($quiz_lesson->quiz->duration)->format('Y-m-d H:i:s') > Carbon::now()->format('Y-m-d H:i:s')
                 && UserQuizAnswer::where('user_quiz_id',$last_attempt->id)->whereNull('force_submit')->count() > 0)
            {
                $last_attempt->left_time=(Carbon::parse($last_attempt->open_time)->addSeconds($quiz_lesson->quiz->duration))->diffInSeconds(Carbon::now());
                foreach($last_attempt->UserQuizAnswer as $answers)
                    $answers->Question;
                return HelperController::api_response_format(200, $last_attempt, __('messages.quiz.continue_quiz'));
            }
            userQuizAnswer::where('user_quiz_id',$last_attempt->id)->update(['force_submit'=>'1']);
        }

        $userQuiz = userQuiz::create([
            'user_id' => Auth::id(),
            'quiz_lesson_id' => $quiz_lesson->id,
            'status_id' => 2,
            'feedback' => null,
            'grade' => null,
            'attempt_index' => (Auth::user()->can('site/quiz/store_user_quiz')) ? $index+1 : 0, // this permission because if these admin don't count his attempts
            'open_time' => Carbon::now()->format('Y-m-d H:i:s'),
            'submit_time'=> null,
        ]);
        $userQuiz->left_time=$quiz_lesson->quiz->duration;

        foreach($quiz_lesson->quiz->Question as $question)
        {
            if($question->question_type_id == 5)
            {
                $quest=$question->children->pluck('id');
                foreach($quest as $child)
                    userQuizAnswer::create(['user_quiz_id'=>$userQuiz->id , 'question_id'=>$child]);
            }
            else // because parent question(comprehension) not have answer
                userQuizAnswer::create(['user_quiz_id'=>$userQuiz->id , 'question_id'=>$question->id]);
        }
        foreach($userQuiz->UserQuizAnswer as $answers)
            $answers->Question;
                    
        event(new QuizAttemptEvent($userQuiz));
        
        return HelperController::api_response_format(200, $userQuiz);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $attempt=UserQuiz::whereId($id)->with('UserQuizAnswer','user','quiz_lesson')->get();
        return HelperController::api_response_format(200, $attempt);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id) //it's answer_api because we do make update really ^_^ 
    {
        $request->validate([
            // 'user_quiz_id' => 'required|integer|exists:user_quizzes,id',
            'Questions' => 'array',
            'Questions.*.id' => 'integer|exists:questions,id',
            'forced' => 'boolean',
            'Questions.*.answered' => 'in:0,1,2' 
            // 0 => question Not answered
            // 1 => question answered
            // 2 => question answered partilly
        ]);

        // check that question exist in the Quiz
        $user_quiz = userQuiz::find($id);
        
        LastAction::lastActionInCourse($user_quiz->quiz_lesson->lesson->courseSegment->course_id);

        $allData = collect([]);
        if(isset($request->Questions))
            foreach ($request->Questions as $index => $question) {
                if(isset($question['id'])){
                    $currentQuestion = Questions::find($question['id']);
                    $question_type_id = $currentQuestion->question_type->id;

                    $data = [
                        'user_quiz_id' => $id,
                        'question_id' => $question['id'],
                        'answered' => isset($question['answered']) ? $question['answered'] : 0,
                    ];
                    switch ($question_type_id) {
                        case 1: // True_false
                            # code...
                            $t_f['is_true'] = isset($question['is_true']) ? $question['is_true']: null;
                            $t_f['and_why'] = isset($question['and_why']) ? $question['and_why']: null;
                            $data['user_answers'] = json_encode($t_f);
                            break;
            
                        case 2: // MCQ
                            $data['user_answers'] = isset($question['MCQ_Choices']) ? json_encode($question['MCQ_Choices']) : null;
                            break;
            
                        case 3: // Match
                            $data['user_answers']=null;
                            if(isset($question['match_a']) && $question['match_b']){
                                foreach($question['match_a'] as $key => $matchA)
                                    $MATCHS[]=[$matchA => $question['match_b'][$key]];
                                
                                $data['user_answers'] = json_encode($MATCHS);
                            }
                            break;
            
                        case 4: // Essay
                            $data['user_answers'] = isset($question['content']) ? json_encode($question['content']) : null; //essay not have special answer
                            break;
                    }
                    // dd($data);
                    $allData->push($data);
                }
                $answer1= userQuizAnswer::where('user_quiz_id',$id)->where('question_id',$question['id'])->first();
                if(isset($answer1))
                    $answer1->update($data);
            }

        if($request->forced){
            $answer2=userQuizAnswer::where('user_quiz_id',$id)->update(['force_submit'=>1,'answered' => 1]);
            $user_quiz->submit_time=Carbon::now()->format('Y-m-d H:i:s');
            $user_quiz->save();
        }

        // dd($user_quiz);
        // $tt=new QuizGrader($user_quiz,$this->gradeInterface);
        // $tt->grade();
        $totalGrade=event(new GradeAttemptEvent($user_quiz,$this->gradeInterface));
        $grade_cat=GradeCategory::where('instance_type','Quiz')->where('instance_id',$user_quiz->quiz_lesson->quiz_id)
                                    ->where('lesson_id',$user_quiz->quiz_lesson->lesson_id)->first();
        //grade item ( attempt_item )user
        $gradeitem=GradeItems::where('index',$user_quiz->attempt_index)->where('grade_category_id',$grade_cat->id)->first();
        UserGrader::where('user_id',Auth::id())->where('item_id',$gradeitem->id)->where('item_type','item')->update(['grade'=>$totalGrade[0]]);

        return HelperController::api_response_format(200, userQuizAnswer::where('user_quiz_id',$id)->get(), __('messages.success.submit_success'));
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
