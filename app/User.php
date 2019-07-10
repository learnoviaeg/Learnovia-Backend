<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Passport\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use App\Notifications\Notificationlearnovia;
use Illuminate\Support\Facades\Notification;
use App\Http\Controllers\HelperController;
use Validator;
use Illuminate\Http\Request;

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
        'name', 'email', 'password',
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
    
    public static function notify($data)
    {
        $myRequest = new Request([
            'message' => $data[0],
             'from'=>$data[1],
             'to'=>$data[2],
             'course_id'=>$data[3],
             'type'=>$data[4]
        ]);;

        return user::notifyhelper($myRequest);

    }

    public static function notifyhelper(Request $request)
    {
 
      $validater=Validator::make($request->all(),[
        'message'=>'required',
        'from'=>'required|integer|exists:users,id',
        'to'=>'required|integer|exists:users,id',
        'course_id'=>'required|integer|exists:courses,id',
        'type'=>'required|string'
        ]);
    
    if ($validater->fails())
    {
        $errors=$validater->errors();
        return response()->json($errors,400);
    }
    
      $touserid=$request->to;
      $toUser = User::find($touserid);
      Notification::send($toUser, new Notificationlearnovia($request));
    }
}
