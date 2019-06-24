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
        
        //Permission::create(['name' => 'Add Permission To User']);
        /*
        Permission::create(['name' => 'Delete Role']);
        Permission::create(['name' => 'Assign Role to User']);
        Permission::create(['name' => 'Assign Permission To Role']);
        Permission::create(['name' => 'Revoke Role from User']);
        Permission::create(['name' => 'Revoke Permission from role']);
        Permission::create(['name' => 'Add Bulk of Users']);*/
       

        // create roles and assign created permissions

        // this can be done as separate statements
        /*$role = Role::create(['name' => 'Admin']);
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
    
    
    public function Add_Role(Request $request)
    {
        Role::create(['name' => $request->name]);
        
        return response()->json(['msg'=>'Role Added!'],200);
    }
    
     public function Delete_Role(Request $request)
    {
        $find= Role::find($request->id);
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
    public function Assign_Permission_User(Request $request)
    {
        
         try{
            $validater=Validator::make($request->all(),[
                'permissionid'=>'required|integer|exists:permissions,id',
                'userid'=>'required|integer|exists:users,id'
                
            ]);
            if ($validater->fails())
            {
                $errors=$validater->errors();
                return response()->json($errors,400);
            }
            
            $findPer = Permission::find($request->permissionid);
            $finduser = User::find($request->userid);
            
             $finduser->givePermissionTo($findPer->name);
            
            return response()->json(['msg'=>'Permission Assigned to User Successfully'],200);
             
        }catch (Exception $ex){
            return response()->json(['msg'=>'Please Try again'],400);
        }
        
    }

    public function List_Roles_With_Permission ()
    {
        
         try{
            
            $roles=Role::all();

            foreach($roles as $role){
                $role->permissions;
            }
            
            return response()->json($roles,200);
             
        }catch (Exception $ex){
            return response()->json(['msg'=>'Please Try again'],400);
        }
        
    }

    public function Get_Individual_Role (Request $request)
    {
        
         try{
            $validater=Validator::make($request->all(),[
                'roleid'=>'required|integer|exists:roles,id',                
            ]);
            if ($validater->fails())
            {
                $errors=$validater->errors();
                return response()->json($errors,400);
            }
            
            $findrole = Role::find($request->roleid);
            $findrole->permissions;
            
            return response()->json($findrole,200);
             
        }catch (Exception $ex){
            return response()->json(['msg'=>'Please Try again'],400);
        }
        
    }

    public function Add_Role_With_Permissions (Request $request)
    {
        
         try{
            $validater=Validator::make($request->all(),[
                'name' => 'required|string|min:1|unique:roles,name',
                "permissions"    => "required|array|min:1",
                'permissions.*' => 'required|distinct|exists:permissions,id'            
            ]);
            if ($validater->fails())
            {
                $errors=$validater->errors();
                return response()->json($errors,400);
            }

            $createrole= Role::create(['name' => $request->name]);
            if($createrole)
            {
                foreach($request->permissions as $per)
                {
                    $createrole->givePermissionTo($per);
                }
            
                
                return response()->json($createrole->permissions,200);
            }

            return response()->json(['msg'=>'Please Try again'],400);
            
             
        }catch (Exception $ex){
            return response()->json(['msg'=>'Please Try again'],400);
        }
        
    }
    
    public function Export_Role_with_Permission()
    {
        $roles = Role::all();
        $data = [];
        foreach($roles as $key =>  $role){
            $data[$key]['roleName'] = $role->name;
            if(isset($data[$key]['roleName'])){
                $data[$key]['permission'] = array();
                foreach($role->permissions as $k => $permission){
                    $data[$key]['permission'][$k] = $permission->name;
                }
            }
        }

        $newJsonString = json_encode($data, JSON_PRETTY_PRINT);
        file_put_contents(public_path('json\E.json'), stripslashes($newJsonString));

        return response()->download(public_path('json\E.json'));
    }
    
    // public function Import_Role_with_Permission(Request $request)
    // {
    //     //dd(json_decode(file_get_contents($request->Imported_file)));
    //     try{
    //         $extension = $request->Imported_file->getClientOriginalExtension();
    //         if($extension == 'json' || $extension == 'Json' || $extension == 'JSON'){
    //             $content = json_decode(file_get_contents($request->Imported_file));
    //               //  $data = json_decode($file, true);
    //             return response()->json($content,200);
                
    //         }
    //         return response()->json(['msg'=>'Invalid file type'],400);

    //     }catch (Exception $ex){
    //         return response()->json(['msg'=>'Please Try again'],400);
    //     }    
    
    // } 

    

}
