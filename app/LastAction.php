<?php

namespace App;
use Carbon\Carbon;
use Auth;

use Illuminate\Database\Eloquent\Model;

class LastAction extends Model
{
    protected $fillable = [
    'user_id'
    ,'name'
    ,'method'
    ,'uri'
    ,'resource',
    'course_id'
     ,'date'];

     public static function lastActionInCourse($course_id)
    {

        $last_action_resource = LastAction::where('user_id',Auth::id())->whereNull('course_id')->first();

        $last_action_update = LastAction::where('user_id',Auth::id())->where('course_id',$course_id)->first();
        if(isset($last_action_update)){
            $last_action_update->update([
            'user_id' => Auth::id()
            ,'name' => $last_action_resource->name
            ,'method'=>$last_action_resource->method
            ,'uri' =>  $last_action_resource->uri
            ,'resource' =>  $last_action_resource->resource
            ,'date' => Carbon::now()
            ,'course_id'=>$course_id
            ]);
        }

        $last_action = LastAction::firstOrCreate([
            'user_id' => Auth::id()
            ,'name' => $last_action_resource->name
            ,'method'=>$last_action_resource->method
            ,'uri' =>  $last_action_resource->uri
            ,'resource' =>  $last_action_resource->resource
            ,'date' => Carbon::now()
            ,'course_id'=>$course_id
    ]);
    }
    public function user()
    {
        return $this->belongsTo('App\User','user_id','id');
    }
}
