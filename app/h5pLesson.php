<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class h5pLesson extends Model
{
      protected $fillable = ['content_id',
        'lesson_id',
        'visible',
        'publish_date' ,
        'start_date' ,
        'due_date',
        'user_id',
        'seen_number'
    ];
    protected $appends = ['user_seen_number'];

    public function getUserSeenNumberAttribute(){

        $user_seen = 0;
        if($this->seen_number != 0){
            $user_seen = UserSeen::where('type','h5p')->where('item_id',$this->content_id)->where('lesson_id',$this->lesson_id)
            ->with(['user' => function($q){
                $q->can('site/course/student');
            }])->count();
        }
            
        return $user_seen;  
    }

}
