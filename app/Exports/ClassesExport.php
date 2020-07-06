<?php

namespace App\Exports;

use App\Classes;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ClassesExport implements FromCollection, WithHeadings
{
    protected $fields = ['id', 'name'];

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $classes =  Classes::whereNull('deleted_at')->get();
        foreach ($classes as $class) {
            $class->setHidden([])->setVisible($this->fields);
        }
        return $classes;
    }

    public function headings(): array
    {
        return $this->fields;
    }
}
