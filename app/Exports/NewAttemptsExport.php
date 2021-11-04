<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Modules\QuestionBank\Entities\userQuiz;
use Maatwebsite\Excel\Concerns\Exportable;

class NewAttemptsExport implements FromCollection, WithHeadings
{
    use Exportable;
    protected $fields = ['Username','Fullname','Status','Quiz_Grade','Number_Of_Attempts','Last_Attempt_Date'];

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
            $forExport['Username']= $user['username'];
            $forExport['Fullname'] = $user['fullname'];
            $forExport['Status'] = $user['status'];
            $forExport['Number_Of_Attempts'] = $user['attempt_index'];
            $forExport['Last_Attempt_Date'] = $user['last_att_date'];
            $forSetExport->push($forExport);
        }
        return $forSetExport;
    }

    public function headings(): array
    {
        return $this->fields;
    }
}