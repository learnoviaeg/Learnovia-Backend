<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\Exportable;
use Modules\Assigments\Entities\UserAssigment;

class AssignmentsExport implements FromCollection, WithHeadings
{
    use Exportable;

    protected $fields = ['fullname','grade_status','grade','override','status'];

    function __construct($submissions) {
        $this->submissions=$submissions;
    }
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $subsCollect=collect();
        foreach($this->submissions as $submission)
        {
            $subs['fullname'] = $submission['fullname'];
            $subs['grade status']='Graded';
            $subs['grade']=$submission['grade'];
            if($submission['grade'] == null){
                $subs['grade status']='Not Graded';
                $subs['grade']='-';
            }

            $subs['override']='False';
            if($submission['override'] == 1)
                $subs['override']='True';

            $subs['status']='Submitted';
            if($submission['submit_date'] == null)
                $subs['status']='Not Submitted';

            dd($subs);

            $subsCollect->push($subs);
        }

        return $subsCollect;
    }

    public function headings(): array
    {
        return $this->fields;
    }
}
