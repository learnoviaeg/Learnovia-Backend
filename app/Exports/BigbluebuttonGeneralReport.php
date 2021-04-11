<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\Exportable;

class BigbluebuttonGeneralReport implements FromCollection, WithHeadings
{
    use Exportable;
    protected $fields = ['creator_name','course','class','session_name','students_number','present_students','absent_students','start_date',
                         'actutal_start_date','start_delay','end_date','actual_end_date','end_delay'];

    function __construct($bbb_object) {
        $this->bbb_general = $bbb_object;
    }
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $generals=collect();
        foreach ($this->bbb_general as $general) {
            
            $generals->push([
                'creator_name' => isset($general['creator_name']) ? $general['creator_name'] : '_',
                'course' => isset($general['course']) ? $general['course'] : '_',
                'class' => isset($general['class']) ? $general['class'] : '_',
                'session_name' => $general['session_name'],
                'students_number' => $general['students_number'],
                'present_students' => $general['present_students'].' ',
                'absent_students' => $general['absent_students'].' ',
                'start_date' => $general['start_date'],
                'actutal_start_date' => $general['actutal_start_date'],
                'start_delay' => isset($general['start_delay']) ?  $general['start_delay'].' Minute/s' : '_' ,
                'end_date' => isset($general['end_date']) ? $general['end_date'] : '_' ,
                'actual_end_date' => isset($general['actual_end_date']) ? $general['actual_end_date'] : '_' ,
                'end_delay' => isset($general['end_delay']) ? $general['end_delay'].' Minute/s' : '_' ,
            ]);
        }

        return $generals;

    }

    public function headings(): array
    {
        return $this->fields;
    }
}
