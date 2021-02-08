<?php

namespace Modules\Page\Observers;

use Modules\Page\Entities\PageLesson;
use App\Events\MassLogsEvent;
use Modules\Page\Entities\Page;
use App\Material;
use App\Lesson;

class PageLessonObserver
{
    /**
     * Handle the page lesson "created" event.
     *
     * @param  \App\PageLesson  $pageLesson
     * @return void
     */
    public function created(PageLesson $pageLesson)
    {
        $page = Page::where('id',$pageLesson->page_id)->first();
        $lesson = Lesson::find($pageLesson->lesson_id);
        $course_id = $lesson->courseSegment->course_id;
        if(isset($page)){
            Material::firstOrCreate([
                'item_id' => $pageLesson->page_id,
                'name' => $page->title,
                'publish_date' => $pageLesson->publish_date,
                'course_id' => $course_id,
                'lesson_id' => $pageLesson->lesson_id,
                'type' => 'page',
                'visible' => $pageLesson->visible,
            ]);
        }
    }

    /**
     * Handle the page lesson "updated" event.
     *
     * @param  \App\PageLesson  $pageLesson
     * @return void
     */
    public function updated(PageLesson $pageLesson)
    {
        $page = Page::where('id',$pageLesson->page_id)->first();
        if(isset($page)){
            Material::where('item_id',$pageLesson->page_id)->where('lesson_id',$pageLesson->getOriginal('lesson_id'))->where('type' , 'page')
            ->update([
                'item_id' => $pageLesson->page_id,
                'name' => $page->title,
                'publish_date' => $pageLesson->publish_date,
                'lesson_id' => $pageLesson->lesson_id,
                'type' => 'page',
                'visible' => $pageLesson->visible,
            ]);
        }
        
    }

    /**
     * Handle the page lesson "deleted" event.
     *
     * @param  \App\PageLesson  $pageLesson
     * @return void
     */
    public function deleted(PageLesson $pageLesson)
    {
        //for log event
        $logsbefore= Material::where('lesson_id',$pageLesson->lesson_id)->where('item_id',$pageLesson->page_id)->where('type','page')->get();
        $all = Material::where('lesson_id',$pageLesson->lesson_id)->where('item_id',$pageLesson->page_id)->where('type','page')->delete();
        if($all > 0)
            event(new MassLogsEvent($logsbefore,'deleted'));
    }

    /**
     * Handle the page lesson "restored" event.
     *
     * @param  \App\PageLesson  $pageLesson
     * @return void
     */
    public function restored(PageLesson $pageLesson)
    {
        //
    }

    /**
     * Handle the page lesson "force deleted" event.
     *
     * @param  \App\PageLesson  $pageLesson
     * @return void
     */
    public function forceDeleted(PageLesson $pageLesson)
    {
        //
    }
}
