<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Carbon\Carbon;

class AttendanceLogsExport implements FromCollection, WithHeadings
{
    protected $fields = ['userName','fullName','sessionName','from','to','status'];

    function __construct($logs)
    {
        $this->logs=$logs;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $toExport=collect();
        foreach($this->logs as $sessionLogs)
        {
            $statusSession['userName']=$sessionLogs->user->username;
            $statusSession['fullName']=$sessionLogs->user->fullname;
            $statusSession['sessionName']=$sessionLogs->session->name;
            $statusSession['from']=Carbon::parse($sessionLogs->session->from)->format('H:i');
            $statusSession['to']=Carbon::parse($sessionLogs->session->to)->format('H:i');
            $statusSession['status']='-';
            if(isset($sessionLogs->status))
                $statusSession['status']=$sessionLogs->status;
            $toExport->push($statusSession);
        }
        return $toExport;
    }

    public function headings(): array
    {
        return $this->fields;
    }
}
