<?php

namespace App\Http\Controllers;

use Spatie\Permission\Models\Role;
use App\Parents;
use App\Dictionary;
use App\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Events\MassLogsEvent;
use Carbon\Carbon;
use App\User;
use App\Classes;
use App\Level;
use App\LastAction;
use App\attachment;
use App\Language;
use Spatie\Permission\Models\Permission;
use Laravel\Passport\Passport;
use Modules\Bigbluebutton\Http\Controllers\BigbluebuttonController;
use Illuminate\Support\Facades\App;

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

        // (new BigbluebuttonController)->clear();
        // (new BigbluebuttonController)->create_hook($request);
        $credentials = request(['username', 'password']);
        if (!Auth::attempt($credentials))
            return HelperController::api_response_format(401, [], __('messages.auth.invalid_username_password'));

        //to detect user language
        $defult_lang = Language::where('default', 1)->first();
        $lang = $request->user()->language ? $request->user()->language : ($defult_lang ? $defult_lang->id : null);
        
        if(isset($lang)){
            if($lang == 1)
                App::setLocale('en');

            if($lang == 2)
                App::setLocale('ar');
        }

        if ($request->user()->suspend == 1) {
            return HelperController::api_response_format(200, null, __('messages.auth.blocked'));
        }

        if ($request->remember_me) {
            Passport::personalAccessTokensExpireIn(now()->addWeeks(2));
        } else {
            Passport::personalAccessTokensExpireIn(now()->addHours(24));
        }
        $user = $request->user();
        $tokenResult = $user->createToken('Personal Access Token');
        $token = $tokenResult->token;
        if ($request->remember_me) {
            $token->expires_at = Carbon::now()->addWeeks(2);
        } else {
            $token->expires_at = Carbon::now()->addHours(24);
        }
            
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
        $user->last_login = Carbon::now();
        $user->api_token = $tokenResult->accessToken;
        $user->save();
       LastAction::updateOrCreate(['user_id'=> $request->user()->id ],[
            'user_id' => $request->user()->id 
            ,'name' => 'login'
            ,'method'=>$request->route()->methods[0]
            ,'uri' =>  $request->route()->uri
            ,'resource' =>  $request->route()->action['controller']
            ,'date' => Carbon::now()->format('Y-m-d H:i:s a')
            ]);
        return HelperController::api_response_format(200, [
            'access_token' => $tokenResult->accessToken,
            'token_type' => 'Bearer',
            'permission' => $permissions,
            'expires_at' => Carbon::parse(
                $tokenResult->token->expires_at
            )->toDateTimeString(),
            'language' => Language::find($user->language),
            // 'dictionary' => self::Get_Dictionary(1,$request),
        ], __('messages.auth.login'));
    }

    public function Get_Dictionary($callOrNot = 0,Request $request)
    {
        $request->validate([
            'name' => 'required|exists:languages,name',
        ]);
        $result = array();
        // $user = User::find(Auth::id());
        // $lang = $user->language;
        // if(!isset($user->language))
        //     $lang = Language::where('default', 1)->pluck('id');
        $id=Language::where('name',$request->name)->pluck('id')->first();
        // if(!isset($request->id))
        $keywords = Dictionary::where('language',$id)->get();
        foreach($keywords as $keyword)
            $result[$keyword->key] = $keyword->value;
        if($callOrNot == 1)
            return $result;
        return HelperController::api_response_format(200, $result , 'Here are the keywords...');
    }

    public function changeUserLanguage(Request $request)
    {
        $request->validate([
            'name' => 'required|exists:languages,name',
        ]);  
        $user = User::find(Auth::id());
        $lang = Language::where('name', $request->name)->first();
        $user->language = $lang->id;
        $user->save();
        $dictionary = self::Get_Dictionary(1,$request);
        return HelperController::api_response_format(200, null , 'Language changed successfully...');  
    }

    /**
     * Logout user (Revoke the token)
     *
     * @return [string] message
     */
    public function logout(Request $request)
    {
        $user=$request->user();
        $user->token=null;
        $user->save();
        $request->user()->token()->revoke();
        //for log event
        $logsbefore=Parents::where('parent_id',Auth::id())->get();
        $all = Parents::where('parent_id',Auth::id())->update(['current'=> 0]);
        if($all > 0)
            event(new MassLogsEvent($logsbefore,'updated'));

        return HelperController::api_response_format(200, [], __('messages.auth.logout'));
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
        if(isset($user->class_id))
          $user['class_name']=Classes::find($user->class_id)->name;
        if(isset($user->level))
          $user['level_name']=Level::find($user->level)->name;
        
        if(isset($user->attachment))
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

    public function config()
    {
        // $bool=;
        $path=null;
        $name=null;
        $attachment=attachment::where('type','Logo')->first();
        if($attachment){
            $path=$attachment->path;
            $name =$attachment->name;
        }

        $firebase=[
            'apiKey' => 'AIzaSyDNHapmkBjO39XztyBqjb_0syU0pHSXd8k',
            'authDomain'=> 'learnovia-notifications.firebaseapp.com',
            'databaseURL'=> 'https://learnovia-notifications.firebaseio.com',
            'projectId'=> 'learnovia-notifications',
            'storageBucket'=> 'learnovia-notifications.appspot.com',
            'messagingSenderId'=> '1056677579116',
            'appId'=> '1:1056677579116:web:23adce50898d8016ec8b49',
            'measurementId'=> 'G-BECF0Q93VE'
        ];
        $config=[
            'production'=> env('APP_DEBUG'),
            'apiUrl'=> env('APP_URL'),
            'firebase'=> $firebase,
            'school_logo' => $path,
            'school_name' => $name
        ];

        return $config;
    }
}
