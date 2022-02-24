<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Repositories\ChainRepositoryInterface;
use App\Events\UserGradesEditedEvent;
use Illuminate\Http\Request;
use App\GradeCategory;
use App\User;
use App\UserGrader;

class RefreshUserGrades implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $grade_category;
    public $chain;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($chain ,GradeCategory $grade_category)
    {
        $this->grade_category = $grade_category;
        $this->chain = $chain;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $req = new Request ([
                'courses' => [$this->grade_category->course_id],
        ]);
        $enrolled_students = $this->chain->getEnrollsByChain($req)->where('role_id' , 3)->get('user_id')->pluck('user_id');
        foreach($enrolled_students as $user){
            event(new UserGradesEditedEvent(User::find($user) , $this->grade_category));
        }
    }
}
