<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;


class BigBlueButtonAttendance implements FromCollection, WithHeadings
{
    protected $fields = ['username','full name','Present','Absent'];

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
        $bbb_logs = $this->bbb_log;
        // dd($bbb_logs);
        foreach($bbb_logs as $bbb){
            $bbb['username'] = $bbb['User']['username'];
            $bbb['full name'] = $bbb['User']['firstname'].' '.$bbb['User']['lastname'];
            $state= $bbb['status'];               
            if($state == 'Present'){
                $bbb['Present'] = '✔';
                $bbb['Absent'] = '-';
            }
            if($state == 'Absent'){
                $bbb['Present'] = '-';
                $bbb['Absent'] = '✔';
            }

            $bbb->setHidden([])->setVisible($this->fields);
        }
        $bbb_logs[] = [
            'username' => '',
            'full name' => '',
            'Present' => $this->bbb_present['count'].' / '.$this->bbb_total,
            'Absent' => $this->bbb_absent['count'].' / '.$this->bbb_total,
        ];
        $bbb_logs[] = [
            'username' => '',
            'full name' => '',
            'Present' => $this->bbb_present['precentage'].' % ',
            'Absent' => $this->bbb_absent['precentage'].' % ',
        ];
        return $bbb_logs;
    }

    public function headings(): array
    {
        return $this->fields;
    }
}
