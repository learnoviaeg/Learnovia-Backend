<?php

// namespace
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
        
            $quiz=Quiz::find(16);


        
                for($i=1; $i>=0; $i++){

                    AuditLog:: create([
                        'action' => 'updated',
                        'subject_id' => 16 ,
                        'subject_type' => 'quiz',
                        'user_id' => 1,
                        // 
                        'properties' => $quiz,
                        'before' => $quiz->getOriginal(),
                        'host' => request()->ip() ?? null,
        // chain
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
