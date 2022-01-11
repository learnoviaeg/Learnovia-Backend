<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\Exportable;

class GradesExport implements FromCollection, WithHeadings
{
    use Exportable;

    function __construct($fields , $usernames , $course_id ) {
        $this->usernames = $usernames;
        $this->course_id = $course_id;
        $this->fields = $fields;
    }
                    
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $forSetExport=collect();
        foreach($this->usernames as $userr)
        {                    
            $forExport['username']= $userr->user->username;
            $forExport['course'] =  $this->course_id;
            $forSetExport->push($forExport);
        }
        return $forSetExport;
        
    }

    public function headings(): array
    {
        return $this->fields;
    }
}
