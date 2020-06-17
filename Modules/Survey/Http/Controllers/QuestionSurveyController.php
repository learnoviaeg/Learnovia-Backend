<?php

namespace Modules\Survey\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Survey\Entities\SurveyQuestion;

use Modules\QuestionBank\Http\Controllers\QuestionBankController;

class QuestionSurveyController extends Controller
{
    public function QuestionSurvey(Request $request)
    {
        if(isset($request->Quest))
        {
            foreach($request->Quest as $quest)
            {
                $check=SurveyQuestion::firstOrCreate([
                    'question_id' => $quest,
                    'survey_id' => $request['survey_id'],
                ]);
            }
        }
        if(isset($request->Question))
        {
            $quest = new QuestionBankController();
            $questions =$quest->store($request,3);
            foreach($questions as $question)
            {
                if(!isset($question))
                    continue;
                $check=SurveyQuestion::firstOrCreate([
                    'question_id' => $question,
                    'survey_id' => $request['survey_id'],
                ]);
            }
        }
    }
}
