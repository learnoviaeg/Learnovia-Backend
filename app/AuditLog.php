<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
// use Spatie\Permission\Models\Role;
use App\Traits\AuditableView;

class AuditLog extends Model
{
    use SoftDeletes;

    public $table = 'audit_logs';

    protected $appends = [
        'description', 'since', 'username'
    ];

    protected $fillable = [
        'action',
        'subject_id',
        'subject_type',
        'user_id',
        'properties',
        'before',
        'host',
        'year_id',
        'type_id',
        'level_id',
        'class_id',
        'segment_id', 
        'course_id',
        'created_at',
        'role_id', 
        'notes',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'properties' => 'collection',
        'before'     => 'collection',
        'year_id'    => 'array',
        'type_id'    => 'array',
        'level_id'   => 'array',
        'class_id'   => 'array',
        'segment_id' => 'array', 
        'course_id'  => 'array',
        'role_id'    => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

   /* public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }*/

    /*public function getDescriptionAttribute()
    {
        $description = 'Item in module ( '. $this->subject_type .' ) has been ( '. $this->action .' ) by ( '. $this->user->firstname. ' )';
        return $description;
    }*/

    /*public function userfullname()
    {
        return $this->user->fullname;
    }*/

    public function item_name()
    {
        // start item name
            $names_array = [
              'QuestionsCategory' => '\Modules\QuestionBank\Entities\QuestionsCategory',
              'assignment'        => '\Modules\Assigments\Entities\assignment',
              'page'              => '\Modules\Page\Entities\page',
              'Questions'         => '\Modules\QuestionBank\Entities\Questions',
              'QuestionsAnswer'   => '\Modules\QuestionBank\Entities\QuestionsAnswer',
              'QuestionsCategory' => '\Modules\QuestionBank\Entities\QuestionsCategory',
              'QuestionsType'     => '\Modules\QuestionBank\Entities\QuestionsType',
              'quiz'              => '\Modules\QuestionBank\Entities\quiz',
              'quiz_questions'    => '\Modules\QuestionBank\Entities\quiz_questions',
              'file'              => '\Modules\UploadFiles\Entities\file',
              'media'             => '\Modules\UploadFiles\Entities\media',
            ];

            if (array_key_exists($this->subject_type, $names_array)) {
              $model = $names_array[$this->subject_type];
            }else{
              $nameSpace = '\\app\\';
              $model     = $nameSpace.$this->subject_type; 
            }
            if ($this->subject_type == 'Enroll') {
              $enroll = $model::withTrashed()->where('id', $this->subject_id)->select('id, user_id')->first();
              $item_name  = $enroll->user->fullname; 
              $item_id    = $enroll->user->id;    
            }elseif($this->subject_type == 'page' || $this->subject_type == 'Announcement'){
                 $item_name   = $model::withTrashed()->where('id', $this->subject_id)->select('title')->first()->title;
                 $item_id = null;
            }else{
                $item_name   = $model::withTrashed()->where('id', $this->subject_id)->select('name')->first()->name;
                $item_id = null;  
            }
            // end item name

            $item_array = array('item_name' => $item_name, 'item_id' => $item_id);
            return $item_array;
    }
}
