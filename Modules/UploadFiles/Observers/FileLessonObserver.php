<?php

namespace Modules\UploadFiles\Observers;

use Modules\UploadFiles\Entities\FileLesson;
use App\Events\MassLogsEvent;
use Modules\UploadFiles\Entities\File;
use App\Lesson;
use App\Material;
use App\SecondaryChain;

class FileLessonObserver
{
    /**
     * Handle the file lesson "created" event.
     *
     * @param  \App\FileLesson  $fileLesson
     * @return void
     */
    public function created(FileLesson $fileLesson)
    {
        $sec_chain = SecondaryChain::where('lesson_id',$fileLesson->lesson_id)->first();
        $file = File::where('id',$fileLesson->file_id)->first();
        $lesson = Lesson::find($fileLesson->lesson_id);
        $course_id = $sec_chain->course_id;
        if(isset($file)){
            Material::firstOrCreate([
                'item_id' => $fileLesson->file_id,
                'name' => $file->name,
                'publish_date' => $fileLesson->publish_date,
                'course_id' => $course_id,
                'lesson_id' => $fileLesson->lesson_id,
                'type' => 'file',
                'visible' => $fileLesson->visible,
                'link' => $file->url,
                'mime_type'=> $file->type,

            ]);
        }
    }

    /**
     * Handle the file lesson "updated" event.
     *
     * @param  \App\FileLesson  $fileLesson
     * @return void
     */
    public function updated(FileLesson $fileLesson)
    {
        $file = File::where('id',$fileLesson->file_id)->first();
        if(isset($file)){
            $logsbefore=Material::where('item_id',$fileLesson->file_id)->where('lesson_id',$fileLesson->getOriginal('lesson_id'))
                                ->where('type' , 'file')->first();
            $logsbefore->update([
                            'item_id' => $fileLesson->file_id,
                            'name' => $file->name,
                            'publish_date' => $fileLesson->publish_date,
                            'lesson_id' => $fileLesson->lesson_id,
                            'type' => 'file',
                            'visible' => $fileLesson->visible,
                            'link' => $file->url,    
                            'mime_type'=> $file->type,
                        ]);
        }
    }

    /**
     * Handle the file lesson "deleted" event.
     *
     * @param  \App\FileLesson  $fileLesson
     * @return void
     */
    public function deleted(FileLesson $fileLesson)
    {
        //for log event
        $logsbefore=Material::where('lesson_id',$fileLesson->lesson_id)->where('item_id',$fileLesson->file_id)->where('type','file')->get();
        $all = Material::where('lesson_id',$fileLesson->lesson_id)->where('item_id',$fileLesson->file_id)->where('type','file')->first()->delete();
        if($all > 0)
            event(new MassLogsEvent($logsbefore,'deleted'));
    }

    /**
     * Handle the file lesson "restored" event.
     *
     * @param  \App\FileLesson  $fileLesson
     * @return void
     */
    public function restored(FileLesson $fileLesson)
    {
        //
    }

    /**
     * Handle the file lesson "force deleted" event.
     *
     * @param  \App\FileLesson  $fileLesson
     * @return void
     */
    public function forceDeleted(FileLesson $fileLesson)
    {
        //
    }
}
