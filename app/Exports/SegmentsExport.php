<?php

namespace App\Exports;

use App\Segment;
use App\AcademicType;
use App\AcademicYear;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SegmentsExport implements FromCollection, WithHeadings
{
     protected $fields = ['id', 'name','current','year','type'];

    /**
    * @return \Illuminate\Support\Collection
    */
    
    function __construct($segmentsIDs) {
        $this->ids = $segmentsIDs;
    }
    public function collection()
    {
        $segments =  Segment::whereNull('deleted_at')->whereIn('id', $this->ids)->get();
        foreach ($segments as $segment) {
            $type = AcademicType::find($segment->academic_type_id);
            $year = AcademicYear::find($segment->academic_year_id);
            // $year = $type !== null && $type->Actypeyear !== null ? AcademicYear::find($type->Actypeyear->academic_year_id) : null;
    
            $segment['year'] = $type->name;
            $segment['type'] = $year->name;

            $segment->setHidden([])->setVisible($this->fields);
        }
        return $segments;
    }

    public function headings(): array
    {
        return $this->fields;
    }
}
