<?php

namespace App\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\Exportable;

class QuizAttemptReport implements FromCollection, WithHeadings
{
    use Exportable;

    protected $fields = ['id','lesson_id','name','course_name','level_name','classes','start_date','due_date','duration',
                        'period','attempts_number','gradeing_method','students_number','solved_students','not_solved_students',
                        'got_full_mark','got_zero','viewed_without_action','equals‌_‌grading‌_‌pass','more‌_than‌_grading‌_‌pass','less‌_than_‌grading‌_‌pass'];

    function __construct($quizLessons) {
        $this->quizLessons = $quizLessons;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $quizLessons = collect();
        foreach ($this->quizLessons as $quizLesson) {

            //calculate days number between two dates
            $start_date = Carbon::createFromFormat('Y-m-d H:i:s', $quizLesson->start_date);
            $end_date = Carbon::createFromFormat('Y-m-d H:i:s', $quizLesson->due_date);
            $different_days = $start_date->diffInDays($end_date);
            
            $quizLessons->push([
                'id'             => $quizLesson->quiz->id,
                'lesson_id'             => $quizLesson->lesson_id,
                'name'           => $quizLesson->quiz->name,
                'course_name'    => $quizLesson->lesson->course->name,
                'level_name'    => $quizLesson->lesson->course->level->name,
                'classes'        => $quizLesson->lesson->shared_classes->pluck('name'),
                'start_date'     => $quizLesson->start_date,
                'due_date'       => $quizLesson->due_date,
                'duration'       => round($quizLesson->quiz->duration/60,0),
                'period'         => $different_days,
                'attempts_number'    => $quizLesson->max_attemp,
                'gradeing_method'    => count($quizLesson->grading_method_id) > 0 ? $quizLesson->grading_method_id[0] : '-',
                'students_number'    => ' '.$quizLesson->lesson->students_number,
                'solved_students'    => ' '.$quizLesson->solved_students,
                'not_solved_students'    => ' '.($quizLesson->lesson->students_number - $quizLesson->solved_students),
                'got_full_mark'    => ' '.$quizLesson->full_mark,
                'got_zero'    => ' '.$quizLesson->got_zero,
                'viewed_without_action' => ' '.($quizLesson->user_seen_number - $quizLesson->solved_students),
                'equals‌_‌grading‌_‌pass' => ' '.$quizLesson->‌equals‌_to_‌pass_grade,
                'more‌_than‌_grading‌_‌pass' => ' '.$quizLesson->‌more‌_than‌_grade_to_pass,
                'less‌_than_‌grading‌_‌pass' => ' '.$quizLesson->less‌_than_‌grading‌_‌pass,
            ]);
        }

        return $quizLessons;
    }

    public function headings(): array
    {
        return $this->fields;
    }
}
