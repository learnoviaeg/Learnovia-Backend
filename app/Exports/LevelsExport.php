<?php

namespace App\Exports;

use App\Level;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class LevelsExport implements FromCollection, WithHeadings
{
    protected $fields = ['id','name'];

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $levels = Level::whereNull('deleted_at')->get();
        foreach ($levels as $level) {
            $level->setHidden([])->setVisible($this->fields);
        }
        return $levels;
    }

    public function headings(): array
    {
        return $this->fields;
    }
}
