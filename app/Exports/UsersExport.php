<?php

namespace App\Exports;

use App\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Auth;
use Maatwebsite\Excel\Concerns\WithHeadings;
use DB;

class UsersExport implements FromCollection, WithHeadings
{

    protected $fields = ['id', 'firstname', 'lastname', 'arabicname', 'country', 'birthdate', 'gender',
     'phone', 'address', 'nationality', 'notes','email','suspend', 'religion', 'second language', 'created_at',
     'class_id','level', 'type','role'];


    function __construct($userids) {
        $this->ids = $userids;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        if (Auth::user()->can('site/show/real-password')) {
            $this->fields[] = 'real_password';
        }
        if (request()->user()->can('site/show/username')) {
            $this->fields[] = 'username';
        }
        $users =  User::whereNull('deleted_at')->whereIn('id', $this->ids)->get();

        foreach ($users as $value) {
            $role_id = DB::table('model_has_roles')->where('model_id',$value->id)->pluck('role_id')->first();
            $role_name='';
            if(isset($role_id))
                $role_name = DB::table('roles')->where('id',$role_id)->first()->name;
            $value['role'] = $role_name;
            
            $value->setHidden([])->setVisible($this->fields);
        }
        
        return $users;
    }

    public function headings(): array
    {
        return $this->fields;
    }
}
