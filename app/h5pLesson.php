<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\UserSeen;
use Illuminate\Support\Facades\DB;
class h5pLesson extends Model
{
      protected $fillable = ['content_id',
        'lesson_id',
        'visible',
        'publish_date' ,
        'start_date' ,
        'due_date',
        'user_id',
        'seen_number',
        'restricted'
    ];
    protected $appends = ['user_seen_number'];

    public function getUserSeenNumberAttribute(){

        $user_seen = 0;
        if($this->seen_number != 0)
            $user_seen = UserSeen::where('type','h5p')->where('item_id',$this->content_id)->where('lesson_id',$this->lesson_id)->count();

        return $user_seen;
    }

    public function user(){
        return $this->belongsTo('App\User');
    }

    public function getNameAttribute(){
        return DB::table('h5p_contents')->whereId($this->content_id)->first()->title;
    }

    public function lesson()
    {
        return $this->belongsTo('App\Lesson', 'lesson_id', 'id');
    }

    public function h5pContent(){
        return $this->belongsTo('Djoudi\LaravelH5p\Eloquents\H5pContent','content_id');
    }

    public function courseItem(){
        return $this->hasOne('App\CourseItem', 'item_id')->where('type', 'h5p_content');
    }
}
