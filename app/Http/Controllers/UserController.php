<?php
/**
 * Created by PhpStorm.
 * User: Huda
 * Date: 6/23/2019
 * Time: 9:51 AM
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use Validator;
use File;

class UserController extends Controller
{
    /*
        @Description:: This Function is for creating new user.
        @Param:: name, email [must be correct and unique], password[must be more than or equal 8].
        @Output:: 'if every thing correct' -> User Created Successfully.
                  'else' -> Error.
    */
    public function create(Request $request)
    {
        $request->validate([
            'name' => 'required|array',
            'name.*' => 'required|string|min:3|max:50',
            'email' => 'required|array',
            'email.*' => 'required|email|unique:users,email',
            'password' => 'required|array',
            'password.*' => 'required|string|min:8|max:191'
        ]);
        $users = collect([]);
        foreach ($request->name as $key => $name) {
            $user = User::create([
                'name' => $name,
                'email' => $request->email[$key],
                'password' => bcrypt($request->password[$key])
            ]);
            $users->push($user);
        }
        return HelperController::api_response_format(201, $users, 'User Created Successfully');

    }

    /*
        @Description:: This Function is for Update user.
        @Param::id, name, email [must be correct and unique], password[must be more than or equal 8].
        @Output:: 'if every thing correct' -> User Updated Successfully.
                  'else' -> Error.
    */

    public function update(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:users,id',
        ]);

        $user = User::find($request->id);

        $request->validate([
            'name' => 'required|string|min:3|max:50',
            'email' => [
                'required',
                Rule::unique('users')->ignore($user->id),
                'email'
            ],
            'password' => 'required|string|min:8|max:191'
        ]);


        $check = $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password)
        ]);

        return HelperController::api_response_format(201, $user, 'User Updated Successfully');

    }

    /*
       @Description:: This Function is for Delete user.
       @Param:: id.
       @Output:: 'if User Exist' -> User Deleted Successfully.
                 'else' -> Error.
   */

    public function delete(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:users,id',
        ]);

        $user = User::find($request->id);
        $user->delete();

        return HelperController::api_response_format(201, null, 'User Deleted Successfully');

    }

    /*
       @Description:: This Function is for List All users.
       @Output:: All users in system.
   */

    public function list()
    {
        $user = User::all(['id', 'name', 'email', 'suspend', 'created_at']);
        return HelperController::api_response_format(201, $user);
    }

    /*
       @Description:: This Function is for Block a user.
       @Param::id.
       @Output:: 'if user found' -> User Blocked Successfully.
                 'else' -> Error.
   */

    public function suspend_user(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:users,id',
        ]);

        $user = User::find($request->id);
        $check = $user->update([
            'suspend' => 1
        ]);
        return HelperController::api_response_format(201, $user, 'User Blocked Successfully');

    }

    /*
       @Description:: This Function is for un Block a user.
       @Param::id.
       @Output:: 'if user found' -> User un Blocked Successfully.
                'else' -> Error.
   */

    public function unsuspend_user(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:users,id',
        ]);
        $user = User::find($request->id);
        $check = $user->update([
            'suspend' => 0
        ]);
        return HelperController::api_response_format(201, $user, 'User Un Blocked Successfully');

    }

}
