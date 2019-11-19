<?php

namespace Modules\Attendance\Entities;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $fillable = ['name', 'type','grade'];

    public static function GetCarbonDay($day)
    {
        switch ($day) {
            case 'sunday';
                return Carbon::SUNDAY;
                break;
            case 'monday';
                return Carbon::MONDAY;
                break;
            case 'tuesday';
                return Carbon::TUESDAY;
                break;
            case 'wednesday';
                return Carbon::WEDNESDAY;
                break;
            case 'thursday';
                return Carbon::THURSDAY;
                break;
        }

    }
}