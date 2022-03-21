<?php
 
namespace Modules\Page\Observers;

use App\LessonComponent;
use Modules\Page\Entities\Page;
use App\Material;

class PageObserver
{
    /**
     * Handle the page "updated" event.
     *
     * @param  \App\Page  $page
     * @return void
     */
    public function updated(Page $page)
    {
        Material::where('item_id',$page->id)->where('type' , 'page')
        ->update([
            'name' => $page->title,
        ]);
    }

    public function deleted(PageLesson $lesson)
    {
        LessonComponent::where('comp_id',$lesson->page_id)->where('lesson_id',$lesson->lesson_id)
        ->where('module','Page')->delete();
    }
}