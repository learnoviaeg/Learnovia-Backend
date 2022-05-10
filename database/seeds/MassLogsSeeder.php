<?php

namespace database\seeds;

use Illuminate\Database\Seeder;
use App\AuditLog;
use Modules\QuestionBank\Entities\quiz;

class MassLogsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
<<<<<<< HEAD
            $quiz=Quiz::find(16);
  
      for($i=3; $i>=0; $i++)
=======

        for($i=1; $i>=0; $i++)

>>>>>>> 150cf8f5091f465f1e51f71615bda2bb47163f8f
        {
            $quiz=Quiz::find(16);
            AuditLog:: create([
                'action' => 'updated',
                'subject_id' => 16 ,
                'subject_type' => 'quiz',
                'user_id' => 1,
<<<<<<< HEAD
//                'properties' => '{"id":16,"name":"Ahmed Edited 0'.$i.'","is_graded":"0","duration":39660,"created_by":1,"shuffle":"No Shuffle","feedback":1,"course_id":5,"draft":0,"grade_feedback":"After due_date","correct_feedback":"After due_date","allow_edit":true,"restricted":false,"deleted_at":null}',
  //              'before' => '{"id":16,"name":"Ahmed Edied 0'. ($i-1) . '","is_graded":0,"duration":39660,"created_by":1,"shuffle":"No Shuffle","created_at":"2022-05-08 13:22:35","updated_at":"2022-05-08 15:59:29","feedback":1,"course_id":5,"draft":0,"grade_feedback":"After due_date","correct_feedback":"After due_date","allow_edit":1,"restricted":0,"deleted_at":null}',
//                'properties' => '{"id":16,"name":"Ahmed Edited"'. $i.',"is_graded":"0","duration":39660,"created_by":1,"shuffle":"No Shuffle","feedback":1,"course_id":5,"draft":0,"grade_feedback":"After due_date","correct_feedback":"After due_date","allow_edit":true,"restricted":false,"deleted_at":null}',
  //              'before' => '{"id":16,"name":"Ahmed Edited"'. ($i-1).',"is_graded":0,"duration":39660,"created_by":1,"shuffle":"No Shuffle","created_at":"2022-05-08 13:22:35","updated_at":"2022-05-10 12:20:36","feedback":1,"course_id":5,"draft":0,"grade_feedback":"After due_date","correct_feedback":"After due_date","allow_edit":1,"restricted":0,"deleted_at":null}',
                    'properties' => $quiz,
                'before' => $quiz->getOriginal(),
//            $quiz=Quiz::find(16);

            'host' => request()->ip() ?? null,
=======
                'properties' => $quiz,
                'before' => $quiz->getOriginal(),
                'host' => request()->ip() ?? null,
>>>>>>> 150cf8f5091f465f1e51f71615bda2bb47163f8f
                'year_id' => [2],
                'type_id' => [1],
                'level_id' => [1],
                'class_id' => [1],
                'segment_id' => [1], 
                'course_id' => [5],
                'role_id' => [1], 
                'notes' => 'script',
            ]);
        }
    }
}
