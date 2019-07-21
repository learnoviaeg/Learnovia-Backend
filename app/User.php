<?php

namespace App;

use Illuminate\Notifications\Notifiable;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Passport\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use App\Notifications\Notificationlearnovia;
use Illuminate\Support\Facades\Notification;

use DB;

class User extends Authenticatable
{
    use HasRoles;

    use Notifiable, HasApiTokens, HasRoles;
    protected $guard_name = 'api';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'firstname', 'email', 'password', 'real_password', 'lastname', 'username',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token', 'real_password', 'created_at', 'updated_at'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    private static function getUserCounter($lastid)
    {
        if ($lastid < 10) {
            return "000" . $lastid;
        } elseif ($lastid < 100 && $lastid >= 10) {
            return "00" . $lastid;
        } elseif ($lastid < 1000 && $lastid >= 100) {
            return "0" . $lastid;
        }
    }

    public static function generateUsername()
    {
        $last_user = User::latest('id')->first();
        if ($last_user)
            return env('PREFIX') . self::getUserCounter($last_user->id);
        return env('PREFIX') . "0001";
    }

    public function roles()
    {
        return $this->belongsToMany('Spatie\Permission\Models\Role', 'model_has_roles', 'model_id', 'role_id');
    }

    public static function FindByName($username)
    {
        return self::where('username', $username)->first();
    }

    public static function notify($request)
    {
        $validater = Validator::make($request, [
            'message' => 'required',
            'from' => 'required|integer|exists:users,id',
            'to' => 'required|integer|exists:users,id',
            'course_id' => 'required|integer|exists:courses,id',
            'type' => 'required|string'
        ]);

        if ($validater->fails()) {
            $errors = $validater->errors();
            return response()->json($errors, 400);
        }
        $touserid = $request['to'];
        $toUser = User::find($touserid);
        Notification::send($toUser, new Notificationlearnovia($request));
        return 1;
    }

    public function childs()
    {
        return $this->belongsToMany('App\User' , 'parents' , 'parent_id' , 'child_id');
    }

    public function parent()
    {

    }
}
