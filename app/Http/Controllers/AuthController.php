<?php

namespace App\Http\Controllers;

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
        $allpermission= $user->roles->first()->getAllPermissions();
        return HelperController::api_response_format(200, [
            'access_token' => $tokenResult->accessToken,
            'token_type' => 'Bearer',
            'permission' => $allpermission,
            'expires_at' => Carbon::parse(
                $tokenResult->token->expires_at
            )->toDateTimeString() ,

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
        return HelperController::api_response_format(200, [], 'Successfully logged out');
    }

    /**
     * Get the authenticated User
     *
     * @return [json] user object
     */
    public function user(Request $request)
    {
        return HelperController::api_response_format(200, $request->user());
    }

    public function userRole(Request $request)
    {
        return HelperController::api_response_format(200, $request->user()->roles);
    }

    public function getuserPermessionFlags(Request $request)
    {
        $permessions = Permission::all();
        $result = [];
        foreach ($permessions as $permession) {
            $result[$permession->name] = $request->user()->hasPermissionTo($permession->name);
        }
        return HelperController::api_response_format(200, $result);
    }
}
