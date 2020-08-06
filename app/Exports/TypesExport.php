<?php

namespace App\Exports;

use App\AcademicType;
use App\AcademicYear;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TypesExport implements FromCollection, WithHeadings
{
    protected $fields = ['id','name','segment_no','year'];
    function __construct($types) {
        $this->types = $types;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        foreach ($this->types as $type) {
            $year_id = AcademicType::find($type->id)->yearType->pluck('academic_year_id')->first();
            if(isset($year_id))
                $year_id = AcademicYear::find(AcademicType::find($type->id)->yearType->pluck('academic_year_id')->first())->name;
            $type['year'] = $year_id;
            $type->setHidden([])->setVisible($this->fields);
        }
        return $this->types;
    }

    public function headings(): array
    {
        return $this->fields;
    }

}
