<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Course;
use App\Segment;
use App\GradeCategory;
use Modules\QuestionBank\Entities\QuizLesson;
use Modules\Assigments\Entities\AssignmentLesson;
use Modules\QuestionBank\Entities\Quiz;
use App\Events\UpdatedAttemptEvent;
use App\LetterDetails;
use Modules\QuestionBank\Entities\QuestionsCategory;
use Modules\QuestionBank\Entities\userQuiz;
use App\GradeItems;
use Auth;
use App\Jobs\migrateChainAmdEnrollment;
use Carbon\Carbon;
use App\UserGrader;
use App\Enroll;
use App\lesson;
use App\Events\GradeItemEvent;
use Modules\QuestionBank\Entities\quiz_questions;
use Modules\QuestionBank\Entities\Questions;
use App\Repositories\ChainRepositoryInterface;
use App\Level;

class ScriptsController extends Controller
{
    public function __construct(ChainRepositoryInterface $chain)
    {
        $this->chain = $chain;
        $this->middleware('auth');
    }

    public function CreateGradeCatForCourse(Request $request)
    {
        $allCourse = Course::all();
        foreach($allCourse as $course)
        {
            $gradeCat = GradeCategory::firstOrCreate([
                'name' => $course->name . ' Total',
                'course_id' => $course->id
            ]);
        }

        return 'done';
    }

    public function gradeAttemptsInQuizlesson(Request $request) //auto correction
    {
        $request->validate([
            'quiz_id' => 'exists:quizzes,id',
            'lesson_id' => 'required_with:quiz_id|exists:quiz_lessons,lesson_id'
        ]);

        if(isset($request->quiz_id)){
            $Quiz_lesson = QuizLesson::where('quiz_id',$request->quiz_id)->where('lesson_id',$request->lesson_id)->first();
            $gradeCat=GradeCategory::whereId($Quiz_lesson->grade_category_id)->update(['calculation_type' => json_encode($Quiz_lesson->grading_method_id)] );
            $users_quiz=userQuiz::where('quiz_lesson_id',$Quiz_lesson->id)->get();
        }
        if(!isset($Quiz_lesson)){
            $users_quiz=userQuiz::cursor();
        }

        foreach($users_quiz as $user_quiz){
            event(new UpdatedAttemptEvent($user_quiz));
        }
        return 'done';
    }


    public function quiz_total_mark(Request $request)
    {
          foreach(QuizLesson::cursor() as $quiz_lesson){
            $quiz_lesson->grade = $quiz_lesson->questions_mark;
            $quiz_lesson->save();
        }
        return 'done';
    }

    public function grade_details_of_questions(Request $request)
    {
        $request->validate([
            'quiz_id' => 'exists:quizzes,id',
            'lesson_id' => 'required_with:quiz_id|exists:quiz_lessons,lesson_id'
        ]);

        if(isset($request->quiz_id)){
            $Quiz_lesson = QuizLesson::where('quiz_id',$request->quiz_id)->where('lesson_id',$request->lesson_id)->first();
            $quizLessons=[$Quiz_lesson];
        }
        if(!isset($Quiz_lesson)){
            $quizLessons=QuizLesson::cursor();
        }
        foreach($quizLessons as $quiz_lesson){
            $grade_cat = GradeCategory::firstOrCreate(
                [
                    'instance_type'=>'Quiz',
                    'instance_id'=> $quiz_lesson->quiz_id,
                    'lesson_id'=> $quiz_lesson->lesson_id,
                    'course_id'=>$quiz_lesson->lesson->course_id,

                ],[
                    'parent' => $quiz_lesson->grade_category_id,
                    'calculation_type' => json_encode($quiz_lesson->grading_method_id),
                    'hidden' => 0 ,
                ]);
                $quiz_lesson->grade_category_id = $grade_cat->id;
                $quiz_lesson->save();
                $max_attempt=$quiz_lesson->max_attemp;                
                    
                for($key =1; $key<=$max_attempt; $key++){
                    $gradeItem = GradeItems::updateOrCreate([
                        'index' => $key,
                        'grade_category_id' => $grade_cat->id,
                        'name' => 'Attempt number ' .$key,
                    ],
                    [
                        'type' => 'Attempts',
                    ]
                );    
                    $enrolled_students = Enroll::where('role_id' , 3)->where('course',$quiz_lesson->lesson->course_id)->pluck('user_id');
                    foreach($enrolled_students as $student){
                        UserGrader::updateOrCreate([
                            'user_id'   => $student,
                            'item_type' => 'Item',
                            'item_id'   => $gradeItem->id
                        ],
                        [
                            'grade'     => null
                        ]);
                    }
                    event(new GradeItemEvent($gradeItem));
                }
        }
        return 'done';
    }

    public function deleteWrongAttempts()
    {
        $user_quizzes=userQuiz::all();
        foreach($user_quizzes as $userQuiz)
        {
            if(Carbon::parse($userQuiz->open_time) > Carbon::parse($userQuiz->quiz_lesson->due_date))
            {
                if(count($userQuiz->quiz_lesson->override) > 0)
                {
                    foreach($userQuiz->quiz_lesson->override as $overwrite)
                    {
                        if(Carbon::parse($userQuiz->open_time) > $overwrite->due_date)
                        {
                            $userQuiz->delete();
                            continue;
                        }
                    }
                }
                else
                    $userQuiz->delete();
            }
        }
        return 'done';
    }

    public function reassign_shuffled_questions(){
        $quizzes =  Quiz::select('id')->whereIn('shuffle', ['Answers' , 'Questions and Answers']);
        $callback = function ($query)  {
                        $query->where('question_type_id' , 2);
                    };
        $quizzes_questions = $quizzes->whereHas('Question', $callback)->with(['Question'=> $callback])->get();
        foreach($quizzes_questions as $quiz_questions){
                foreach($quiz_questions->Question as $question){
                    $question_with_wrong_content = quiz_questions::where('question_id' , $question->id)->first();
                    $choices = [];
                    foreach($question_with_wrong_content->grade_details->details as $wrong_q){
                        $choices['type'] = $question_with_wrong_content->grade_details->type;
                        foreach($question->content as $right_quest){
                            if($right_quest->content == $wrong_q->content){
                                $wrong_q->key = $right_quest->key;
                                $choices['details'][] = $wrong_q;
                            }
                        }
                    }
                    $choices['total_mark'] = $question_with_wrong_content->grade_details->total_mark;
                    $choices['exclude_mark'] = $question_with_wrong_content->grade_details->exclude_mark;
                    $choices['exclude_shuffle'] = $question_with_wrong_content->grade_details->exclude_shuffle;
                    $question_with_wrong_content->update(['grade_details' => json_encode($choices)]);
            }
        }
        return 'done';
    }

    public function Full_Mark(Request $request)
    {
        $request->validate([
            'course' => 'required|exists:courses,short_name',
            'quiz_id' => 'exists:quizzes,id',
            'lesson_id' => 'required_with:quiz_id|exists:quiz_lessons,lesson_id'
        ]);

        if(isset($request->course))
        {
            $course=Course::where('short_name',$request->course)->first();
            $quizzesLessId=QuizLesson::whereIn('quiz_id',Quiz::where('course_id',$course->id)->pluck('id'))->pluck('id');
            if(isset($request->quiz_id)){
                $Quiz_lesson = QuizLesson::where('quiz_id',$request->quiz_id)->where('lesson_id',$request->lesson_id)->first();
                $users_quiz=userQuiz::where('quiz_lesson_id',$Quiz_lesson->id)->get();
            }
            if(!isset($Quiz_lesson)){
                $users_quiz=userQuiz::whereIn('quiz_lesson_id',$quizzesLessId)->get();
            }
            foreach($users_quiz as $user_quiz){
                $user_quiz->grade=$user_quiz->quiz_lesson->grade;
                $uu=User::find($user_quiz->user_id);
                if(isset($uu)){
                    if(!in_array(3,$uu->roles->pluck('id')->toArray())){
                        continue;
                    }
                }
                $user_grader=UserGrader::where('item_type','category')->where('item_id',$user_quiz->quiz_lesson->grade_category_id)->
                                    where('user_id',$user_quiz->user_id)->first();
                $user_grader->update(['grade' => $user_quiz->quiz_lesson->grade]);
                $user_quiz->save();
                $user_grader->save();
            }
        }
        return 'done';
    }

    public function user_grades(Request $request)
    {
        $request->validate([
            'courses'    => 'nullable|array',
            'courses.*'  => 'nullable|integer|exists:courses,id',
        ]);
        $courses = Course::whereIn('id', $request->courses)->with('gradeCategory')->get();
        foreach($courses as $course)
        {
            foreach($course->gradeCategory as $category){
                $enrolls = $this->chain->getEnrollsByManyChain($request)->where('role_id',3)->select('user_id')->distinct('user_id')->pluck('user_id');
                foreach($enrolls as $user){
                    UserGrader::firstOrCreate(
                        ['item_id' =>  $category->id , 'item_type' => 'category', 'user_id' => $user],
                        ['grade' => null]
                    );
                }

            }
        
        }
        return 'done';
    }

    public function updateGradeCatParent()
    {
        $parents=GradeCategory::whereNull('parent')->where('type','category')->get();
        foreach($parents as $parent)
        {
            $parent->update([
                'calculation_type' => json_encode(["Natural"]),
            ]);
        }
        return 'done';
    }

    public function update_letter_percentage(Request $request)
    {
        $request->validate([
            'course'  => 'required|integer|exists:courses,id',
        ]);
        $userGradesJob = (new \App\Jobs\PercentageAndLetterCalculation(Course::where('id' , $request->course)->first()));
        dispatch($userGradesJob);
        return 'done';
    }

    public function MigrateChainWithEnrollment(Request $request)
    {
        $request->validate([
            'segment_id'  => 'required|exists:segments,id',
        ]);

        // $migrated = (new \App\Jobs\migrateChainAmdEnrollment($request->segment_id));
        // dispatch($migrated);
        // dd($migrated);

        $newSegment=Segment::find($request->segment_id);
        $type=$newSegment->academic_type_id;
        $oldSegment=Segment::Get_current_by_one_type($type);
        $courses=Course::where('segment_id',$oldSegment)->get();

        foreach($courses as $course)
        {
            if(Course::where('segment_id',$newSegment->id)->where('short_name',$course->short_name . "_" .$newSegment->name)->count() > 0)
                continue;
                
            $coco=Course::firstOrCreate([
                'name' => $course->name. "_" .$newSegment->name,
                'short_name' => $course->short_name . "_" .$newSegment->name],[
                'image' => $course->getOriginal()['image'],
                'category_id' => $course->category,
                'description' => $course->description,
                'mandatory' => $course->mandatory,
                'level_id' => $course->level_id,
                'is_template' => $course->is_template,
                'classes' => json_encode($course->classes),
                'segment_id' => $newSegment->id,
                'letter_id' => $course->letter_id
            ]);

            for ($i = 1; $i <= 4; $i++) {
                $lesson=lesson::firstOrCreate([
                    'name' => 'Lesson ' . $i,
                    'index' => $i,
                    'shared_lesson' => 1,
                    'course_id' => $coco->id,
                    'shared_classes' => json_encode($course->classes),
                ]);
            }

            //Creating defult question category
            $quest_cat = QuestionsCategory::firstOrCreate([
                'name' => $coco->name . ' Category',
                'course_id' => $coco->id,
            ]);

            $gradeCat = GradeCategory::firstOrCreate([
                'name' => $coco->name . ' Total',
                'course_id' => $coco->id,
                'calculation_type' => json_encode(['Natural']),
            ]);

            $enrolls=Enroll::where('course',$course->id)->whereIn('segment',$oldSegment->toArray())->where('type',$type)->get()->unique();
            foreach($enrolls as $enroll)
            {
                $f=Enroll::firstOrCreate([
                    'user_id' => $enroll->user_id,
                    'role_id'=> $enroll->role_id,
                    'year' => $enroll->year,
                    'type' => $type,
                    'level' => $enroll->level,
                    'group' => $enroll->group,
                    'segment' => $newSegment->id,
                    'course' => $coco->id
                ]);
            }
        }

        return 'Done';
    }

    public function delete_duplicated(Request $request)
    {
        $request->validate([
            'segment_id'  => 'required|exists:segments,id',
        ]);
        
        foreach(Course::where('segment_id',$request->segment_id)->cursor() as $course)
        {
            if(count(Course::where('short_name',$course->short_name)->get()) > 1)
                Course::where('short_name',$course->short_name)->first()->delete();
        }

        return 'Done';
    }

    public function changeLetterName(Request $request)
    {
        LetterDetails::where('evaluation','Passed')->update(['evaluation' => 'Fair']);
        UserGrader::where('letter', 'Passed')->update(['letter' => 'Fair']);
        return 'Done';
    }

    public function course_index(Request $request)
    {
        foreach(Level::select('id')->cursor() as $level){
            foreach(Course::where('level_id',$level->id)->cursor() as $key => $course){
                $course->update([ 'index' => $key+1 ]);;
            }
        }
        return 'done';
    }

    public function indexCatItem(Request $request)
    {
        foreach(Course::where('segment_id',3)->pluck('id') as $course)
        {
            $gradeCategoryParent=GradeCategory::where('course_id',$course)->whereNull('parent')->first();
            $grades=GradeCategory::where('id',$gradeCategoryParent->id)->with('categories_items')->get();
            self::index($grades);
        }

        return 'Done';
    }

    public function index($gradeCat)
    {
        $index=1;
        foreach($gradeCat as $grade)
        {
            if($grade->index == null)
            {
                $grade->index=$index;
                $grade->save();
                if(count($grade->categories_items) >= 1)
                    self::index($grade->categories_items);
    
                $index++;
            }
        }
    }

    public function ongoingPastCoursesIssue(Request $request)
    {
        foreach(Course::cursor() as $course){
            $enrolled_students = Enroll::where('course', $course->id)->where('segment', '!=' , $course->segment_id);
            if($enrolled_students->count() > 0)
                $enrolled_students->delete();
        }
        return 'Done';
    }

    public function lessons_index(Request $request)
    {
        foreach(Course::select('id')->cursor() as $course){
            foreach(Lesson::where('course_id',$course->id)->cursor() as $key => $lesson){
                $lesson->update([ 'index' => $key+1 ]);;
            }
        }
        return 'done';
    }
    public function delete_duplicated_enroll(Request $request)
    {
        $enrolls=Enroll::select('user_id')->where('role_id',3)->where('course',$request->course_id)->get();
        foreach($enrolls as $enroll)
        {   
            $count=Enroll::where('user_id',$enroll->user_id)->where('course',$request->course_id);
            if($count->count() > 1)
                $countss=$count->first()->delete();
        }
        return 'Done';
    }

    public function update_publish_date(Request $request)
    {
        $assignments=AssignmentLesson::get();
        foreach($assignments as $assignment)
        {
            $assignment->publish_date = $assignment->start_date;
            $assignment->save();
        }

        $quizzes = QuizLesson::get();
        foreach($quizzes as $quiz)
        {
            $quiz->publish_date=$quiz->start_date;
            $quiz->save();
        }
        return 'Done';
    }
}
