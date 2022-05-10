<?php

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
        for($i=1; $i>=0; $i++)
        {
            $quiz=Quiz::find(16);
            AuditLog:: create([
                'action' => 'updated',
                'subject_id' => 16 ,
                'subject_type' => 'quiz',
                'user_id' => 1,
                'properties' => $quiz,
                'before' => $quiz->getOriginal(),
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
