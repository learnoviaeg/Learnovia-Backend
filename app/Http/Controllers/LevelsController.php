<?php

namespace App\Http\Controllers;

use App\YearLevel;
use Illuminate\Http\Request;

use App\Level;
class LevelsController extends Controller
{
    public function AddLevelWithYear(Request $request)
    {
        if(Level::Validate($request->all()) == true){
            $level = Level::create([
                'name'=> $request->name,
            ]);
            YearLevel::create([
                'academic_year_type_id' => $request->year,
                'level_id' => $level->id
            ]);
            return 'Level Created in Year ' . $request->year;
        }
        return Level::Validate($request->all());
    }

    public function Delete(Request $request){
        $assign = YearLevel::whereLevel_id($request->level)->get();
        if($assign){
            foreach ($assign as $tmp){
                $tmp->delete();
            }
        }
        $level = Level::find($request->level);
        if($level)
            $level->delete();
        return response()->json(['msg' => 'Level Deleted Successfully'],200);

    }


    public function UpdateLevel(Request $request){

    }

    public function GetAllLevelsInYear(Request $request){
        return response()->json(['body'=> Level::whereIn('id' , Level::GetAllLevelsInYear($request->year))->get() ],200);
    }
}
