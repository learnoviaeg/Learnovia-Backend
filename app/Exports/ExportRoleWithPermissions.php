<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ExportRoleWithPermissions implements FromCollection, WithHeadings
{
    protected $fields = ['role_id','role_name'];

    /**
    * @return \Illuminate\Support\Collection
    */
    function __construct($ids)
    {
        $this->ids = $ids;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $roles = collect();
        foreach ($this->ids as $role) {

            $role = Role::find($role);

            if(!isset($role))
                continue;

            $permissions = $role->permissions;

            $obj['id'] = $role->id;
            $obj['name'] = $role->name;

            $i=1;
            foreach($permissions as $perm){

                $this->fields = array_merge( $this->fields, ['permission'.$i] );

                $obj['permission'.$i] = $perm->title;
                
                $i++;
            }

            $roles->push($obj);
            
            $role->setHidden([])->setVisible($this->fields);
        }

        return $roles;
    }

    public function headings(): array
    {
        return $this->fields;
    }
}
