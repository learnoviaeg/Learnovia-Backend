<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;


class Segment extends Model
{
    use SoftDeletes;

    protected $fillable = ['name','academic_type_id','academic_year_id','start_date','end_date'];

    // public function Segment_class(){
    //     return $this->belongsToMany('App\ClassLevel', 'segment_classes','segment_id','class_level_id');
    // }

    public function academicType()
    {
        return $this->belongsTo('App\AcademicType', 'academic_type_id', 'id','start_date','end_date');
    }

    public function academicYear()
    {
        return $this->belongsTo('App\AcademicYear', 'academic_year_id', 'id');
    }

    // public static function Get_current($type)
    // {
    //     $segment = self::where('academic_type_id', $type)->where("end_date", '>' ,Carbon::now())->where("start_date", '<=' ,Carbon::now())->first();
    //     return $segment;
    // }
    public static function Get_current_by_many_types($types)
    {
        $segment = self::whereIn('academic_type_id', $types)->where("end_date", '>' ,Carbon::now())->where("start_date", '<=' ,Carbon::now())->pluck('id');
        return $segment;
    }

    public static function Get_current_by_one_type($type)
    {
        $segment = self::where('academic_type_id', $type)->where("end_date", '>' ,Carbon::now())->where("start_date", '<=' ,Carbon::now())->pluck('id');
        return $segment;
    }

    protected $hidden = [
        'created_at','updated_at'
    ];
}
