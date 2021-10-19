<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Modules\QuestionBank\Entities\userQuiz;

class AttemptsExport implements FromCollection, WithHeadings
{
    protected $fields = ['fullname','username','attempt_index','status','open_time','submit_time','number of attempts'];

    function __construct($attempts) {
        $this->attempts=$attempts;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        foreach($this->attempts as $user)
        {    
            foreach($user['Attempts'] as $attempt)
                {
                    // dd($attempt);
                    $forSetExport=UserQuiz::find($attempt['id']);
                    $forSetExport['username']= $user['username'];
                    $forSetExport['fullname'] = $user['fullname'];
                    // $forSetExport['attempt_index'] = $attempt['details']['attempt_index'];
                    // dd(($forSetExport));
                    // $forSetExport['status'] = $user['fullname'];
                    // $forSetExport['open_time'] = $user['fullname'];
                    // $forSetExport['submit_time'] = $user['fullname'];
                    // $forSetExport['number of attempts'] = $user['fullname'];

                    // $oo=(object)$forSetExport;
                }
                $forSetExport->setHidden(['id'])->setVisible($this->fields);
        }
        return collect($this->attempts);
    }

    public function headings(): array
    {
        return $this->fields;
    }
}