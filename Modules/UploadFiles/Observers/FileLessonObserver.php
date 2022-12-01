<?php

namespace Modules\UploadFiles\Observers;

use Modules\UploadFiles\Entities\FileLesson;
use App\Events\MassLogsEvent;
use Modules\UploadFiles\Entities\File;
use App\Lesson;
use App\Material;
use App\Repositories\NotificationRepoInterface;
use App\CourseItem;
use App\SecondaryChain;
use App\LessonComponent;

class FileLessonObserver
{
    public function __construct(NotificationRepoInterface $notification)
    {
        $this->notification=$notification;
    }

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
            $material=Material::firstOrCreate([
                'item_id' => $fileLesson->file_id,
                'name' => $file->name,
                'publish_date' => $fileLesson->publish_date,
                'course_id' => $course_id,
                'lesson_id' => $fileLesson->lesson_id,
                'type' => 'file',
                'restricted' => 0,
                'visible' => $fileLesson->visible,
                'link' => $file->url,
                'mime_type'=> $file->type,
            ]);

            LessonComponent::firstOrCreate([
                'lesson_id' => $fileLesson->lesson_id,
                'comp_id'   => $fileLesson->file_id,
                'module'    => 'UploadFiles',
                'model'     => 'file',
            ], [
                'index'     => LessonComponent::getNextIndex($fileLesson->lesson_id)
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

            ////updating component lesson and indexing mods in old lesson in case of updating lesson
            if($fileLesson->getOriginal('lesson_id') != $fileLesson->lesson_id ){
                $current_lesson_component = LessonComponent::select('index')->where('lesson_id',$fileLesson->getOriginal('lesson_id'))->where('comp_id',$fileLesson->file_id)
                ->where('model' , 'file')->first();

                LessonComponent::where('lesson_id',$fileLesson->getOriginal('lesson_id'))
                ->where('index' ,'>=',$current_lesson_component->index )->decrement('index');
            
                LessonComponent::where('comp_id',$fileLesson->file_id)->where('lesson_id',$fileLesson->getOriginal('lesson_id'))->where('model' , 'file')
                                ->update([
                                    'lesson_id' => $fileLesson->lesson_id,
                                    'comp_id' => $file->id,
                                    'module' => 'UploadFiles',
                                    'model' => 'file',
                                    'visible' => $fileLesson->visible,
                                    'publish_date' => $fileLesson->publish_date,
                                    'index' => LessonComponent::getNextIndex($fileLesson->lesson_id)
                                ]);
            }

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
        // $logsbefore=Material::where('lesson_id',$fileLesson->lesson_id)->where('item_id',$fileLesson->file_id)->where('type','file')->get();
        // $all = Material::where('lesson_id',$fileLesson->lesson_id)->where('item_id',$fileLesson->file_id)->where('type','file')->first()->delete();
        $LessonComponent = LessonComponent::where('comp_id',$fileLesson->file_id)->where('lesson_id',$fileLesson->lesson_id)->where('model' , 'file')->first();

        if(isset($LessonComponent)){
            $current_lesson_component = LessonComponent::select('index')->where('lesson_id',$fileLesson->lesson_id)->where('comp_id',$fileLesson->file_id)
            ->where('model' , 'file')->first();
            LessonComponent::where('lesson_id',$fileLesson->lesson_id)
            ->where('index' ,'>=',$current_lesson_component->index )->decrement('index');
            $LessonComponent->delete();
        }
        // if($all > 0)
        //     event(new MassLogsEvent($logsbefore,'deleted'));

        LessonComponent::where('comp_id',$fileLesson->media_id)
            ->where('lesson_id',$fileLesson->lesson_id)
            ->where('module','UploadFiles')
            ->where('model' , 'file')
            ->delete();
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
