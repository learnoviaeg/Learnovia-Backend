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
    protected $fields = ['id', 'name','type','level_id'];

    /**
    * @return \Illuminate\Support\Collection
    */

    
    function __construct($classesIDs) {
        $this->ids = $classesIDs;
    }
    public function collection()
    {
        $classes =  Classes::whereNull('deleted_at')->whereIn('id', $this->ids)->get();
        foreach ($classes as $class) {
            $class['type'] = Level::whereId($class->level_id)->pluck('academic_type_id');

            $class->setHidden([])->setVisible($this->fields);
        }
        return $classes;
    }

    public function headings(): array
    {
        return $this->fields;
    }
}
