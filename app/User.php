<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Passport\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;


class User extends Authenticatable
{
    //use Notifiable;
    use Notifiable, HasApiTokens, HasRoles;
    protected $guard_name = 'api';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'firstname', 'email', 'password','real_password','lastname','username',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    private static function getUserCounter($lastid){
        if ($lastid < 10){
            return "000" . $lastid;
        }elseif ($lastid < 100 && $lastid >= 10){
            return "00" . $lastid;
        }elseif ($lastid < 1000 && $lastid >= 100){
            return "0" . $lastid;
        }
    }

    public static function generateUsername(){
        $last_user = User::latest('id')->first();
        if ($last_user)
            return env('PREFIX') . self::getUserCounter($last_user->id);
        return env('PREFIX') . "0001";
    }
}
