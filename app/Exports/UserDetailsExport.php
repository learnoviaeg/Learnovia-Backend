<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\Exportable;
use App\User;

class UserDetailsExport implements FromCollection, WithHeadings
{
    use Exportable;

    protected $fields = ['id','username','firstname','lastname','fullname'];

    function __construct($userids) {
        $this->ids = $userids;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $forSetExport = collect();
        $users = User::select('id', 'username', 'firstname','lastname', 'profile_fields')->whereNull('deleted_at')->whereIn('id', $this->ids); 
        foreach ($users->cursor() as $user){
            $forExport = [];
            $forExport['id']= $user->id;
            $forExport['username']= $user->username;
            $forExport['firstname']= $user->firstname;
            $forExport['lastname']= $user->lastname;
            $forExport['fullname']= $user->fullname;
            $extra_fields = json_decode(json_encode($user->profile_fields), true);
            if($user->profile_fields != null){
                foreach($extra_fields as $key => $field){
                    if (!in_array( $key, $this->fields)) {
                        $this->fields[] = $key;
                    }
                    $forExport[$key] = $field;
                }
            }   
            $forSetExport->push($forExport);
            $user->setHidden([])->setVisible($this->fields);
        }
        return $forSetExport;
    }

    public function headings(): array
    {
        return $this->fields;
    }
}
