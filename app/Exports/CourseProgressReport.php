<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\Exportable;

class CourseProgressReport implements FromCollection , WithHeadings
{
    use Exportable;

    function __construct($report,$exportDetails,$fields) {
        $this->report = $report;
        $this->fields = $fields;
        $this->exportDetails = $exportDetails;
    }
    
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {

        if(!$this->exportDetails){

            $this->report = $this->report->map(function ($report) {
                $report['count'] = $report['count'] ? $report['count']  : '0';
                return $report;
            });
        }

        return $this->report;
    }

    public function headings(): array
    {
        return $this->fields;
    }
}
