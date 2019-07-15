<?php

namespace App\Http\Controllers;

use App\Message_Role;
use Illuminate\Http\Request;
use Validator;

class RolePermissionController extends Controller
{
    /**
     * @Description: add Roles to send Message Permission
     *@param: Request  to access From_Role and To_Role
     *@return : if succss -> return MSG -> 'Message Role insertion sucess'
     *          IF NOT ->   return MSG -> 'Message Role insertion Fail'
     *
     */
    public function add_send_Permission_for_role(Request $req)
    {

        $valid = Validator::make($req->all(), [
            'From_Role' => 'required | exists:roles,id',
            'To_Role' => 'required | exists:roles,id'
        ]);
        if ($valid->fails()) {
            return response()->json(['msg' => $valid->errors()], 404);
        }
        $Message_Role = Message_Role::firstOrCreate([
            'From_Role' => $req->From_Role,
            'To_Role' => $req->To_Role,
        ]);
        if ($Message_Role) {
            return response()->json(['msg' => 'Message Role insertion sucess'], 200);
        }

        return response()->json(['msg' => 'Message Role insertion Fail'], 404);

    }
}
