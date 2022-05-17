<?php

namespace Modules\QuestionBank\Observers;

use App\Repositories\RepportsRepositoryInterface;
use Modules\QuestionBank\Entities\QuizLesson;
use Modules\QuestionBank\Entities\Quiz;
use App\Events\MassLogsEvent;
use App\Lesson;
use App\Timeline;
use App\GradeCategory;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Log;
use App\Enroll;
use App\UserGrader;
use App\User;
use App\LessonComponent;
use App\Notification;
use App\UserSeen;
use Illuminate\Http\Request;

class QuizLessonObserver
{
    protected $report;

    public function __construct(RepportsRepositoryInterface $report)
    {
        $this->report = $report;
    }
    
    /**
     * Handle the quiz lesson "created" event.
     *
     * @param  \App\QuizLesson  $quizLesson
     * @return void
     */
    public function created(QuizLesson $quizLesson)
    {
        $quiz = Quiz::where('id',$quizLesson->quiz_id)->first();
        $lesson = Lesson::where('id',$quizLesson->lesson_id)->first();
       
        $this->report->calculate_course_progress($lesson->course_id);

        $grade_category=GradeCategory::find($quizLesson->grade_category_id);
        //creating grade category for quiz
        $categoryOfQuiz = GradeCategory::create([
            'course_id' => $lesson->course_id,
            'parent' => $grade_category->id,
            'name' => $quiz->name,
            'hidden' => $quizLesson->visible,
            'calculation_type' => json_encode($quizLesson->grading_method_id),
            'instance_type' => 'Quiz',
            'instance_id' => $quiz->id,
            'lesson_id' => $lesson->id,
        ]);

        ///add user grader to each enrolled student in course segment of this grade category
        $enrolled_students = Enroll::where('role_id' , 3)->whereIn('group',$lesson->shared_classes->pluck('id'))
                                    ->where('course',$lesson->course_id)->pluck('user_id');
        if(count($enrolled_students) > 0){
            foreach($enrolled_students as $student){
                UserGrader::firstOrCreate([
                    'user_id'   => $student,
                    'item_type' => 'Category',
                    'item_id'   => $categoryOfQuiz->id
                ],[
                    'grade'     => null
                ]);
            };  
        }
    
        //update quiz lesson with the id of grade categoey created for quiz
        $quizLesson->grade_category_id = $categoryOfQuiz->id;
        $quizLesson->save();

        LessonComponent::firstOrCreate([
            'lesson_id' => $lesson->id,
            'comp_id'   => $quiz->id,
            'module'    => 'QuestionBank',
            'model'     => 'quiz',
            'index'     => LessonComponent::getNextIndex($lesson->id)
        ]);
    }

    /**
     * Handle the quiz lesson "updated" event.
     *
     * @param  \App\QuizLesson  $quizLesson
     * @return void
     */
    public function updated(QuizLesson $quizLesson)
    {
        $lesson=Lesson::find($quizLesson->lesson_id);
        $quiz = Quiz::where('id',$quizLesson->quiz_id)->first();

        event(new MassLogsEvent([$quizLesson],'updated'));

        if($quizLesson->isDirty('lesson_id')){
            
            $lesson = Lesson::find($quizLesson->lesson_id);
            $course_id = $lesson->course_id;
            $old_class_id=null;
            $class_id = $lesson->shared_classes->pluck('id');

            $old_lesson = Lesson::find($quizLesson->getOriginal('lesson_id'));
            if($old_lesson)
                $old_class_id = $old_lesson->shared_classes->pluck('id');
            
            if($old_class_id != $class_id)
                UserSeen::where('lesson_id',$quizLesson->getOriginal('lesson_id'))->where('item_id',$quizLesson->quiz_id)->where('type','quiz')->delete();

            if($old_class_id == $class_id){
                UserSeen::where('lesson_id',$quizLesson->getOriginal('lesson_id'))->where('item_id',$quizLesson->quiz_id)->where('type','quiz')->update([
                    'lesson_id' => $quizLesson->lesson_id
                ]);
            }

            $LessonComponent = LessonComponent::where('comp_id',$quizLesson->quiz_id)->where('lesson_id',$quizLesson->getOriginal('lesson_id'))->where('model' , 'quiz')->first();
            if(isset($LessonComponent)){
                $current_lesson_component = LessonComponent::select('index')->where('lesson_id',$quizLesson->getOriginal('lesson_id'))->where('comp_id',$quizLesson->quiz_id)
                ->where('model' , 'quiz')->first();
                LessonComponent::where('lesson_id',$quizLesson->getOriginal('lesson_id'))
                ->where('index' ,'>=',$current_lesson_component->index )->decrement('index');
                $LessonComponent->update([
                    'lesson_id' => $quizLesson->lesson_id,
                    'comp_id' => $quiz->id,
                    'module' => 'QuestionBank',
                    'model' => 'quiz',
                    'visible' => $quizLesson->visible,
                    'index' => LessonComponent::getNextIndex($quizLesson->lesson_id)
                ]);
            }


            $this->report->calculate_course_progress($course_id);
        }
    }

    /**
     * Handle the quiz lesson "deleted" event.
     *
     * @param  \App\QuizLesson  $quizLesson
     * @return void
     */
    public function deleted(QuizLesson $quizLesson)
    {
        //for log event
        $logsbefore=Timeline::where('lesson_id',$quizLesson->lesson_id)->where('item_id',$quizLesson->quiz_id)->where('type','quiz')->get();
        $all = Timeline::where('lesson_id',$quizLesson->lesson_id)->where('item_id',$quizLesson->quiz_id)->where('type','quiz')->delete();
        if($all > 0)
            event(new MassLogsEvent($logsbefore,'deleted'));

        LessonComponent::where('comp_id',$quizLesson->quiz_id)->where('lesson_id',$quizLesson->lesson_id)
        ->where('module','Quiz')->delete();

        $lesson = Lesson::find($quizLesson->lesson_id);
        $course_id = $lesson->course_id;

        UserSeen::where('lesson_id',$quizLesson->lesson_id)->where('item_id',$quizLesson->quiz_id)->where('type','quiz')->delete();
        $this->report->calculate_course_progress($course_id);
    }

    /**
     * Handle the quiz lesson "restored" event.
     *
     * @param  \App\QuizLesson  $quizLesson
     * @return void
     */
    public function restored(QuizLesson $quizLesson)
    {
        //
    }

    /**
     * Handle the quiz lesson "force deleted" event.
     *
     * @param  \App\QuizLesson  $quizLesson
     * @return void
     */
    public function forceDeleted(QuizLesson $quizLesson)
    {
        //
    }
}
