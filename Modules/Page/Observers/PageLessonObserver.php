<?php

namespace Modules\Page\Observers;
use Modules\Page\Entities\PageLesson;
use App\Events\MassLogsEvent;
use Modules\Page\Entities\Page;
use App\Material;
use App\Repositories\NotificationRepoInterface;
use App\LessonComponent;
use App\Lesson;
use App\CourseItem;
use App\SecondaryChain;

class PageLessonObserver
{
    public function __construct(NotificationRepoInterface $notification)
    {
        $this->notification=$notification;
    }

    /**
     * Handle the page lesson "created" event.
     *
     * @param  \App\PageLesson  $pageLesson
     * @return void
     */
    public function created(PageLesson $pageLesson)
    {
        $sec_chain = SecondaryChain::where('lesson_id',$pageLesson->lesson_id)->first();
        $page = Page::where('id',$pageLesson->page_id)->first();
        $lesson = Lesson::find($pageLesson->lesson_id);
        $course_id = $sec_chain->course_id;
        if(isset($page)){
            $material=Material::firstOrCreate([
                'item_id' => $pageLesson->page_id,
                'name' => $page->title,
                'publish_date' => $pageLesson->publish_date,
                'course_id' =>  $course_id,
                'lesson_id' => $pageLesson->lesson_id,
                'type' => 'page',
                'visible' => $pageLesson->visible,
                'retricted' => 0,
                // added ahmed
                'link'        => 'no-url',
                'mime_type'   => 'no-mime-type',
            ]);
            $courseItem=CourseItem::where('item_id',$pageLesson->page_id)->where('type','page')->first();
            if(isset($courseItem))
            {
                $material->restricted=1;
                $material->save();
            }
            if($courseItem == null)
            {
                $users=SecondaryChain::select('user_id')->where('lesson_id',$material->lesson_id)->pluck('user_id');
                $this->notification->sendNotify($users->toArray(),$material->name. " ". $material->type.' is created',$material->item_id,'notification',$material->type);    
            }
            LessonComponent::firstOrCreate([
                'lesson_id' => $lesson->id,
                'comp_id' => $page->id,
                'module' => 'Page',
                'model' => 'page',
                'index' => LessonComponent::getNextIndex($lesson->id)
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
            Material::where('item_id',$pageLesson->page_id)->where('lesson_id',$pageLesson->getOriginal('lesson_id'))->where('type' , 'page')->first()
            ->update([
                'item_id' => $pageLesson->page_id,
                'name' => $page->title,
                'publish_date' => $pageLesson->publish_date,
                'lesson_id' => $pageLesson->lesson_id,
                'type' => 'page',
                'visible' => $pageLesson->visible,
                'publish_date' => $pageLesson->publish_date,
            ]);

            ////updating component lesson and indexing mods in old lesson in case of updating lesson
            if($pageLesson->getOriginal('lesson_id') != $pageLesson->lesson_id ){
                $current_lesson_component = LessonComponent::select('index')->where('lesson_id',$pageLesson->getOriginal('lesson_id'))->where('comp_id',$pageLesson->page_id)
                ->where('model' , 'page')->first();

                LessonComponent::where('lesson_id',$pageLesson->getOriginal('lesson_id'))
                ->where('index' ,'>=',$current_lesson_component->index )->decrement('index');
           
                LessonComponent::where('comp_id',$pageLesson->page_id)->where('lesson_id',$pageLesson->getOriginal('lesson_id'))->where('model' , 'page')
                                ->update([
                                    'lesson_id' => $pageLesson->lesson_id,
                                    'comp_id' => $page->id,
                                    'module' => 'Page',
                                    'model' => 'page',
                                    'visible' => $pageLesson->visible,
                                    'publish_date' => $pageLesson->publish_date,
                                    'index' => LessonComponent::getNextIndex($pageLesson->lesson_id)
                                ]);
            }
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
        // $logsbefore= Material::where('lesson_id',$pageLesson->lesson_id)->where('item_id',$pageLesson->page_id)->where('type','page')->get();
        // $all = Material::where('lesson_id',$pageLesson->lesson_id)->where('item_id',$pageLesson->page_id)->where('type','page')->first()->delete();
        $LessonComponent = LessonComponent::where('comp_id',$pageLesson->page_id)->where('lesson_id',$pageLesson->lesson_id)->where('model' , 'page')->first();
       
        if(isset($LessonComponent)){
            $current_lesson_component = LessonComponent::select('index')->where('lesson_id',$pageLesson->lesson_id)->where('comp_id',$pageLesson->page_id)
            ->where('model' , 'page')->first();
            LessonComponent::where('lesson_id',$pageLesson->lesson_id)
            ->where('index' ,'>=',$current_lesson_component->index )->decrement('index');
            $LessonComponent->delete();
        }

        // if($all > 0)
        //     event(new MassLogsEvent($logsbefore,'deleted'));

        LessonComponent::where('comp_id',$pageLesson->page_id)->where('lesson_id',$pageLesson->lesson_id)
        ->where('module','Page')->delete();
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
