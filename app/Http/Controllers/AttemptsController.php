<?php

namespace App\Http\Controllers;

use App\GradeCategory;
use App\GradeItems;
use App\User;
use App\Enroll;
use App\Lesson;
use App\UserGrade;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Auth;
use Carbon\Carbon;
use Modules\QuestionBank\Entities\userQuiz;
use Modules\QuestionBank\Entities\quiz;
use Modules\QuestionBank\Entities\QuizLesson;
use Modules\QuestionBank\Entities\QuizOverride;
use App\Http\Controllers\HelperController;
use Modules\QuestionBank\Entities\Questions;
use Modules\QuestionBank\Entities\userQuizAnswer;
use App\LastAction;

class AttemptsController extends Controller
{
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
        // $quiz['attempts_index']=$attempts->pluck('id');
        // // $quiz['quiz_less']=$attempts->pluck('id');

        // if(isset($request->attempt_index))
        //     $attempts->whereId($request->attempt_index);

        // $user_answer=UserQuizAnswer::where('user_quiz_id',$user_Quiz)->get();
            
        // foreach($quiz->Question as $question){
        //     if($question->question_type_id == 5){
        //         foreach($question->children as $single){
        //             foreach($userAnswers as $userAnswer)
        //                 if($userAnswer->question_id == $question->id)
        //                     $question->User_Answer=$userAnswer;

        //             $single->question_type;

        //             $question->question_category;
        //             unset($single->pivot);
        //         }
        //     }
        //     else
        //         unset($question->children);

        //     $question->question_category;
        //     $question->question_type;
        //     unset($question->pivot);
        // }

        // return HelperController::api_response_format(200, $quiz);
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
        $attempt=UserQuiz::find($id);
        return HelperController::api_response_format(200, $attempt);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id) //it's answer_api because we do make update really ^_^ >>>>> array in params in postman
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
        foreach ($request->Questions as $index => $question) {
            if(isset($question['id'])){
                $currentQuestion = Questions::find($question['id']);
                $question_type_id = $currentQuestion->question_type->id;

                $data = [
                    'user_quiz_id' => $id,
                    'question_id' => $question['id'],
                    'answered' => $question['answered'],
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
                        $match['match_a']=isset($question['match_a']) ? $question['match_a'] : null;
                        $match['match_b']=isset($question['match_b']) ? $question['match_b'] : null;
                        $data['user_answers'] = json_encode($match);
                        break;
        
                    case 4: // Essay
                        $data['user_answers'] = isset($question['content']) ? $question['content'] : null; //essay not have special answer
                        break;
                }
                $allData->push($data);
            }
            $answer1= userQuizAnswer::where('user_quiz_id',$id)->where('question_id',$question['id'])->first();
            if(isset($answer1))
                $answer1->update($data);
        }

        if($request->forced){
            $answer2=userQuizAnswer::where('user_quiz_id',$id)->update(['force_submit'=>'1']);
            
            $user_quiz->submit_time=Carbon::now()->format('Y-m-d H:i:s');
            $user_quiz->save();
        }

        return HelperController::api_response_format(200, $allData, __('messages.success.submit_success'));
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
