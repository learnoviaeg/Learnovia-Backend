<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\Exportable;

class teacherwithcourse implements FromCollection,WithHeadings
{

    use Exportable;
    protected $fields = ['teacher','classname','coursename'];

    function __construct($enrolls) {
        $this->enroll = $enrolls;
    }
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $enrolls=collect();
        foreach($this->enroll as $en){
            // dd($en['class']['name']);
            $enrolls->push([
                'teacher' => $en['user'] ? $en['user']['username'] : '-',
                'classname' => $en['classes']? $en['classes']['name'] : '-',
                'coursename' => $en['courses'] ? $en['courses']['short_name'] : '-',
            ]);
        }

        return $enrolls;
    }

    public function headings(): array
    {
        return $this->fields;
    }
}
