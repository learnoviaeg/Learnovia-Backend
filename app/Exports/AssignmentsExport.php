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
        $subsCollect=collect();
        foreach($this->submissions as $submission)
        {
            $subs['fullname'] = $submission['fullname'];
            $subs['grade_status']='Graded';
            if($submission['grade'] == null)
                $subs['grade_status']='Not Graded';

            $subs['override']='False';
            if($submission['override'] == 1)
                $subs['override']='True';

            $subs['grade']=$submission['grade'];
            if($subs['grade'] == null)
                $subs['grade']='-';

            $subsCollect->push($subs);
        }

        return $subsCollect;
    }

    public function headings(): array
    {
        return $this->fields;
    }
}
