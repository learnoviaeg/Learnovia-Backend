<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class Level extends Model
{
    use Auditable;
    use SoftDeletes;

    protected $fillable = ['name','academic_type_id'];
    public function type()
    {
        return $this->belongsTo('App\AcademicType', 'academic_type_id', 'id');
    }

    // public function yearlevel()
    // {
    //     return $this->hasMany('App\YearLevel', 'level_id', 'id');
    // }

    public static function Validate($data){
        $validator = Validator::make($data, [
            'name' => 'required|string|max:50',
        ]);
        if ($validator->fails()) {
            return $validator->errors();
        }
        return true ;
    }

    public static function GetAllLevelsInYear($id){
        $tmp = YearLevel::whereAcademic_year_type_id($id)->get(['level_id']);
        $ids = [];
        foreach ($tmp as $id){
            $ids[] = $id['level_id'];
        }
        return $ids;
    }
    protected $hidden = [
        'created_at','updated_at','deleted_at'
    ];

    public function timeline()
    {
        return $this->hasMany('App\Timeline','level_id','id');
    }

    public function courses()
    {
        //         $query->whereHas('courses'function($query2){
        //     $query->whereIn($query->courses->pluck('id'))
        // })
        return $this->hasMany('App\Course', 'level_id', 'id');
    }

    // start function get name and value f attribute
    public static function get_year_name($old, $new)
    {
        $academic_type_id   = intval($new['academic_type_id']);
        $academic_year_id[] = AcademicType::where('id', $academic_type_id)->first()->academic_year_id;
        return $academic_year_id;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_type_name($old, $new)
    {
        $academic_type_id = [intval($new['academic_type_id'])];
        return $academic_type_id;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_level_name($old, $new)
    {
        return null;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_class_name($old, $new)
    {
        return null;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_segment_name($old, $new)
    {
        return null;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_course_name($old, $new)
    {
        return null;
    }
    // end function get name and value attribute
}
