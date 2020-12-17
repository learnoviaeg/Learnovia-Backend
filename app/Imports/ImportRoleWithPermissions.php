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
            'exists' => 'Id must exist in roles list',
        ];
        
        $validator = Validator::make($row,[
            'name' => 'nullable|unique:roles,name',
            'id' => 'nullable|exists:roles,id',
        ],$messages)->validate();

        if(isset($row['id']) && isset($row['name']))
            throw new \Exception('Plese, enter only name of new role or id of existing one');

        if(!isset($row['id']) && !isset($row['name']))
            throw new \Exception('Plese, you must enter only name of new role or id of existing one');

        $permission = 'permission';

        if(isset($row['id']))
            $role = Role::find($row['id']);

        if(isset($row['name'])){
            $role = Role::create([
                'name' => $row['name']
            ]);
        }

        $count=1;
        while(isset($row[$permission.$count])) {
            
            $permission_exist = Permission::find($row[$permission.$count]);

            if(!isset($permission_exist))
                continue;

            $role->givePermissionTo($permission_exist);

            $count++;
        }
        
    }
}
