<?php

namespace App\Exports;

use App\AcademicType;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TypesExport implements FromCollection, WithHeadings
{
    protected $fields = ['id','name','current'];

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $types = AcademicType::all();
        foreach ($types as $type) {
            $type->setHidden([])->setVisible($this->fields);
        }
        return $types;
    }

    public function headings(): array
    {
        return $this->fields;
    }

}
