<?php

namespace App\Exports;

use App\AcademicType;
use App\AcademicYear;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TypesExport implements FromCollection, WithHeadings
{
    protected $fields = ['id','name','segment_no'];

    function __construct($types) {
        $this->types = $types;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        foreach ($this->types as $type) {
            $type['year'] = $type->year ? $type->year->name : '-';
            $type->setHidden([])->setVisible($this->fields);
        }
        return $this->types;
    }

    public function headings(): array
    {
        return $this->fields;
    }

}
