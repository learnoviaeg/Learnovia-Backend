<?php

namespace App\Exports;

use App\Segment;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SegmentsExport implements FromCollection, WithHeadings
{
     protected $fields = ['id', 'name','current'];

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $segments =  Segment::whereNull('deleted_at')->get();
        foreach ($segments as $segment) {
            $segment->setHidden([])->setVisible($this->fields);
        }
        return $segments;
    }

    public function headings(): array
    {
        return $this->fields;
    }
}
