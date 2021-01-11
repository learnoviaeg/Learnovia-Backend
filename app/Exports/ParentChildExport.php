<?php

namespace App\Exports;

use App\Parents;
use App\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ParentChildExport implements FromCollection , WithHeadings
{
    function __construct($fields){
        $this->fields=$fields;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $users =  Parents::whereNotNull('id')->get();
        foreach ($users as $value){
            $value['username_parent']=User::whereId($value['parent_id'])->first()->username;
            $value['username_child']=User::whereId($value['child_id'])->first()->username;
            $value->setHidden(['lastsction'])->setVisible($this->fields);
        }
        return $users;
    }

    public function headings(): array
    {   
        return $this->fields;
    }
}

