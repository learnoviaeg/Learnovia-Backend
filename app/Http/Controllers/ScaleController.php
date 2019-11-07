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

        return HelperController::api_response_format(200,$newScale, 'Scale Created Cuccefully' );
    }

}
