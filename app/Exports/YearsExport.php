<?php

namespace App\Exports;

use App\AcademicYear;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class YearsExport implements FromCollection, WithHeadings
{
    protected $fields = ['id','name','current'];

    function __construct($years) {
        $this->years = $years;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        foreach ($this->years as $year) {
            $year->setHidden([])->setVisible($this->fields);
        }
        return $this->years;
    }

    public function headings(): array
    {
        return $this->fields;
    }

}
