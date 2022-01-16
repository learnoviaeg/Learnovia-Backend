<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\Exportable;
use App\UserGrader;

class GradesExport implements FromCollection, WithHeadings
{
    use Exportable;

    function __construct($fields , $usernames , $course_id , $cat_ids ) {
        $this->usernames = $usernames;
        $this->course_id = $course_id;
        $this->fields = $fields;
        $this->cat_ids = $cat_ids;
    }
                    
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $forSetExport=collect();
        foreach($this->usernames as $key => $userr)
        {               
            $forExport['fullname']= $userr->user->fullname;
            $forExport['username']= $userr->user->username;
            $forExport['course'] =  $this->course_id;
            for($i=0; $i<count($this->cat_ids); $i++){
                $usergrade = UserGrader::select('grade')->where('user_id' ,$userr->user->id )->where('item_id' , $this->cat_ids[$i])->where('item_type' , 'category')->first();
                $forExport[$this->fields[3+$i]] =  isset($usergrade->grade) ? $usergrade->grade : 0;
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
