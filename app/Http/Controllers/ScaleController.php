<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\scale;

class ScaleController extends Controller
{
    public function AddScale(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'formate' => 'required|array'
        ]);

        $scaleFormates=serialize($request->formate);

        $newScale = scale::firstOrCreate([
            'name' => $request->name,
            'formate' => $scaleFormates
        ]);
        $newScale->formate = unserialize($newScale->formate);

        return HelperController::api_response_format(200,$newScale, 'Scale Created Successfully' );
    }

    public function UpdateScale(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:scales,id',
            'name' => 'nullable',
            'formate' => 'nullable|array'
        ]);

        $check = GradeItems::where('scale_id',$request->id)->first();
        $scale_id=scale::find($request->id);

        if(!isset($check))
        {
            if ($request->filled('name')) {
                $scale_id->name = $request->name;
            }
            if ($request->filled('formate')) {
                $scaleFormates=serialize($request->formate);
                $scale_id->formate = $scaleFormates;
            }
            $scale_id->save();

            $scale_id->formate = unserialize($scale_id->formate);

            return HelperController::api_response_format(200,$scale_id, 'Scale Updated Succefully' );
        }
        else
            return HelperController::api_response_format(200,$scale_id, 'This Scale Used Before ' );
    }

    public function DeleteScale(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:scales,id'
        ]);

        $check = GradeItems::where('scale_id',$request->id)->first();
        $scale_id=scale::find($request->id);

        if(!isset($check))
        {
            $scale_id->delete();
            return HelperController::api_response_format(200,$scale_id, 'Scale Deleted Successfully' );
        }
        else
            return HelperController::api_response_format(200,$scale_id, 'This Scale Used Before ' );
    }

    public function GetScale(Request $request)
    {
        $request->validate([
            'id' => 'nullable|exists:scales,id'
        ]);

        if(isset($request->id))
        {
            $scale_id=scale::find($request->id);
            $scale_id->formate = unserialize($scale_id->formate);
            return HelperController::api_response_format(200,$scale_id );
        }
        $scales=scale::get();
        foreach($scales as $scale)
            $scale->formate = unserialize($scale->formate);

        return HelperController::api_response_format(200,$scale);
    }
}
