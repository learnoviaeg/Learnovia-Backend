<?php
 
namespace Modules\Page\Observers;

use App\LessonComponent;
use Modules\Page\Entities\Page;
use Modules\Page\Entities\PageLesson;
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

    public function deleted(Page $page)
    {

    }
}