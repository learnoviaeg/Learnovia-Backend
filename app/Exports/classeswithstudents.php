<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\Exportable;

class classeswithstudents implements FromCollection,WithHeadings
{
    use Exportable;
    protected $fields = ['Level_Name','Class_Name','Student_Username'];

    function __construct($enrolls) {
        $this->enroll = $enrolls;
    }
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $enrolls=collect();
        foreach($this->enroll as $level){
            foreach($level as $lev){
                $enrolls->push([
                    'Level_Name' => $lev[0]['levels'] ? $lev[0]['levels']['name'] : '-',
                    'Class_Name' => $lev[0]['classes']? $lev[0]['classes']['name'] : '-',
                ]);
                
                $students= [];
                for($i=0;$i<count($lev);$i++){
                    if(!in_array($lev[$i]['user']['username'],$students)){
                        $enrolls->push([
                            'Level_Name' =>' ',
                            'Class_Name' => ' ',
                            'Student_Username' => $lev[$i]['user'] ? $lev[$i]['user']['username'] : '-',
                        ]);
                    }
                    $students[]=$lev[$i]['user']['username'];
                }
            }
        }

        return $enrolls;
    }

    public function headings(): array
    {
        return $this->fields;
    }
}
