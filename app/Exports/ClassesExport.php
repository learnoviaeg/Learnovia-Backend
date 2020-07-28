<?php

namespace App\Exports;

use App\Classes;
use App\Level;
use App\AcademicType;
use App\AcademicYear;
use App\AcademicYearType;
use App\YearLevel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ClassesExport implements FromCollection, WithHeadings
{
    protected $fields = ['id', 'name','year','type','level'];

    /**
    * @return \Illuminate\Support\Collection
    */

    
    function __construct($classesIDs) {
        $this->ids = $classesIDs;
    }
    public function collection()
    {
        $classes =  Classes::whereNull('deleted_at')->whereIn('id', $this->ids)->get();
        $year_name='';
        $type_name='';
        $level_name='';
        foreach ($classes as $class) {
            $year_level= YearLevel::find($class->classlevel->pluck('year_level_id')->first());
            
            if(isset($year_level)){
                $level= Level::find($year_level->level_id);
                $level_name= isset($level) ? $level->name : '';
                $year_type = AcademicYearType::find($year_level->academic_year_type_id);
            }

            if(isset($year_type)){
                $year=AcademicYear::find($year_type->academic_year_id);
                $type= AcademicType::find($year_type->academic_type_id);
                $year_name= isset($year) ? $year->name : '';
                $type_name = isset($type) ? $type->name : '';
            }

            $class['year'] = $year_name;
            $class['type'] = $type_name;
            $class['level'] = $level_name;

            $class->setHidden([])->setVisible($this->fields);
        }
        return $classes;
    }

    public function headings(): array
    {
        return $this->fields;
    }
}
