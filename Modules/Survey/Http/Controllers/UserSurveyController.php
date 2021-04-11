<?php

namespace Modules\Survey\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Survey\Entities\UserSurvey;
use Modules\Survey\Entities\Survey;
use Modules\Survey\Entities\UserSurveyAnswers;
use Auth;
use App\User;
use Modules\QuestionBank\Entities\Questions;
use Modules\QuestionBank\Entities\QuestionsAnswer;
use Symfony\Component\Console\Question\Question;
use App\Http\Controllers\HelperController;
use Modules\Survey\Entities\SurveyQuestion;

class UserSurveyController extends Controller
{
    public function submitSurvey(Request $request)
    {
        $Q_IDS = array();
        $request->validate([
         'survey_id' => 'required|integer|exists:user_surveys,survey_id',
         'Questions' => 'required|array',
         'Questions.*.id' => 'integer|exists:questions,id',
        ]);

        $userSurvey = UserSurvey::where('survey_id',$request->survey_id)->where('user_id',Auth::id())->first();
        $questionss = SurveyQuestion::where('survey_id',$request->survey_id)->pluck('question_id');
        foreach ($questionss as $Quest){
            UserSurveyAnswers::firstOrCreate([
            'user_survey_id' => $userSurvey->id,
            'question_id' => $Quest,
            'answered'=>1,
            ]);
        }
        
        $allData = collect([]);
        foreach ($request->Questions as $index => $question) {
            if(isset($question['id'])){
            $currentQuestion = Questions::find($question['id']);
            $question_type_id = $currentQuestion->question_type->id;
            $question_answers = $currentQuestion->question_answer->pluck('id');

            $data = [
                'user_survey_id' => $userSurvey->id,
                'question_id' => $question['id'],
                'answered' => 1
            ];
            array_push($Q_IDS, $question['id']);
            if (isset($question_type_id)) {
                switch ($question_type_id) {
                    case 1: // True_false
                        # code...
                        $request->validate([
                            'Questions.' . $index . '.answer_id' => 'required|integer|exists:questions_answers,id',
                            'Questions.' . $index . '.and_why' => 'nullable|string',
                        ]);

                        if (!$question_answers->contains($question['answer_id'])) {
                            return HelperController::api_response_format(400, $question['answer_id'], 'This answer didn\'t belongs to this question');
                        }

                        $answer = QuestionsAnswer::find($question['answer_id']);
                        $data['answer_id'] = $question['answer_id'];
                        // $data['user_grade'] = ($answer->is_true == 1) ? $currentQuestion->mark : 0;
                        if (isset($question['and_why']))
                            $data['and_why'] = $question['and_why'];
                        break;

                    case 2: // MCQ
                        # code...
                        $request->validate([
                            'Questions.' . $index . '.mcq_answers_array' => 'required|array',
                            'Questions.' . $index . '.mcq_answers_array.*' => 'required|integer|exists:questions_answers,id'
                        ]);

                        $flag = 1;
                        foreach ($question['mcq_answers_array'] as $mcq_answer) {
                            if (!$question_answers->contains($mcq_answer)) {
                                return HelperController::api_response_format(400, null, 'This answer didn\'t belongs to this question');
                            }
                            $answer = QuestionsAnswer::find($mcq_answer);
                            if ($answer->is_true == 0) {
                                $flag = 0;
                            }
                        }
                        // $data['user_grade'] = ($flag == 1) ? $currentQuestion->mark : 0;

                        $data['mcq_answers_array'] = serialize($question['mcq_answers_array']);
                        break;

                    case 3: // Match
                        # code...
                        $request->validate([
                            'Questions.' . $index . '.choices_array' => 'required|array',
                            'Questions.' . $index . '.choices_array.*' => 'required|integer|exists:questions_answers,id',
                        ]);

                        $trueAnswer = $currentQuestion->question_answer->where('is_true', 1)->pluck('id');

                        if (count($question['choices_array']) != count($trueAnswer)) {
                            return HelperController::api_response_format(400, null, 'Please submit all choices');
                        }
                        $true_counter = 0;
                        $false_counter = 0;
                        foreach ($question['choices_array'] as $choice) {
                            if (!$question_answers->contains($choice)) {
                                return HelperController::api_response_format(400, null, 'This answer didn\'t belongs to this question');
                            }
                            $answer = QuestionsAnswer::find($choice);
                            if ($answer->is_true == 0) {
                                $false_counter++;
                            } else {
                                $true_counter++;
                            }
                        }
                        $question_mark = (float)$currentQuestion->mark;
                        $multiple = (float)$true_counter * (float)$question_mark;
                        $grade = (float)$multiple / (float)count($trueAnswer);
                        // $data['user_grade'] = $grade;

                        $data['choices_array'] = serialize($question['choices_array']);
                        break;

                    case 4: // Essay
                        # code...
                        $request->validate([
                            'Questions.' . $index . '.content' => 'required|string',
                        ]);
                        $data['content'] = $question['content'];
                        break;

                    case 5: // Paragraph
                        # code...
                        break;
                }

                $allData->push($data);
            } else {
                return HelperController::api_response_format(400, null, 'No type determine to this question');
            }
        }
        $answer1= UserSurveyAnswers::where('user_survey_id',$userSurvey->id)->where('question_id',$question['id'])->first();
        // return 
        if(isset($answer1))
            $answer1->update($data);
        }

        $answer2=UserSurveyAnswers::where('user_survey_id',$userSurvey->id)->whereNotIn('question_id',$Q_IDS)->get();

        foreach($answer2 as $ans)
            $ans->update(['answered'=>'1']);

        return HelperController::api_response_format(200, $allData, 'Survey Answer Registered Successfully');
    }

    public function get_my_surveys(Request $request)
    {
        $request->validate([
            'survey_id' => 'integer|exists:surveys,id',
            ]);

        $final = collect([]);
        $created_surveys = array();
       $UserSurveys = UserSurvey::where('user_id',Auth::id())->pluck('id');
       $check = UserSurveyAnswers::whereIn('user_survey_id',$UserSurveys)->where('answered',1)->pluck('user_survey_id');

        $UserSurveys = UserSurvey::where('user_id',Auth::id())->whereNotIn('id', $check)->pluck('survey_id');
        $sur = Survey::whereIn('id',$UserSurveys)->with(['Question.question_type','Question.question_category','Question.question_answer'])->get();

        $surveys = Survey::where('created_by',Auth::id())->with(['Question.question_type','Question.question_category','Question.question_answer'])->get();
        if(count($surveys) > 0)
            $created_surveys = $surveys;
        if($request->filled('survey_id'))
            $final ->push (Survey::where('id',$request->survey_id)->with(['Question.question_type','Question.question_category','Question.question_answer'])->first());
        else{
            $final ->put ('created_',$created_surveys);
            $final ->put ('assigned',$sur);
        }
        return HelperController::api_response_format(200, $final, 'Surveys are ....');
    }

    public function Review_all_Submissions_of_survey(Request $request)
    {
        $request->validate([
            'survey_id' => 'required|integer|exists:surveys,id',
            ]);
        $allSurvs=UserSurvey::where('survey_id',$request->survey_id)->with('UserSurveyAnswer')->get();
        return HelperController::api_response_format(200, $allSurvs, 'User_Surveys with them surveysAnswers');
    }
}
    
