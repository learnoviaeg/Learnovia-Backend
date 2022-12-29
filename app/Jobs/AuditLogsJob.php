<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\User;
use Carbon\Carbon;
use App\AuditLog;
use Illuminate\Database\Eloquent\Model;

class AuditLogsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $user;
    public $description;
    public $model;
    public $ip;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(User $user , $description , $model,$ip)
    {
        $this->user = $user;
        $this->description = $description;
        $this->model = $model;
        $this->ip = $ip;
    }
    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //
        // protected static function audit($description, $model)
        // {
            $subject_model = substr(get_class($this->model),strripos(get_class($this->model),'\\')+1);
            $user_fullname = $this->user->fullname;
    
            $hole_description = 'Item in module ( '. $subject_model .' ) has been ( '. $this->description .' ) by ( '. $user_fullname. ' )';
    
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
                $diff_before        = $this->model->getOriginal();
                $diff_before['profile_fields'] = null;
                $diff_after         = $this->model->toArray();
                $diff_after['profile_fields'] = null;
                unset($diff_after['picture']);
                // unset($diff_before['picture']);
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
                            'action'       => $this->description,
                            'subject_id'   => $this->model->id ?? null,
                            'subject_type' => substr(get_class($this->model),strripos(get_class($this->model),'\\')+1),//get_class($model) ?? null,
                            'user_id'      => $this->user->id ?? null,
                            'role_id'      => $this->user->id ? $this->user->roles->pluck('id')->toArray() : null,
                            'properties'   => $this->model ?? null,
                            'host'         => $this->ip() ,
                            'year_id'      => $this->model->get_year_name($this->model->getOriginal(), $this->model),
                            'type_id'      => $this->model->get_type_name($this->model->getOriginal(), $this->model),
                            'level_id'     => $this->model->get_level_name($this->model->getOriginal(), $this->model),
                            'class_id'     => $this->model->get_class_name($this->model->getOriginal(), $this->model),
                            'segment_id'   => $this->model->get_segment_name($this->model->getOriginal(), $this->model), 
                            'course_id'    => $this->model->get_course_name($this->model->getOriginal(), $this->model),
                            'before'       => $this->model->getOriginal(),
                            'created_at'   => $created_at,
                            'notes'        => $notes,
                            'item_name'    => $this->model->firstname,
                            'item_id'      => null,
                            'hole_description' => $hole_description,
                        ]);
                    }
            }else{  // end to exclude refresh tokens of firebase*/
                $notes = null;
                if ($subject_model == 'page' || $subject_model == 'Announcement') {
                    $item_name = $this->model->title;
                    $item_id = null;
                }
                elseif ($subject_model == 'Enroll') {
                    $item_name = $this->model->user->fullname;
                    $item_id   = $this->model->user->id;
                }elseif ($subject_model == 'media') {
                        $item_name = $this->model->name;
                        $item_id   = null; 
                        if ($this->model->type == null) {
                            $notes = 'link';
                        }else{
                            $notes = 'media';
                        }
                }elseif ( in_array($subject_model, $quiz_related) ) {
                    $item_name = 'quiz';
                    $item_id   = $this->model->quiz_id;
                }else{
                    $item_name = $this->model->name;
                    $item_id   = null;   
                }
    
                    AuditLog::create([
                    'action'       => $this->description,
                    'subject_id'   => $this->model->id ?? null,
                    'subject_type' => $subject_model,
                    'user_id'      => $this->user->id ?? null,
                    'role_id'      => $this->user->id ? $this->user->roles->pluck('id')->toArray() : null,
                    'properties'   => $this->model ?? null,
                    'host'         => request()->ip() ?? null,
                    'year_id'      => $this->model->get_year_name($this->model->getOriginal(), $this->model),
                    'type_id'      => $this->model->get_type_name($this->model->getOriginal(), $this->model),
                    'level_id'     => $this->model->get_level_name($this->model->getOriginal(), $this->model),
                    'class_id'     => $this->model->get_class_name($this->model->getOriginal(), $this->model),
                    'segment_id'   => $this->model->get_segment_name($this->model->getOriginal(), $this->model), 
                    'course_id'    => $this->model->get_course_name($this->model->getOriginal(), $this->model),
                    'before'       => $this->model->getOriginal(),
                    'created_at'   => $created_at,
                    'notes'        => $notes,
                    'item_name'    => $item_name,
                    'item_id'      => $item_id,
                    'hole_description' => $hole_description,
                ]);
            }
        // }
        //
    }
}
