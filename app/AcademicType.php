<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class AcademicType extends Model
{
    use SoftDeletes, Auditable;

    protected $fillable = ['name' , 'segment_no','academic_year_id'];

    public function year()
    { 
        return $this->hasone('App\AcademicYear','id','academic_year_id');
    }

    protected $hidden = [
        'created_at','updated_at','pivot'
    ];

    // public function Actypeyear() // this is right
    // {
    //     return $this->belongsTo('App\AcademicYearType', 'id', 'academic_type_id');
    // }

    // start function get name and value f attribute
    public static function get_year_name($old, $new)
    {
        $old_count = count($old);
        if ($old_count == 0) {
            $year_id = [intval($new['academic_year_id'])];
        }else{
            if ($old['academic_year_id'] == $new['academic_year_id']) {
                $year_id = [intval($new['academic_year_id'])];
            }else{
                $year_id = [intval($old['academic_year_id']), intval($new['academic_year_id'])];
            }
        }
        return $year_id;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_type_name($old, $new)
    {
        return null;
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
