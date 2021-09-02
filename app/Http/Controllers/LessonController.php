<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Lesson;
use App\CourseSegment;
use App\attachment;
use App\LastAction;
use App\SecondaryChain;
use App\Events\LessonCreatedEvent;

class LessonController extends Controller
{
    /**
     * Add lesson
     * 
     * @param  [string] name 
     * @param  [array] image
     * @param  [array/string] description
     * @return if no course segment [string] Something went wrong or no active segments on this class
     * @return [string] added successfully
    */
    public function AddLesson(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            // 'image' => 'array',
            'image' => 'mimes:jpeg,jpg,png,gif|max:10000',
            // 'description' => 'array',
            'description' => 'string',
            'course' => 'required|exists:courses,id',
            'classes' => 'required|array',
            'classes.*' => 'exists:classes,id',
            'shared_lesson' => 'required|in:0,1'
        ]);
        LastAction::lastActionInCourse($request->course);
        $lessons_in_Course = Lesson::where('course_id', $request->course)->max('index');
        // return $lessons_in_Course;
        $Next_index = $lessons_in_Course + 1;
        $lesson= Lesson::create([
            'name' => $request->name,
            'course_id' => $request->course,
            'shared_lesson' => $request->shared_lesson,
            'index' => $Next_index,
            'shared_classes' => json_encode($request->classes),
        ]);
        if (isset($request->image))
            $lesson->image = attachment::upload_attachment($request->image[$key], 'lesson', '')->path;
        
        if (isset($request->description))
            $lesson->description = $request->description;
        
        $lesson->save();
        return HelperController::api_response_format(200, Lesson::where('course_id',$request->course)->get(),__('messages.lesson.add'));
    }

    /**
     * show lesson
     * 
     * @param  [int] id 
     * @return [object] lesson
    */
    public function ShowLesson(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:lessons,id',
        ]);
        $lesson = Lesson::find($request->id);
        return HelperController::api_response_format(200, $lesson);
    }

    /**
     * delete lesson
     * 
     * @param  [int] id 
     * @return [string] Lesson is deleted Successfully
     * 
    */
    public function deleteLesson(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:lessons,id',

        ]);
        $lesson = Lesson::find($request->id);
        LastAction::lastActionInCourse($lesson->course_id);
        $lesson->delete();
        $secondary_chain = SecondaryChain::where('lesson_id',$request->id)->delete();
        $lessons = Lesson::where('course_id',$lesson->course_id)->where('index', '>', $lesson->index)->get();
        foreach ($lessons as $temp) {
            $temp->index = $temp->index - 1;
            $temp->save();
        }
        return HelperController::api_response_format(200, null, __('messages.lesson.delete'));
    }

    /**
     * update lesson
     * 
     * @param  [string] name, description
     * @param  [int] id 
     * @param  [string..path] image
     * @return [string] Lesson is updated Successfully
    */
    public function updateLesson(Request $request)
    {
        $request->validate([
            'name' => 'nullable',
            'id'  => 'required|exists:lessons,id',
            'image' => 'mimes:jpeg,jpg,png,gif|max:10000',
            'description' => 'string',
            'classes' => 'nullable|array',
            'classes.*' => 'exists:classes,id',
        ]);
        $lesson = Lesson::find($request->id);
        LastAction::lastActionInCourse($lesson->course_id);
        $lesson->name = $request->name;
        if ($request->hasFile('image')) {
            $lesson->image = attachment::upload_attachment($request->image, 'lesson', '')->path;
        }
         $lesson->description = "";
        if ($request->filled('description')) {
            $lesson->description = $request->description;
        }
        foreach($lesson->shared_classes as $class){
            $secondary_chain = SecondaryChain::where('group_id', $class)->whereNotIn('group_id',$request->classes)->where('lesson_id',$request->id)->delete();
        }
        $lesson->shared_classes = json_encode($request->classes);
        $lesson->save();
        event(new LessonCreatedEvent($lesson));
        return HelperController::api_response_format(200, $lesson, __('messages.lesson.update'));
    }

    /**
     * sort lesson down
     * 
     * @param  [int] lesson_id, index
     * @return [array] all Lessons
    */
    public function sortDown($lesson_id, $index)
    {
        $lesson_index = Lesson::where('id', $lesson_id)->pluck('index')->first();
        $all_lessons = Lesson::Get_lessons_per_CourseSegment_from_lessonID($lesson_id);
        foreach ($all_lessons as $lesson) {
            if ($lesson->index < $index || $lesson->index > $lesson_index) {
                continue;
            } elseif ($lesson->index  !=  $lesson_index) {
                $lesson->update([
                    'index' => $lesson->index + 1
                ]);
            } else {
                $lesson->update([
                    'index' => $index
                ]);
            }
        }
        return $all_lessons;
    }

    /**
     * sort lesson up
     * 
     * @param  [int] lesson_id, index
     * @return [array] all Lessons
    */
    public function SortUp($lesson_id, $index)
    {

        $lesson_index = Lesson::where('id', $lesson_id)->pluck('index')->first();
        $all_lessons = Lesson::Get_lessons_per_CourseSegment_from_lessonID($lesson_id);
        foreach ($all_lessons as $lesson) {
            if ($lesson->index > $index || $lesson->index < $lesson_index) {
                continue;
            } elseif ($lesson->index  !=  $lesson_index) {
                $lesson->update([
                    'index' => $lesson->index - 1
                ]);
            } else {
                $lesson->update([
                    'index' => $index
                ]);
            }
        }
        return $all_lessons;
    }

    /**
     * sorting
     * 
     * @param  [int] lesson_id, index
     * @return [array] all Lessons sorted Successfully
    */
    public function Sorting(Request $request)
    {
        $request->validate([
            'lesson_id' => 'required|integer|exists:lessons,id',
            'index' => 'required|integer',
            'count' => 'integer'
        ]);
        $lesson_index = Lesson::where('id', $request->lesson_id)->pluck('index')->first();

        if ($lesson_index > $request->index) {
            $lessons = $this->sortDown($request->lesson_id, $request->index);
        } else {
            $lessons = $this->SortUp($request->lesson_id, $request->index);
        }
        return HelperController::api_response_format(200, $lessons, __('messages.lesson.sort'));
    }

    public function AddNumberOfLessons(Request $request)
    {
        $request->validate([
            'course' => 'required|exists:courses,id',
            'class' => 'required|exists:classes,id',
            'count' => 'required|integer|min:1'
        ]);

        $courseSeg=CourseSegment::GetWithClassAndCourse($request->class,$request->course);
        $maxIndx=Lesson::where('course_segment_id',$courseSeg->id)->orderBy('index', 'desc')->first();
        for($i=1; $i<=$request->count; $i++)
        {
            $lessons[] = Lesson::create([
                'name' => 'Lesson '.($maxIndx->index+1),
                'course_segment_id' => $courseSeg->id,
                'index' => ++$maxIndx->index
            ]);
        }

        return HelperController::api_response_format(200, $lessons, __('messages.lesson.add'));
    }
}
