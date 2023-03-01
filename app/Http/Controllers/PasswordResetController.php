<?php
namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Notifications\PasswordResetRequest;
use App\Notifications\PasswordResetSuccess;
use App\User;
use App\PasswordReset;
class PasswordResetController extends Controller
{
    /**
     * Create token password reset
     *
     *
     * @param  [string] email
     *
     * @return [string] message
     */
    public function create(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
        ]);
        $user = User::where('email', $request->email)->first();
        if (!$user)
            return HelperController::api_response_format(404, null, 'We can\'t find a user with that e-mail address.');
        $passwordReset = PasswordReset::updateOrCreate(
            ['email' => $user->email],
            [
                'email' => $user->email,
                'token' => rand(1000,9999)
            ]
        );
        if ($user && $passwordReset)
            $user->notify(
                new PasswordResetRequest($passwordReset->token)
            );
        return HelperController::api_response_format(200, null, 'We have e-mailed your password reset link!');

    }

    /**
     * Reset password
     *
     *
     * @param  [string] email
     *
     * @param  [string] password
     *
     * @param  [string] password_confirmation
     *
     * @param  [string] token
     *
     * @return [string] message
     *
     * @return [json] user object
     */
    public function reset(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string|min:8|max:191|confirmed',
            'recovery_code' => 'required|string'
        ]);
        $passwordReset = PasswordReset::where([
            ['token', $request->recovery_code],
            ['email', $request->email]
        ])->first();
        if (!$passwordReset)
            return HelperController::api_response_format(404, null, 'This password reset recovery code is invalid.');

        $user = User::where('email', $passwordReset->email)->first();
        if (!$user)
            return HelperController::api_response_format(404, null, 'We can\'t find a user with that e-mail address.');

        $user->password = bcrypt($request->password);
        $user->save();
        $passwordReset->delete();
        $user->notify(new PasswordResetSuccess($passwordReset));
        return HelperController::api_response_format(200, $user);

    }
}
