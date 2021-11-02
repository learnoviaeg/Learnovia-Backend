<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Modules\QuestionBank\Entities\userQuiz;
use Maatwebsite\Excel\Concerns\Exportable;

class AttemptsExport implements FromCollection, WithHeadings
{
    use Exportable;
    protected $fields = ['username','fullname','attempt_index','open_time','submit_time','status','taken_duration_min'];

    function __construct($attempts) {
        $this->attempts=$attempts;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $forSetExport=collect();
        foreach($this->attempts as $user)
        {                    
            $forExport['username']= $user['username'];
            $forExport['fullname'] = $user['fullname'];
            $forExport['attempt_index'] = '-';
            $forExport['open_time'] = '-';
            $forExport['submit_time'] = '-';
            $forExport['status'] = 'Not Submitted';
            $forExport['taken_duration_min'] = '-';
            foreach($user['Attempts'] as $attempt)
            {
                $forExport['attempt_index'] = $attempt['details']['attempt_index'];
                $forExport['open_time'] = $attempt['details']['open_time'];
                $forExport['submit_time'] = $attempt['details']['submit_time'];
                $forExport['status'] = $attempt['details']['status'];
                $forExport['taken_duration_min'] = $attempt['taken_duration']/60;
            }
            $forSetExport->push($forExport);
        }
        return $forSetExport;
    }

    public function headings(): array
    {
        return $this->fields;
    }
}