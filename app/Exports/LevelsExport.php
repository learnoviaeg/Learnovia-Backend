<?php

namespace App\Exports;

use App\Level;
use App\AcademicType;
use App\AcademicYear;
use App\AcademicYearType;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class LevelsExport implements FromCollection, WithHeadings
{
    protected $fields = ['id','name','year','type'];

    function __construct($levelsIDs) {
        $this->ids = $levelsIDs;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $levels = Level::whereNull('deleted_at')->whereIn('id', $this->ids)->get();
        $year_name='';
        $type_name='';
        foreach ($levels as $level) {
            $year_type = AcademicYearType::find($level->yearlevel->pluck('academic_year_type_id')->first());

            if(isset($year_type)){
                $year=AcademicYear::find($year_type->academic_year_id);
                $type= AcademicType::find($year_type->academic_type_id);
                $year_name= isset($year) ? $year->name : '';
                $type_name = isset($type) ? $type->name : '';
            }

            $level['year'] = $year_name;
            $level['type'] = $type_name;
            $level->setHidden([])->setVisible($this->fields);
        }
        return $levels;
    }

    public function headings(): array
    {
        return $this->fields;
    }
}
