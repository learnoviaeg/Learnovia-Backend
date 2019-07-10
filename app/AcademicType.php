<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Http\Controllers\HelperController;

class AcademicType extends Model
{
    protected $fillable = ['name' , 'segment_no'];
        public function AC_year()
        {
            return $this->belongsToMany('App\AcademicYear', 'academic_year_types', 'academic_year_id', 'academic_type_id');
        }
    public static function Get_Levels_with_specific_Type($id)
    {
        $cat = self::with("AC_year")->where('id',$id)->first();
        $AC_year_Type= AcademicYearType::where('academic_year_id',"=",$cat->AC_year[0]->id)->get(['id'])->toArray();
        $_year_Level_id= YearLevel::whereIN('academic_year_type_id',$AC_year_Type)->get(['id'])->toArray();
        $_Level= Level::whereIn('id',$_year_Level_id)->get();

        return HelperController::api_response_format(200,$_Level);
    }
}
