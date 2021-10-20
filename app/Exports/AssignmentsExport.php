<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\Exportable;
use Modules\Assigments\Entities\UserAssigment;

class AssignmentsExport implements FromCollection, WithHeadings
{
    use Exportable;

    protected $fields = ['fullname','status','override','grade_status'];

    function __construct($submissions) {
        $this->submissions=$submissions;
    }
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        foreach($this->submissions as $submission)
        {
            // $submission=UserAssigment::where('user_id',$submission['user_id'])->where('assignment_lesson_id',$submission['assignment_lesson_id'])->first();
            $submission['grade_status']='Graded';
            if($submission['grade'] == null)
                $submission['grade_status']='Not Graded';

            $submission['override']=False;
            if($submission['override'] == 1)
                $submission['override']=True;

            if($submission['grade'] == null)
                $submission['grade']='-';

            $submission=(object) $submission;

            $submission->setHidden([])->setVisible($this->fields);
        }

        return $logs;
    }

    public function headings(): array
    {
        return $this->fields;
    }
}
