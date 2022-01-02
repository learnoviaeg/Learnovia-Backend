<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\Exportable;
use Carbon\Carbon;

class InactiveUsers implements FromCollection, WithHeadings
{
    use Exportable;
    protected $fields = ['fullname','username','since','status'];

    function __construct($report) {
        $this->report = $report;
    }
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $reports=collect();
        foreach ($this->report as $user) {
            
            $reports->push([
                'fullname' => $user['fullname'],
                'username' => $user['username'],
                'since' => Carbon::parse($user['lastaction'])->diffForHumans(),
                'status' => $user['status']
            ]);
        }

        return $reports;

    }

    public function headings(): array
    {
        return $this->fields;
    }

}
