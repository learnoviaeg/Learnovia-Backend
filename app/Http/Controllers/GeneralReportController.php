<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\LastAction;

class GeneralReportController extends Controller
{
    public function active_users(Request $request){

        $request->validate([
            'from' => 'date|required_with:to',
            'to' => 'date|required_with:from',
            'report_year' => 'required|integer',
            'report_month' => 'integer|required_with:report_day',
            'report_day' => 'integer',
        ]);

        $users_lastaction = LastAction::whereNull('course_id')->whereYear('date', $request->report_year)->with('user');

        if($request->filled('report_month'))
        $users_lastaction->whereMonth('date',$request->report_month);
        
        if($request->filled('report_day'))
            $users_lastaction->whereDay('date',$request->report_day);

        if($request->filled('from') && $request->filled('to'))
            $users_lastaction->whereBetween('date', [$request->from, $request->to]);
        
        return response()->json(['message' => 'Active users list ', 'body' => $users_lastaction->get()->pluck('user')], 200);
    }
}
