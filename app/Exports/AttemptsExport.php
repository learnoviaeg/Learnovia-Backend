<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Modules\QuestionBank\Entities\userQuiz;

class AttemptsExport implements FromCollection, WithHeadings
{
    protected $fields = ['attempt_index','open_time','submit_time','status','username','fullname','taken_duration_min'];

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
            foreach($user['Attempts'] as $attempt)
            {
                $forExport=UserQuiz::find($attempt['id']);
                $forExport['username']= $user['username'];
                $forExport['fullname'] = $user['fullname'];
                $forExport['taken_duration_min'] = $attempt['taken_duration']/60;
                $forSetExport->push($forExport);
            }
            $forExport->setHidden([])->setVisible($this->fields);
        }
        return collect($forSetExport);
    }

    public function headings(): array
    {
        return $this->fields;
    }
}