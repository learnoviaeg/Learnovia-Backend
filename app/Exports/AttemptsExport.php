<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AttemptsExport implements FromCollection, WithHeadings
{
    protected $fields = ['fullname','username','attempt_index','status','start_date','end_date','number of attempts'];

    function __construct($attempts) {
        $this->attempts=$attempts;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        // $att->setHidden([])->setVisible($this->fields);
        dd($this->attempts);
    }

    public function headings(): array
    {
        return $this->fields;
    }
}