<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $fillable = [
        'name',
        'description',
        'attached_file',
        'from',
        'to',
        'cover',
        'id_number',
        'user_id',
    ];
    public static function getalldays()
    {
        return ['friday', 'saturday' ,'sunday' ,'monday','tuesday','wednesday','thursday'];
    }
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
            case 'saturday';
                return Carbon::SATURDAY;
                break;
            case 'friday';
                return Carbon::FRIDAY;
                break;
        }
    }
    public static function getAllWorkingDays($start, $end)
    {
        $allday = self::getalldays();
        $start=Carbon::parse($start);
        foreach ($allday as $day) {
            $startDate = Carbon::parse($start)->next(self::GetCarbonDay($day));
            $endDate = Carbon::parse($end);

            for ($date = $startDate; $date->lte($endDate); $date->addWeek()) {
                $alldays[] = $date->format('Y-m-d H:i:s');
            }
        }
        array_push($alldays,$start->format('Y-m-d H:i:s'));
        return $alldays;

    }

}
