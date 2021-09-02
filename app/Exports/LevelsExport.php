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
        $levels=$this->ids;
        foreach ($levels as $level) {
            $level['id'] = $level->id;
            $level['name'] = $level->name;
            $level['year'] = $level->type->year->name;
            $level['type'] = $level->type->name;
            $level->setHidden([])->setVisible($this->fields);
        }
        return $levels;
    }

    public function headings(): array
    {
        return $this->fields;
    }
}
