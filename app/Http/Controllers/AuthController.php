<?php

namespace App\Http\Controllers;

use Spatie\Permission\Models\Role;
use App\Parents;
use App\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\User;
use Spatie\Permission\Models\Permission;

class AuthController extends Controller
{
    /**
     * Create user
     *
     * @param  [string] name
     * @param  [string] email
     * @param  [string] password
     * @param  [string] password_confirmation
     * @return [string] message
     */
    public function signup(Request $request)
    {
        $request->validate([
            'firstname' => 'required|string|',
            'password' => 'required|string',
            'lastname' => 'required|string'
        ]);
        $user = new User([
            'username' => User::generateUsername(),
            'firstname' => $request->firstname,
            'lastname' => $request->firstname,
            'password' => bcrypt($request->password),
            'real_password' => $request->password
        ]);
        $user->save();
        $role = Role::find(8);
        $user->assignRole($role);
        return HelperController::api_response_format(200, $user, 'User Created Successfully');
    }

    /**
     * Login user and create token
     *
     * @param  [string] email
     * @param  [string] password
     * @param  [boolean] remember_me
     * @return [string] access_token
     * @return [string] token_type
     * @return [string] expires_at
     */
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
            'remember_me' => 'boolean'
        ]);
        $credentials = request(['username', 'password']);
        if (!Auth::attempt($credentials))
            return HelperController::api_response_format(401, [], 'Invalid username or password');

        if ($request->user()->suspend == 1) {
            return HelperController::api_response_format(200, null, 'Your Account is Blocked!');
        }

        $user = $request->user();
        $tokenResult = $user->createToken('Personal Access Token');
        $token = $tokenResult->token;
        if ($request->remember_me)
            $token->expires_at = Carbon::now()->addWeeks(1);
        else
            $token->expires_at = Carbon::now()->addWeeks(6);
        $token->save();
        $user = Auth::user();
        $permissions = array();
        $allRole =  $user->roles;
        foreach ($allRole as $role) {
            $pers = $role->getAllPermissions();
            foreach ($pers as $permission) {
                $key =  explode("/", $permission->name)[0];
                $permissions[$key][] = $permission->name;
            }
        }
        $languages = SystemSetting::where('key', 'languages')->first();
        $languages = unserialize($languages->data);
        foreach ($languages as $index => $language) {
            if ($language['default'])
                $result = $language;
        }
        $job=(new \App\Jobs\MessageDelivered(Auth::User()->id));
        dispatch($job);

        return HelperController::api_response_format(200, [
            'access_token' => $tokenResult->accessToken,
            'token_type' => 'Bearer',
            'permission' => $permissions,
            'expires_at' => Carbon::parse(
                $tokenResult->token->expires_at
            )->toDateTimeString(),
            'language' => $result
        ], 'User Login Successfully , Don`t share this token with any one they are hackers.');
    }

    /**
     * Logout user (Revoke the token)
     *
     * @return [string] message
     */
    public function logout(Request $request)
    {
        $request->user()->token()->revoke();
        Parents::where('parent_id',Auth::id())->update(['current'=> 0]);
        return HelperController::api_response_format(200, [], 'Successfully logged out');
    }
 /**
     *
     * @Description :getuserPermession gets all permissions for logged in user.
     * @param : No parameters.
     * @return : return an array of permissions of this user .
     */
    public function getuserPermession()
    {
        $user = Auth::user();
        $permissions = array();
        $allRole =  $user->roles;
        foreach ($allRole as $role) {
            $pers = $role->getAllPermissions();
            foreach ($pers as $permission) {
                $key =  explode("/", $permission->name)[0];
                $permissions[$key][] = $permission->name;
            }
        }
        return HelperController::api_response_format(200, $permissions, 'your permissions is ..');
    }
    /**
     * Get the authenticated User
     *
     * @return [json] user object
     */
    public function user(Request $request)
    {
        $user = $request->user();
        $user->picture = $user->attachment->path;
        return HelperController::api_response_format(200, $user);
    }

    public function userRole(Request $request)
    {
        return HelperController::api_response_format(200, $request->user()->roles);
    }
 /**
     *
     * @Description :getuserPermessionFlags gets all permissions for logged in user.
     * @param : No parameters.
     * @return : return an array of permissions as keys and True/False as a values for this user .
     */
    public function getuserPermessionFlags(Request $request)
    {
        $permessions = Permission::all();
        $result = [];
        foreach ($permessions as $permession) {
            $result[$permession->name] = $request->user()->hasPermissionTo($permession->name);
        }
        return HelperController::api_response_format(200, $result);
    }

    public function site(){
        $array = [];
        $array['allow'] = true;
        $array['site'] = env('APP_NAME' , 'Learnovia');
        return HelperController::api_response_format(200,$array);
    }
}
