<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Http\Resources\Year_type_resource;
use App\Http\Controllers\HelperController;

class AcademicYear extends Model
{
    protected $fillable = ['name'];
    public function AC_Type()
    {
        return $this->belongsToMany('App\AcademicType', 'academic_year_types', 'academic_year_id','academic_type_id');
    }
    public static function Get_types_with_specific_year($id)
    {
        $cat = Year_type_resource::collection(self::with("AC_type")->get()->where('id',$id));
        return HelperController::api_response_format(200, $cat);
    }

}
