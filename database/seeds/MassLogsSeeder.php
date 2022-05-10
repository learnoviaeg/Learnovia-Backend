<?php

use Illuminate\Database\Seeder;
use App\AuditLog;

class MassLogsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        for($i=3; $i>=0; $i++)
        {
            AuditLog:: create([
                'action' => 'updated',
                'subject_id' => 16 ,
                'subject_type' => 'quiz',
                'user_id' => 1,
                'properties' => '{"id":16,"name":"Ahmed Edited 0'.$i.'","is_graded":"0","duration":39660,"created_by":1,"shuffle":"No Shuffle","feedback":1,"course_id":5,"draft":0,"grade_feedback":"After due_date","correct_feedback":"After due_date","allow_edit":true,"restricted":false,"deleted_at":null}',
                'before' => '{"id":16,"name":"Ahmed Edied 0'. ($i-1) . '","is_graded":0,"duration":39660,"created_by":1,"shuffle":"No Shuffle","created_at":"2022-05-08 13:22:35","updated_at":"2022-05-08 15:59:29","feedback":1,"course_id":5,"draft":0,"grade_feedback":"After due_date","correct_feedback":"After due_date","allow_edit":1,"restricted":0,"deleted_at":null}',
                'host' => request()->ip() ?? null,
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
