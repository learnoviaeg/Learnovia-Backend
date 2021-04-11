<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Validator;

class ImportRoleWithPermissions implements ToModel , WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        $messages = [
            'unique' => 'Role name must be unique',
            'exists' => 'Id must exist in the lists',
        ];
        
        $validator = Validator::make($row,[
            'name' => 'nullable',
            'id' => 'nullable|exists:roles,id',
            'permission_id' => 'exists:permissions,id'
        ],$messages)->validate();

        if(isset($row['id']) && isset($row['name']))
            throw new \Exception('Plese, enter only name of new role or id of existing one');

        if(!isset($row['id']) && !isset($row['name']))
            throw new \Exception('Plese, you must enter only name of new role or id of existing one');


        if(isset($row['id']))
            $role = Role::find($row['id']);

        if(isset($row['name'])){
            $role = Role::where('name',$row['name'])->first();

            if(!isset($role)){
                $role = Role::create([
                    'name' => $row['name']
                ]);
            }
        }

        if(isset($row['permission_id'])){

            $permission = Permission::find($row['permission_id']);

            $role->givePermissionTo($permission);
        }

    }
}
