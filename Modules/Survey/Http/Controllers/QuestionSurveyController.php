<?php

namespace Modules\Survey\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Survey\Entities\SurveyQuestion;

use Modules\QuestionBank\Http\Controllers\QuestionBankController;

class QuestionSurveyController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index()
    {
        return view('survey::index');
    }

    public function QuestionSurvey(Request $request)
    {
        if($request->filled('template_id'))
        {
            $qustions =SurveyQuestion::where('survey_id',$request->template_id)->pluck('question_id');
            foreach($qustions as $quest)
            {
                $check=SurveyQuestion::firstOrCreate([
                    'question_id' => $quest,
                    'survey_id' => $request['survey_id'],
                ]);
            }
        }
        else
        {
            $quest = new QuestionBankController();
            $questions =$quest->store($request,3);
            foreach($questions as $question)
            {
                $check=SurveyQuestion::firstOrCreate([
                    'question_id' => $question,
                    'survey_id' => $request['survey_id'],
                ]);
            }
        }
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Response
     */
    public function show($id)
    {
        return view('survey::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Response
     */
    public function edit($id)
    {
        return view('survey::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function destroy($id)
    {
        //
    }
}
