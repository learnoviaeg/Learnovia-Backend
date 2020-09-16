<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AttendnaceExport implements FromCollection, WithHeadings
{
    protected $fields = ['User Name','Full Name','Present','Late','Excuse','Absent'];

    function __construct($attendnace_object) {
        $this->log = $attendnace_object['logs'];
        $this->present = $attendnace_object['Present'];
        $this->absent = $attendnace_object['Absent'];
        $this->late = $attendnace_object['Late'];
        $this->excuse = $attendnace_object['Excuse'];
        $this->total = $attendnace_object['Total_Logs'];
    }
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $logs = $this->log;
        foreach($logs as $att){
            $att['User Name'] = $att['username'];
            $att['Full Name'] = $att['firstname'].' '.$att['lastname'];
            $state= $att['status'];  

            if($state == 'Present'){
                $att['Present'] = '✔';
                $att['Late'] = '-';
                $att['Excuse'] = '-';
                $att['Absent'] = '-';
            }

            if($state == 'Absent'){
                $att['Present'] = '-';
                $att['Late'] = '-';
                $att['Excuse'] = '-';
                $att['Absent'] = '✔';
            }

            if($state == 'Late'){
                $att['Present'] = '-';
                $att['Late'] = '✔';
                $att['Excuse'] = '-';
                $att['Absent'] = '-';
            }

            if($state == 'Excuse'){
                $att['Present'] = '-';
                $att['Late'] = '-';
                $att['Excuse'] = '✔';
                $att['Absent'] = '-';
            }

            if($state == null){
                $att['Present'] = '-';
                $att['Late'] = '-';
                $att['Excuse'] = '-';
                $att['Absent'] = '-';
            }

            $att->setHidden([])->setVisible($this->fields);
        }
        $logs[] = [
            'User Name' => '',
            'Full Name' => '',
            'Present' => $this->present['count'].' / '.$this->total,
            'Late' => $this->late['count'].' / '.$this->total,
            'Excuse' => $this->excuse['count'].' / '.$this->total,
            'Absent' => $this->absent['count'].' / '.$this->total,

        ];
        $logs[] = [
            'User Name' => '',
            'Full Name' => '',
            'Present' => isset($this->present['precentage'])?$this->present['precentage'].' % ': '0 %',
            'Late' => isset($this->late['precentage'])?  $this->late['precentage'].' % ' : '0 %',
            'Excuse' => isset($this->excuse['precentage'])?  $this->excuse['precentage'].' % ' : '0 %',
            'Absent' => isset($this->absent['precentage'])?$this->absent['precentage'].' % ' : '0 %',
        ];

        return $logs;
    }

    public function headings(): array
    {
        return $this->fields;
    }
}
