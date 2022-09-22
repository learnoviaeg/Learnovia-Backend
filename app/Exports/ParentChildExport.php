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
        $year_callback = function ($qu)  {
            $qu->where('current', 1);
            };
        $callback = function ($qu) use ( $year_callback) {
            $qu->take(1);
            $qu->whereHas('year' , $year_callback)->with(['classes']); 
            
        };
        $forSetExport = collect();
        foreach (Parents::cursor() as $value){
            $parent = User::select('id','firstname','lastname','username','real_password')->whereId($value['parent_id'])->first();
            $child = User::select('id','firstname','lastname','username','real_password')->with(['enroll' => $callback])->whereId($value['child_id'])->first();
            $value['parent_username']=$parent->username;
            $value['parent_name']=$parent->fullname;
            $value['parent_password']=$parent->real_password;
            $value['child_username']=$child->username;
            $value['child_name']=$child->fullname;
            $value['child_password']=$child->real_password;
            $value['child_class']= $child->enroll[0]->classes->name;

            $value->setHidden(['lastsction'])->setVisible($this->fields);
            $forSetExport->push($value);
        }
        return $forSetExport;
    }

    public function headings(): array
    {   
        return $this->fields;
    }
}
