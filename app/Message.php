<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Message extends Model
{
    protected $fillable = ['text','From','To','about','seen','file','deleted','delivered'];
    public static $DELETE_FROM_ALL = 1;
    public static $DELETE_FOR_RECEIVER = 2;
    public static $DELETE_FOR_SENDER = 3;
    protected $hidden = [
        'created_at','updated_at'
    ];

    public static function GetMessageDetails($messages , $user_id){
        $users = [];
        foreach($messages as $message){
            if($message->From == $user_id){
                $users[] = $message->To;
                continue;
            }
            $users[] = $message->From;
        }
        $users = User::whereIn('id' , $users)->distinct()->get();
        $users = self::handleMessageView($users , $user_id);
        return $users;
    }

    private static function getLastMessage($auth_user , $other_user){
        $message = Message::where('From' , $auth_user)->Where('To' , $other_user)->orderBy('created_at' , 'desc')->first();
        // dd( attachment::find($message->attachment_id)->extension);
        $attachment=null;
        $type=null;
        if($message->file != null)
        {
            $attachment=attachment::find($message->attachment_id);
            if($attachment != null)
                $type=$attachment->extension;
        }
        if($message == null)
            $message = Message::where('From' , $other_user)->Where('To' , $auth_user)->orderBy('created_at' , 'desc')->first();
        return ['message'=>$message->text , 'file' => $message->file, 'seen'=>$message->seen,'type'=>$type];
    }

    private static function handleMessageView($users , $user_id){
        $temp = [];
        $i = 0 ;
        foreach($users as $user){
            $temp[$i]['picture'] = $user->picture;
            $temp[$i]['id'] = $user->id;
            $temp[$i]['name'] = $user->firstname . ' ' .$user->lastname;
            $temp[$i]['roles'] = $user->getRoleNames();
            $temp[$i]['last'] = self::getLastMessage($user_id , $user->id);
            $i++;
        }
        return $temp;
    }

    public function attachment()
    {
        return $this->belongsTo('App\attachment', 'attachment_id', 'id');
    }
}
