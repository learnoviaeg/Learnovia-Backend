<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ExportRoleWithPermissions implements FromCollection, WithHeadings
{
    protected $fields = ['id','name','permission_id','permission_title'];

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

            $roles->push([
                'id' => $role->id,
                'name' => $role->name,
                'permission_id' => count($permissions) > 0 ? $permissions[0]->id : '',
                'permission_title' => count($permissions) > 0 ? $permissions[0]->title : ''
            ]);

            for($i=1;$i<count($permissions);$i++){
                $roles->push([
                    'id' => '',
                    'name' => '',
                    'permission_id' => count($permissions) > 0 ? $permissions[$i]->id : '',
                    'permission_title' => count($permissions) > 0 ? $permissions[$i]->title : ''
                ]);
            }
            
            $role->setHidden([])->setVisible($this->fields);
        }

        return $roles;
    }

    public function headings(): array
    {
        return $this->fields;
    }
}
