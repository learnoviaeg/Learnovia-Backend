<?php

namespace App\Exports;

use App\AcademicYear;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class YearsExport implements FromCollection, WithHeadings
{
    protected $fields = ['id','name','current'];

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $years = AcademicYear::whereNull('deleted_at')->get();
        foreach ($years as $year) {
            $year->setHidden([])->setVisible($this->fields);
        }
        return $years;
    }

    public function headings(): array
    {
        return $this->fields;
    }

}
