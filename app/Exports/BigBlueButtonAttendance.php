<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\Exportable;

class BigBlueButtonAttendance implements FromCollection, WithHeadings
{
    use Exportable;
    protected $fields = ['user name','full name','attend duration','duration percentage','first login','last logout','enter date','left date','Present','Absent'];

    function __construct($bbb_object) {
        $this->bbb_log = $bbb_object['logs'];
        $this->bbb_present = $bbb_object['Present'];
        $this->bbb_absent = $bbb_object['Absent'];
        $this->bbb_total = $bbb_object['Total_Logs'];
    }
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $bbb_logs=collect();
        foreach($this->bbb_log as $bbb){
            
            $first_login = 'After '.$bbb['first_login']. ' Minute/s';
            if(!isset($bbb['first_login']))
                $first_login = '-';
            
            $last_logout = 'Before '.$bbb['last_logout']. ' Minute/s';
            if(!isset($bbb['last_logout']))
                $last_logout = '-';

            $bbb_logs->push([
                'user name' => $bbb['username'],
                'full name' => $bbb['fullname'],
                'attend duration' => $bbb['attend_duration'] .' Minute/s',
                'duration percentage' => $bbb['duration_percentage'],
                'first login' => $first_login,
                'last logout' => $last_logout,
                'enter date' => count($bbb['log_times']) > 0 ? $bbb['log_times'][0]['entered_date'] : '-',
                'left date' => count($bbb['log_times']) > 0 ? $bbb['log_times'][0]['left_date'] : '-',
                'Present' => $bbb['status'] == 'Present'? '✔' : '-',
                'Absent' => $bbb['status'] == 'Absent'? '✔' : '-'
            ]);

            for($i=1;$i<count($bbb['log_times']);$i++){
                $bbb_logs->push([
                    'user name' => '',
                    'full name' => '',
                    'attend duration' => '',
                    'duration percentage' => '',
                    'first login' => '',
                    'last logout' => '',
                    'enter date' => $bbb['log_times'][$i]['entered_date'],
                    'left date' => $bbb['log_times'][$i]['left_date'],
                    'Present' => '',
                    'Absent' => '',
                ]);
            }
        }
        $bbb_logs->push([
            'user name' => '',
            'full name' => '',
            'attend duration' => '',
            'duration percentage' => '',
            'first login' => '',
            'last logout' => '',
            'enter date' => '',
            'left date' => '',
            'Present' => $this->bbb_present['count'].' / '.$this->bbb_total,
            'Absent' => $this->bbb_absent['count'].' / '.$this->bbb_total,
        ]);
        $bbb_logs->push([
            'user name' => '',
            'full name' => '',
            'attend duration' => '',
            'duration percentage' => '',
            'first login' => '',
            'last logout' => '',
            'enter date' => '',
            'left date' => '',
            'Present' => $this->bbb_present['precentage'].' % ',
            'Absent' => $this->bbb_absent['precentage'].' % ',
        ]);
        return $bbb_logs;
    }

    public function headings(): array
    {
        return $this->fields;
    }
}
