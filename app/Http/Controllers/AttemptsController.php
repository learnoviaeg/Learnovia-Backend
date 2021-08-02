<?php

namespace App\Http\Controllers;

use App\GradeCategory;
use App\GradeItems;
use App\User;
use App\Enroll;
use App\Grader\TypeGrader;
use App\Lesson;
use App\UserGrade;
use App\UserGrader;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Grader\gradingMethodsInterface;
use App\Events\RefreshGradeTreeEvent;
use Auth;
use Carbon\Carbon;
use Modules\QuestionBank\Entities\userQuiz;
use Modules\QuestionBank\Entities\quiz;
use App\Grader\QuizGrader;
use Modules\QuestionBank\Entities\QuizLesson;
use Modules\QuestionBank\Entities\QuizOverride;
use App\Http\Controllers\HelperController;
use Modules\QuestionBank\Entities\Questions;
use Modules\QuestionBank\Entities\quiz_questions;
use Modules\QuestionBank\Entities\userQuizAnswer;
use App\Events\QuizAttemptEvent;
use App\Events\GradeAttemptEvent;
use App\LastAction;
use Log;

class AttemptsController extends Controller
{
    // protected $typegrader;

    // public function __construct(TypeGrader $typegrader)
    // {
    //     $this->typegrader = $typegrader;
    // }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // $request->validate([
        //     'quiz_id' => 'required|integer|exists:quizzes,id',
        //     'lesson_id' => 'required|integer|exists:lessons,id',
        //     'attempt_index'=>'integer|exists:user_quizzes,id',
        //     'user_id' => 'integer|exists:users,id',
        // ]);
        // $user_id=($request->user_id) ? $request->user_id : Auth::id();
        // $quiz=Quiz::find($request->quiz_id);
        // $attempts=UserQuiz::where('user_id',$user_id)->where('quiz_lesson_id',$quiz->quizLesson[0]->id);

        // if(isset($request->attempt_index))
        //     $attempts->whereId($request->attempt_index);

        // return HelperController::api_response_format(200, $attempts->with('UserQuizAnswer','user','quiz_lesson')->get());

        $request->validate([
            'quiz_id' => 'required|integer|exists:quizzes,id',
            'lesson_id' => 'required|integer|exists:lessons,id',
            'user_id' => 'integer|exists:users,id',
        ]);

        $final= collect([]);
        $all_users = array();
        $user_attempts = array();
        $quiz=Quiz::find($request->quiz_id);
        $childs=[];
        foreach($quiz->Question as $oneQ)
            if($oneQ->question_type_id == 5)
                $childs=$oneQ->children->pluck('id')->toArray();
        $quetions=$quiz->Question->pluck('id');
        $questions=array_merge($quetions->toArray(),$childs);
        $essay=0;
        $t_f_Ques=0;
        $essayQues = Questions::whereIn('id',$questions)->where('question_type_id',4)->pluck('id');
        $t_f_Quest = Questions::whereIn('id',$questions)->where('question_type_id',1)->pluck('id');
        if(count($essayQues) > 0)
            $essay = 1;

        if(count($t_f_Quest) > 0)
            $t_f_Ques = 1;
        
        $quiz_lesson = QuizLesson::where('quiz_id', $request->quiz_id)->where('lesson_id', $request->lesson_id)->first();
        if(!$quiz_lesson)
            return HelperController::api_response_format(200, null, __('messages.error.not_found'));

        // $quiz_duration_ended=false;
        // if(Carbon::parse($quiz_lesson->due_date)->format('Y-m-d H:i:s') <= Carbon::now()->format('Y-m-d H:i:s'))
        //     $quiz_duration_ended=true;
        
        $users=Enroll::where('course_segment',$quiz_lesson->lesson->course_segment_id)->where('role_id',3)->pluck('user_id')->toArray();

        if($request->filled('user_id')){
            unset($users);
            $users = userQuiz::where('quiz_lesson_id', $quiz_lesson->id)->where('user_id',$request->user_id)->pluck('user_id')->unique();
            if(count ($users) == 0)
                return HelperController::api_response_format(200, __('messages.error.user_not_assign'));
        }
        
        $Submitted_users=0;
        foreach ($users as $user_id){
            $i=0;
            $All_attemp=[];
            $user = User::find($user_id);
            if($user == null){
                unset($user);
                continue;
            }
            if( !$user->can('site/quiz/store_user_quiz'))
                continue;
            
            $attems=userQuiz::where('user_id', $user_id)->where('quiz_lesson_id', $quiz_lesson->id)->orderBy('submit_time', 'desc')->get();

            $countEss_TF=0;
            foreach($attems as $key=>$attem){
                $gradeNotWeight=0;
                $grade_cat=GradeCategory::where('instance_type','Quiz')->where('instance_id',$attem->quiz_lesson->quiz_id)
                                            ->where('lesson_id',$attem->quiz_lesson->lesson_id)->first();
                //grade item ( attempt_item )user
                $gradeitem=GradeItems::where('index',$attem->attempt_index)->where('grade_category_id',$grade_cat->id)->first();
                $grade=UserGrader::where('user_id',$user_id)->where('item_id',$gradeitem->id)->where('item_type','item')->pluck('grade')->first();
                $gradeNotWeight+=$grade;
                // dd($grade);

                //7esab daragat el true_false questions
                $userEssayCheckAnswerTF=UserQuizAnswer::where('user_quiz_id',$attem->id)->where('answered',1)->where('force_submit',1)->whereIn('question_id',$t_f_Quest)->get();
                if(count($userEssayCheckAnswerTF) > 0)
                {
                    foreach($userEssayCheckAnswerTF as $TF){
                        if($TF->correction->and_why == true){
                            if(isset($TF->correction->grade)){
                                $gradeNotWeight+= $TF->correction->grade;
                                if(($TF->correction->and_why_right == 1 && $TF->correction->mark < 1) ||
                                    $TF->correction->and_why_right == 0 && $TF->correction->mark >= 1){
                                    $tes=$TF->correction;
                                    $tes->right=2;
                                    $tes->user_quest_grade=$TF->correction->grade + $TF->correction->mark; // daraget el taleb fel so2al koloh
                                    $TF->update(['correction'=>json_encode($tes)]); //because it doesn't read update
                                }
                            }
                            else{
                                $user_Attemp["grade"]= null;
                                $user_Attemp["feedback"] =null;
                            }
                        }
                    }
                }

                //7esab daragat el essay questions
                $userEssayCheckAnswerE=UserQuizAnswer::where('user_quiz_id',$attem->id)->where('answered',1)->where('force_submit',1)->whereIn('question_id',$essayQues)->get();
                if(count($userEssayCheckAnswerE) > 0)
                {
                    foreach($userEssayCheckAnswerE as $esay){
                        if(isset($esay->correction)){
                            $gradeNotWeight+= $esay->correction->grade;
                        }
                        else{
                            $user_Attemp["grade"]= null;
                            $user_Attemp["feedback"] =null;
                        }
                    }
                }

                $user_Attemp['id']= $attem->id;

                //check if grade is null so, there is and_why and essay not graded
                if(array_key_exists('grade',$user_Attemp)){
                    if(!is_null($user_Attemp['grade']))
                        $user_Attemp['grade']= $gradeNotWeight;
                }
                else
                    $user_Attemp['grade']= $gradeNotWeight;

                // $grade->grade=$user_Attemp['grade'];
                $user_Attemp["open_time"]= $attem->open_time;
                $user_Attemp["submit_time"]= $attem->submit_time;
                $user_Attemp["taken_duration"]= Carbon::parse($attem->open_time)->diffInSeconds(Carbon::parse($attem->submit_time),false);
                $user_Attemp['details']= UserQuiz::whereId($attem->id)->with('UserQuizAnswer.Question')->first();
                foreach($user_Attemp['details']->UserQuizAnswer as $answ)
                    $answ->Question->grade_details=quiz_questions::where('quiz_id',$request->quiz_id)->where('question_id',$answ->question_id)->pluck('grade_details')->first();

                $useranswerSubmitted = userQuizAnswer::where('user_quiz_id',$attem->id)->where('force_submit',null)->count();
                if($useranswerSubmitted < 1){
                    array_push($All_attemp, $user_Attemp);
                    $i++;
                }
            }

            $attemps['id'] = $user->id;
            $attemps['username'] = $user->username;
            $attemps['fullname'] =ucfirst($user->firstname) . ' ' . ucfirst($user->lastname);
            $attemps['picture'] = $user->attachment;
            $attemps['Attempts'] = $All_attemp;
            array_push($user_attempts, $attemps);
            if($i>0)
                $Submitted_users++;
        }

        $all_users['essay']=$essay;
        $all_users['T_F']=$t_f_Ques;
        $all_users['unsubmitted_users'] = count($users) - $Submitted_users ;
        $all_users['submitted_users'] = $Submitted_users ;
        $all_users['notGraded'] = $countEss_TF ;
        $final->put('submittedAndNotSub',$all_users);
        $final->put('users',$user_attempts);
        LastAction::lastActionInCourse($quiz_lesson->lesson->courseSegment->course_id);

        return HelperController::api_response_format(200, $final, __('messages.quiz.students_attempts_list'));
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
            $end_date = Carbon::parse($last_attempt->open_time)->addSeconds($quiz_lesson->quiz->duration);
            $seconds = $end_date->diffInSeconds(Carbon::now());
            if($seconds < 0) 
                $seconds = 0;

            if(Carbon::parse($last_attempt->open_time)->addSeconds($quiz_lesson->quiz->duration)->format('Y-m-d H:i:s') > Carbon::now()->format('Y-m-d H:i:s')
                 && UserQuizAnswer::where('user_quiz_id',$last_attempt->id)->whereNull('force_submit')->count() > 0)
            {
                $last_attempt->left_time=(Carbon::parse($last_attempt->open_time)->addSeconds($quiz_lesson->quiz->duration))->diffInSeconds(Carbon::now());
                foreach($last_attempt->UserQuizAnswer as $answers)
                    $answers->Question;
                return HelperController::api_response_format(200, $last_attempt, __('messages.quiz.continue_quiz'));
            }

            if(($last_attempt->attempt_index) == $quiz_lesson->max_attemp )
            {                
                $job = (new \App\Jobs\CloseQuizAttempt($last_attempt))->delay($seconds);
                dispatch($job);

                return HelperController::api_response_format(400, null, __('messages.error.submit_limit'));
            }
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
        if(!Auth::user()->can('site/show-all-courses'))
            $q=Quiz::whereId($quiz_lesson->quiz->id)->update(['allow_edit' => 0]);

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
        $attempt=UserQuiz::whereId($id)->with('UserQuizAnswer.Question','user','quiz_lesson')->first();
        $due_date=$attempt->quiz_lesson->due_date;
        $grade_feedback=$attempt->quiz_lesson->quiz->grade_feedback;
        $correct_feedback=$attempt->quiz_lesson->quiz->correct_feedback;
        
        foreach($attempt->UserQuizAnswer as $one)
        {
            if(!isset($one->correction))
                continue;
            $con=($one->correction);
            $question_type=Questions::whereId($one->question_id)->pluck('question_type_id')->first();

            //correct feedback
            if($grade_feedback == 'After due_date')
            {
                if(Carbon::parse($due_date) > Carbon::now())
                {
                    $con->mark=null;
                    if($question_type == 2)
                        foreach($con->details as $detail)
                            $detail->mark=null;
    
                    if($question_type == 3)
                        if(property_exists($con,'stu_ans'))
                            foreach($con->stu_ans as $ans)
                                $ans->grade=null;
                }
            }

            if($grade_feedback == 'Never'){
                $con->mark=null;
                if($question_type == 2)
                    foreach($con->details as $detail)
                        $detail->mark=null;

                if($question_type == 3)
                    if(property_exists($con,'stu_ans'))
                        foreach($con->stu_ans as $ans)
                            $ans->grade=null;
            }

            //correct feedback
            if($correct_feedback == 'After due_date')
            {
                if(Carbon::parse($due_date) > Carbon::now())
                {
                    $con->right=null;
                    if($question_type == 2)
                        foreach($con->details as $detail)
                            $detail->right=null;
    
                    if($question_type == 3)
                        if(property_exists($con,'stu_ans'))
                            foreach($con->stu_ans as $ans)
                                $ans->right=null;
                }
            }
            if($correct_feedback == 'Never'){
                $con->right=null;
                if($question_type == 2)
                    foreach($con->details as $detail)
                        $detail->right=null;

                if($question_type == 3)
                    if(property_exists($con,'stu_ans'))
                        foreach($con->stu_ans as $ans)
                            $ans->right=null;
            }
            $one->correction = json_encode($con);
        }

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
                                $MATCHS=[]; //if there is more than one match_question >> clear array
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

    public function get_all_users_quiz_attempts(Request $request)
    {

    }
}
