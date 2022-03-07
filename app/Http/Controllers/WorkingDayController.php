<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\WorkingDay;

class WorkingDayController extends Controller
{
    public function __construct(ChainRepositoryInterface $chain)
    {
        $this->middleware(['permission:settings/working-days'],   ['only' => ['index','edit']]);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return HelperController::api_response_format(200 , WorkingDay::all() , __('messages.working_day.list'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
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

    public function edit(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:working_days,id'
        ]); 

        WorkingDay::whereIn('id',$request->ids)->update(['status',true]);
        WorkingDay::whereNotIn('id',$request->ids)->update(['status',false]);

        return HelperController::api_response_format(200 , WorkingDay::all() , __('messages.working_day.update'));
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
