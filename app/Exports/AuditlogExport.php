<?php

namespace App\Exports;

use App\Auditlog;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AuditlogExport implements FromCollection, WithHeadings
{
	protected $data;
	protected $fields = [
					        "id", 
				            "action",
			                "subject_type",
			                "subject_id",
			                "user_id",
			                "created_at",
			                "host",
			            ];

    function __construct($data) {
        $this->data = $data;
    }


    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
       foreach ($this->data as $data_value) {
            $data_value->setVisible($this->fields);
        }
        return $this->data;
    }

    public function headings(): array
    {
        return $this->fields;
    }

}
