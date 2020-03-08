<?php

namespace App\Exports;

use App\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Auth;
use Maatwebsite\Excel\Concerns\WithHeadings;

class UsersExport implements FromCollection, WithHeadings
{

    protected $fields = ['id', 'firstname', 'lastname', 'arabicname', 'real_password', 'created_at', 'level', 'type', 'class_id'];

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $users =  User::get();
        if (request()->user()->can('show/real-password')) {
            foreach ($users as $value) {
                $value->setHidden([])->setVisible($this->fields);
            }
        }
        return $users;
    }

    public function headings(): array
    {
        return $this->fields;
    }
}
