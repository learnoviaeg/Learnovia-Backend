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

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $levels = Level::whereNull('deleted_at')->get();
        $year_name='';
        $type_name='';
        foreach ($levels as $level) {
            $year_type = AcademicYearType::find($level->yearlevel->pluck('academic_year_type_id')->first());

            if(isset($year_type)){
                $year_name= AcademicYear::find($year_type->academic_year_id)->name;
                $type_name= AcademicType::find($year_type->academic_type_id)->name;
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
