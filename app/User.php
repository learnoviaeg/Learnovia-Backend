<?php

namespace App;
use Carbon\Carbon;
use Illuminate\Notifications\Notifiable;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Passport\HasApiTokens;
use DB;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasRoles;
    use SoftDeletes;
    use Notifiable, HasApiTokens, HasRoles;
    protected $guard_name = 'api';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'firstname', 'email', 'password', 'real_password', 'lastname', 'username','suspend','class_id','picture', 'level',
        'type', 'arabicname', 'country', 'birthdate', 'gender', 'phone', 'address', 'nationality', 'notes', 'language',
        'timezone', 'religion', 'second language', 'profile_fields','token','chat_uid','chat_token','refresh_chat_token','last_login','nickname','api_token'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'remember_token', 'created_at', 'updated_at','chat_uid','refresh_chat_token'
    ];
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    protected $appends = ['fullname','lastaction'];

    private static function getUserCounter($lastid)
    {
        if ($lastid < 10) {
            return "00000" . $lastid;
        } elseif ($lastid < 100 && $lastid >= 10) {
            return "0000" . $lastid;
        } elseif ($lastid < 1000 && $lastid >= 100) {
            return "000" . $lastid;
        } elseif ($lastid < 10000 && $lastid >= 1000) {
            return "00" . $lastid;
        } elseif ($lastid < 100000 && $lastid >= 10000) {
            return "0" . $lastid;
        }
    }

    public static function generateUsername($id=0)
    {
        $last_user = DB::table('users')->latest('id')->first();
        if ($last_user)
        {
            $check = env('PREFIX') . self::getUserCounter($last_user->id+$id);
            $check2 = User::where('username',$check)->get();
            if(count($check2) > 0)
                self::generateUsername($id+1);
            else
                return $check;
        }
        return env('PREFIX') . "0001";
    }
    public static function generatePassword()
    {
        $pass = rand(0, 99999999);
        return $pass;
    }

    public function roles()
    {
        return $this->belongsToMany('Spatie\Permission\Models\Role', 'model_has_roles', 'model_id', 'role_id');
    }

    public static function FindByName($username)
    {
        return self::where('username', $username)->first();
    }

    public static function GetUsersByClass_id($class_id){
        $check = self::where('class_id',$class_id)->pluck('id');
        return $check;
    }

    public function childs()
    {
        return $this->belongsToMany('App\User' , 'parents' , 'parent_id' , 'child_id');
    }

    public function currentChild()
    {
        return $this->hasOne('App\Parents','parent_id','id')->where('current',1);

    }
    public function parents()
    {
        return $this->belongsToMany('App\User' , 'parents' , 'child_id' , 'parent_id');
    }

    public function contacts()
    {
        return $this->belongsToMany('App\User' , 'contacts' , 'Person_id' , 'Friend_id');
    }

    public function coursesegnments()
    {
        return $this->hasMany('App\CourseSegment');
    }
    public function lastactionincourse()
    {
        return $this->hasMany('App\LastAction');
    }
    public function enroll(){
       return $this->hasMany('App\Enroll' , 'user_id');
    }

    public function attachment()
    {
        return $this->hasOne('App\attachment', 'id', 'picture');
    }

    public function userQuiz()
    {
        return $this->hasMany('Modules\QuestionBank\Entities\userQuiz', 'user_id', 'id');
    }

    public function userAssignment()
    {
        return $this->hasMany('Modules\Assigments\Entities\UserAssigment', 'user_id', 'id');
    }

    public function UserGrade()
    {
        return $this->hasMany('App\UserGrade');
    }

    public function userSurvey()
    {
        return $this->belongsToMany('Modules\Survey\Entities\UserSurvey', 'user_id', 'id');
    }

    public function getFullNameAttribute() {
        if($this->nickname)
            return ucfirst($this->firstname) . ' ' . ucfirst($this->lastname).' ( ' . ucfirst($this->nickname) . ' )' ;
        return ucfirst($this->firstname) . ' ' . ucfirst($this->lastname);
    }

    public function getLastActionAttribute() {
       $last_action  = LastAction :: where('user_id',$this->id)->where('course_id',null)->first();
       if (isset($last_action))
            return Carbon::Parse($last_action->date)->format('Y-m-d H:i:s');
        
    }

    public function getStatusAttribute(){
        return;
    }

    public function getProfileFieldsAttribute()
    {
        $content=$this->attributes['profile_fields'];
        if(isset($content))
            return json_decode($content);
        return $content;
    }

    public function notifications()
    {
        return $this->belongsToMany('App\Notification')->with('lesson')->orderByDesc('publish_date')->withPivot('read_at');
    }
    
    public function assignmentOverride()
    { 
        return $this->hasMany('Modules\Assigments\Entities\assignmentOverride','user_id','id');
    }

    public function topics()
    {
        return $this->belongsToMany('App\Topic');
    }
    
    public function quizOverride()
    { 
        return $this->hasMany('Modules\QuestionBank\Entities\QuizOverride','user_id','id');
    }

    public function logs()
    {
        return $this->hasMany('App\Log','user','username');
    }

    public function attendanceLogs()
    { 
        return $this->hasMany('Modules\Attendance\Entities\AttendanceLog','student_id','id');
    }
}
