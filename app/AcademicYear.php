<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AcademicYear extends Model
{
    protected $fillable = ['id','name','current'];
    public function AC_Type()
    {
        return $this->belongsToMany('App\AcademicType', 'academic_year_types', 'academic_year_id','academic_type_id');
    }
    public static function Get_current()
    {
        $current= self::where('current',1)->first();
        return $current;
    }

    protected $hidden = [
        'created_at','updated_at'
    ];

    public function Acyeartype()
    {
        return $this->belongsTo('App\AcademicYearType', 'id', 'academic_year_id');
    }

    public function YearType()
    {
        return $this->hasMany('App\AcademicYearType', 'academic_year_id' , 'id');
    }

    public static function getAllYearLevel($year = null ,$levels = null){
        $result = collect();
        $start = self::Get_current();
        if($year)
            $start = self::find($year);
        $YearLevels = $start->where('id', $start->id)->with(['YearType.yearLevel' => function ($query) use ($levels) {
            if(isset($levels))
                $query->whereIn('level_id', $levels);
        }])->first();
        $types = $YearLevels->YearType;
        foreach($types as $type){
            foreach ($type->yearLevel as $yearLevel) {
                $result->push($yearLevel);
            }
        }
        return $result->pluck('id');
    }
}
