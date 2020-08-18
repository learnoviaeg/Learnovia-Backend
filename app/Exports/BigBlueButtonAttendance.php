<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;


class BigBlueButtonAttendance implements FromCollection, WithHeadings
{
    protected $fields = ['username','full name','student_status'];

    function __construct($bbb_object) {
        $this->bbb_log = $bbb_object;
    }
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $bbb_logs = $this->bbb_log;
        foreach($bbb_logs as $bbb){
            $bbb['username'] = $bbb['User']['username'];
            $bbb['full name'] = $bbb['User']['firstname'].' '.$bbb['User']['lastname'];
            $bbb['student_status'] = $bbb['status'];
            $bbb->setHidden([])->setVisible($this->fields);
        }
        return $bbb_logs;
    }

    public function headings(): array
    {
        return $this->fields;
    }
}
