<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CourseItem extends Model
{
    protected $fillable = ['item_id', 'type'];


    public function courseItemUsers(){
        return $this->hasMany('App\UserCourseItem', 'course_item_id', 'id');
    }

    // public function page(){
    //     return $this->belongsTo('Modules\Page\Entities\Page','item_id')->where('type', 'page');
    // }

    // public function file(){
    //     return $this->belongsTo('Modules\UploadFiles\Entities\File','item_id')->where('type', 'file');
    // }

    // public function media(){
    //     return $this->belongsTo('Modules\UploadFiles\Entities\Media','item_id')->where('type', 'media');
    // }

    // public function link(){
    //     return $this->belongsTo('Modules\UploadFiles\Entities\Media','item_id')->where('type', 'link');
    // }

    public function material(){
        return $this->belongsTo('App\Material','item_id')->where('type', 'material');
    }

    public function assignment(){
        return $this->belongsTo('Modules\Assigments\Entities\Assignment','item_id')->where('type', 'assignment');
    }

    public function quiz(){
        return $this->belongsTo('Modules\QuestionBank\Entities\Quiz','item_id')->where('type', 'quiz');
    }

    // public function h5p(){
    //     return $this->belongsTo('Djoudi\LaravelH5p\Eloquents\H5pContent','item_id')->where('type', 'h5p');
    // }

    public function h5pLesson(){
        return $this->belongsTo('App\h5pLesson','item_id')->where('type', 'h5pLesson');
    }

}
