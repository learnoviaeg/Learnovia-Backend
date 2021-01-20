<?php

namespace App\Exports;

use App\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Auth;
use Maatwebsite\Excel\Concerns\WithHeadings;
use DB;
use App\LastAction;

class UsersExport implements FromCollection, WithHeadings
{

    function __construct($userids,$fields) {
        $this->ids = $userids;
        $this->fields=$fields;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $users =  User::whereNull('deleted_at')->whereIn('id', $this->ids)->get();

        foreach ($users as $value) {
            $role_id = DB::table('model_has_roles')->where('model_id',$value->id)->pluck('role_id')->first();
            $role_name='';
            if(isset($role_id))
                $role_name = DB::table('roles')->where('id',$role_id)->first()->name;
            $value['role'] = $role_name;
            $value['last_action'] = '_';
            $last = LastAction::where('user_id',$value->id)->whereNull('course_id')->first();
            if(isset($last))
                $value['last_action'] = $last->date;
                
            $value->setHidden([])->setVisible($this->fields);
        }
        
        return $users;
    }

    public function headings(): array
    {   
        //  dd($this->fields);
        return $this->fields;
    }
}
