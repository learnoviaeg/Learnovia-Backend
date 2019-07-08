<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\User;
use Validator;

class SpatieController extends Controller
{
    public function index()
    {
        
        // create permissions
        /*
        Permission::create(['name' => 'Add Role']);
        Permission::create(['name' => 'Delete Role']);
        Permission::create(['name' => 'Assign Role to User']);
        Permission::create(['name' => 'Assign Permission To Role']);
        Permission::create(['name' => 'Revoke Role from User']);
        Permission::create(['name' => 'Revoke Permission from role']);
        Permission::create(['name' => 'Add Bulk of Users']);*/
       

        // create roles and assign created permissions

        // this can be done as separate statements
       /* $role = Role::create(['name' => 'Admin']);
        $role->givePermissionTo('Add Role');
        $role->givePermissionTo('Delete Role');
        $role->givePermissionTo('Assign Role to User');
        $role->givePermissionTo('Assign Permission To Role');
        $role->givePermissionTo('Revoke Role from User');
        $role->givePermissionTo('Revoke Permission from role');*/

       // $role = Role::create(['name' => 'Teacher']) ->givePermissionTo(['Add Course', 'delete Course','Update Course','List Course']);
        //$role->givePermissionTo(Permission::all());
        
       // auth()->user()->assignRole('Admin');
       // return User::role('Admin')->get();

    }
    
    
    public function Add_Role($name)
    {
        Role::create(['name' => $name]);
        
        return response()->json(['msg'=>'Role Added!'],200);
    }
    
     public function Delete_Role($id)
    {
        $find= Role::find($id);
        if($find)
        {
            $find->delete();
             return response()->json(['msg'=>'Role Deleted!'],200);
        }
        
             return response()->json(['msg'=>'Error!'],404); 
        
        
    }
    
     public function Assign_Role_to_user(Request $request)
    {
        try{
            $validater=Validator::make($request->all(),[
                'userid'=>'required|integer|exists:users,id',
                'roleid'=>'required|integer|exists:roles,id'
                
            ]);
            if ($validater->fails())
            {
                $errors=$validater->errors();
                return response()->json($errors,400);
            }
            
            $finduser = User::find($request->userid);
            $findrole = Role::find($request->roleid);
            
            $finduser->assignRole($findrole->name);
            
             return response()->json(['msg'=>'Role Assigned Successfully'],200);
            
        }catch (Exception $ex){
            return response()->json(['msg'=>'Please Try again'],400);
        }
    }
    
     public function Assign_Permission_Role(Request $request)
    {
        
         try{
            $validater=Validator::make($request->all(),[
                'permissionid'=>'required|integer|exists:permissions,id',
                'roleid'=>'required|integer|exists:roles,id'
                
            ]);
            if ($validater->fails())
            {
                $errors=$validater->errors();
                return response()->json($errors,400);
            }
            
            $findPer = Permission::find($request->permissionid);
            $findrole = Role::find($request->roleid);
            
             $findrole->givePermissionTo($findPer);
            
            return response()->json(['msg'=>'Permission Assigned to Role Successfully'],200);
             
        }catch (Exception $ex){
            return response()->json(['msg'=>'Please Try again'],400);
        }
        
       
        
    }

     public function Revoke_Role_from_user(Request $request)
    {
      try{
            $validater=Validator::make($request->all(),[
                'userid'=>'required|integer|exists:users,id',
                'roleid'=>'required|integer|exists:roles,id'
                
            ]);
            if ($validater->fails())
            {
                $errors=$validater->errors();
                return response()->json($errors,400);
            }
            
            $finduser = User::find($request->userid);
            $findrole = Role::find($request->roleid);
            
            $finduser->removeRole($findrole->name);
            
             return response()->json(['msg'=>'Role Revoked from user Successfully'],200);
            
        }catch (Exception $ex){
            return response()->json(['msg'=>'Please Try again'],400);
        }  
    }
    
     public function Revoke_Permission_from_Role(Request $request)
    {
          try{
            $validater=Validator::make($request->all(),[
                'permissionid'=>'required|integer|exists:permissions,id',
                'roleid'=>'required|integer|exists:roles,id'
                
            ]);
            if ($validater->fails())
            {
                $errors=$validater->errors();
                return response()->json($errors,400);
            }
            
            $findPer = Permission::find($request->permissionid);
            $findrole = Role::find($request->roleid);
            
             $findrole->revokePermissionTo($findPer->name);
            
            return response()->json(['msg'=>'Permission Revoked from Role Successfully'],200);
             
        }catch (Exception $ex){
            return response()->json(['msg'=>'Please Try again'],400);
        }
    }
    
    public function List_Roles_Permissions ()
    {
        $roles=Role::all();
        $permissions=Permission::all();
        
       return response()->json(
           ['roles'=> $roles,
           'permissions'=> $permissions]
           ,200);
    }
}
