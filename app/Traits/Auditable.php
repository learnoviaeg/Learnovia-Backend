<?php

namespace App\Traits;

use App\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

trait Auditable
{
    public static function bootAuditable()
    {
        static::created(function (Model $model) {
            self::audit('created', $model);
        });
    

        static::updated(function (Model $model) {
            self::audit('updated', $model);
        });

        static::deleted(function (Model $model) {
            self::audit('deleted', $model);
        });
    }

    protected static function audit($description, $model)
    {
        $subject_model = substr(get_class($model),strripos(get_class($model),'\\')+1);
        $user_fullname = auth()->user()->fullname;

        $hole_description = 'Item in module ( '. $subject_model .' ) has been ( '. $description .' ) by ( '. $user_fullname. ' )';

        $quiz_related = [
                            'QuizLesson', 'quiz_questions', 
                        ];
       
       // start to ensure order of course and enrolls
        if ($subject_model == 'Course') {
            $created_at = Carbon::now()->addSeconds(1);
        }else{
            $created_at = Carbon::now();
        }
        // end to ensure order of course and enrolls

        // start to exclude refresh tokens of firebase
        if ($subject_model == 'User') {
            $diff_before        = $model->getOriginal();
            $diff_before['profile_fields'] = null;
            $diff_after         = $model->toArray();
            $diff_after['profile_fields'] = null;
            $get_diff_before    = array_diff_assoc($diff_before, $diff_after);

            $tracked = [
                            'firstname', 'email', 'password', 'real_password', 'lastname', 'username', 'suspend', 
                            'class_id','picture', 'level', 'type', 'arabicname', 'country', 'birthdate', 'gender', 
                            'phone', 'address', 'nationality', 'notes', 'language', 'timezone', 'religion', 'second language', 
                            'profile_fields', 'nickname'
                        ];

            $arrayKeys       = array_keys($get_diff_before);
            $intersect       = array_intersect($tracked, $arrayKeys);
            $count_intersect = count($intersect);

                if ( $count_intersect > 0 ) 
                {
                    $notes = $count_intersect == 2 ? 'login' : null;
                    AuditLog::create([
                        'action'       => $description,
                        'subject_id'   => $model->id ?? null,
                        'subject_type' => substr(get_class($model),strripos(get_class($model),'\\')+1),//get_class($model) ?? null,
                        'user_id'      => auth()->id() ?? null,
                        'role_id'      => auth()->id() ? auth()->user()->roles->pluck('id')->toArray() : null,
                        'properties'   => $model ?? null,
                        'host'         => request()->ip() ?? null,
                        'year_id'      => $model->get_year_name($model->getOriginal(), $model),
                        'type_id'      => $model->get_type_name($model->getOriginal(), $model),
                        'level_id'     => $model->get_level_name($model->getOriginal(), $model),
                        'class_id'     => $model->get_class_name($model->getOriginal(), $model),
                        'segment_id'   => $model->get_segment_name($model->getOriginal(), $model), 
                        'course_id'    => $model->get_course_name($model->getOriginal(), $model),
                        'before'       => $model->getOriginal(),
                        'created_at'   => $created_at,
                        'notes'        => $notes,
                        'item_name'    => $model->firstname,
                        'item_id'      => null,
                        'hole_description' => $hole_description,
                    ]);
                }
        }else{  // end to exclude refresh tokens of firebase*/
            $notes = null;
            if ($subject_model == 'page' || $subject_model == 'Announcement') {
                $item_name = $model->title;
                $item_id = null;
            }
            elseif ($subject_model == 'Enroll') {
                $item_name = $model->user->fullname;
                $item_id   = $model->user->id;
            }elseif ($subject_model == 'media') {
                    $item_name = $model->name;
                    $item_id   = null; 
                    if ($model->type == null) {
                        $notes = 'link';
                    }else{
                        $notes = 'media';
                    }
            }elseif ( in_array($subject_model, $quiz_related) ) {
                $item_name = 'quiz';
                $item_id   = $model->quiz_id;
            }else{
                $item_name = $model->name;
                $item_id   = null;   
            }

                AuditLog::create([
                'action'       => $description,
                'subject_id'   => $model->id ?? null,
                'subject_type' => $subject_model,
                'user_id'      => auth()->id() ?? null,
                'role_id'      => auth()->id() ? auth()->user()->roles->pluck('id')->toArray() : null,
                'properties'   => $model ?? null,
                'host'         => request()->ip() ?? null,
                'year_id'      => $model->get_year_name($model->getOriginal(), $model),
                'type_id'      => $model->get_type_name($model->getOriginal(), $model),
                'level_id'     => $model->get_level_name($model->getOriginal(), $model),
                'class_id'     => $model->get_class_name($model->getOriginal(), $model),
                'segment_id'   => $model->get_segment_name($model->getOriginal(), $model), 
                'course_id'    => $model->get_course_name($model->getOriginal(), $model),
                'before'       => $model->getOriginal(),
                'created_at'   => $created_at,
                'notes'        => $notes,
                'item_name'    => $item_name,
                'item_id'      => $item_id,
                'hole_description' => $hole_description,
            ]);
        }
    }
}
