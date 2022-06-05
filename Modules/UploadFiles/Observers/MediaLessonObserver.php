<?php

namespace Modules\UploadFiles\Observers;

use Modules\UploadFiles\Entities\MediaLesson;
use App\Events\MassLogsEvent;
use Modules\UploadFiles\Entities\Media;
use App\Lesson;
use App\Material;
use App\SecondaryChain;
use App\LessonComponent;
use App\CourseItem;

class MediaLessonObserver
{
    /**
     * Handle the media lesson "created" event.
     *
     * @param  \App\MediaLesson  $mediaLesson
     * @return void
     */
    public function created(MediaLesson $mediaLesson)
    {
        $sec_chain = SecondaryChain::where('lesson_id',$mediaLesson->lesson_id)->first();
        $media = Media::where('id',$mediaLesson->media_id)->first();
        $lesson = Lesson::find($mediaLesson->lesson_id);
        $course_id = $sec_chain->course_id;
        if(isset($media)){
            $material=Material::firstOrCreate([
                'item_id' => $mediaLesson->media_id,
                'name' => $media->name,
                'publish_date' => $mediaLesson->publish_date,
                'course_id' => $course_id,
                'lesson_id' => $mediaLesson->lesson_id,
                'type' => 'media',
                'visible' => $mediaLesson->visible,
                'link' => $media->link,
                'mime_type'=>($media->show&&$media->type==null )?'media link':$media->type
            ]);

            LessonComponent::firstOrCreate([
                'lesson_id' => $mediaLesson->lesson_id,
                'comp_id'   => $mediaLesson->media_id,
                'module'    => 'UploadFiles',
                'model'     => 'media',
                'index' => LessonComponent::getNextIndex($mediaLesson->lesson_id)
            ]);

            $courseItem=CourseItem::where('item_id',$mediaLesson->media_id)->where('type','media')->first();
            if(isset($courseItem))
            {
                $material->restricted=1;
                $material->save();
            }
        }
    }

    /**
     * Handle the media lesson "updated" event.
     *
     * @param  \App\MediaLesson  $mediaLesson
     * @return void
     */
    public function updated(MediaLesson $mediaLesson)
    {
        $media = Media::where('id',$mediaLesson->media_id)->first();
        if(isset($media)){
            $logsbefore=Material::where('item_id',$mediaLesson->media_id)->where('lesson_id',$mediaLesson->getOriginal('lesson_id'))
                                    ->where('type' , 'media')->first();
            $logsbefore ->update([
                                'item_id' => $mediaLesson->media_id,
                                'name' => $media->name,
                                'publish_date' => $mediaLesson->publish_date,
                                'lesson_id' => $mediaLesson->lesson_id,
                                'type' => 'media',
                                'visible' => $mediaLesson->visible,
                                'link' => $media->link,
                                'mime_type'=>($media->show&&$media->type==null )?'media link':$media->type
                            ]);
        }

        ////updating component lesson and indexing mods in old lesson in case of updating lesson
        if($mediaLesson->getOriginal('lesson_id') != $mediaLesson->lesson_id ){
            $current_lesson_component = LessonComponent::select('index')->where('lesson_id',$mediaLesson->getOriginal('lesson_id'))->where('comp_id',$mediaLesson->media_id)
            ->where('model' , 'media')->first();

            LessonComponent::where('lesson_id',$mediaLesson->getOriginal('lesson_id'))
            ->where('index' ,'>=',$current_lesson_component->index )->decrement('index');
        
            LessonComponent::where('comp_id',$mediaLesson->media_id)->where('lesson_id',$mediaLesson->getOriginal('lesson_id'))->where('model' , 'media')
                            ->update([
                                'lesson_id' => $mediaLesson->lesson_id,
                                'comp_id' => $media->id,
                                'module' => 'UploadFiles',
                                'model' => 'media',
                                'visible' => $mediaLesson->visible,
                                'publish_date' => $mediaLesson->publish_date,
                                'index' => LessonComponent::getNextIndex($mediaLesson->lesson_id)
                            ]);
        }
    }

    /**
     * Handle the media lesson "deleted" event.
     *
     * @param  \App\MediaLesson  $mediaLesson
     * @return void
     */
    public function deleted(MediaLesson $mediaLesson)
    { 
        //for log event
        // dd($mediaLesson);
        // $logsbefore=Material::where('lesson_id',$mediaLesson->lesson_id)->where('item_id',$mediaLesson->media_id)->where('type','media')->get();
        // $all = Material::where('lesson_id',$mediaLesson->lesson_id)->where('item_id',$mediaLesson->media_id)->where('type','media')->first()->delete();

        $LessonComponent = LessonComponent::where('comp_id',$mediaLesson->media_id)->where('lesson_id',$mediaLesson->lesson_id)->where('model' , 'media')->first();
        // if($LessonComponent != null){
        //     dd($LessonComponent);

            $current_lesson_component = LessonComponent::select('index')->where('lesson_id',$mediaLesson->lesson_id)->where('comp_id',$mediaLesson->media_id)
            // ->where('model' , 'media')
            ->where('module', 'UploadFiles')->where('model', '!=', 'file')->first();
            LessonComponent::where('lesson_id',$mediaLesson->lesson_id)
            ->where('index' ,'>=',$current_lesson_component->index )->decrement('index');
            $LessonComponent->delete();

        // $LessonComponent = LessonComponent::where('comp_id',$mediaLesson->media_id)->where('lesson_id',$mediaLesson->lesson_id)->where('model' , 'media')->first();

        // if(isset($LessonComponent)){
        //     $current_lesson_component = LessonComponent::select('index')->where('lesson_id',$mediaLesson->lesson_id)->where('comp_id',$mediaLesson->media_id)
        //     ->where('model' , 'media')->first();
        //     LessonComponent::where('lesson_id',$mediaLesson->lesson_id)
        //     ->where('index' ,'>=',$current_lesson_component->index )->decrement('index');
        //     $LessonComponent->delete();
        // }

        // if($all > 0)
        //     event(new MassLogsEvent($logsbefore,'deleted'));

        LessonComponent::where('comp_id',$mediaLesson->media_id)
            ->where('lesson_id',$mediaLesson->lesson_id)
            ->where('module','UploadFiles')
            ->where('model' , 'media')
            ->delete();
    }

    /**
     * Handle the media lesson "restored" event.
     *
     * @param  \App\MediaLesson  $mediaLesson
     * @return void
     */
    public function restored(MediaLesson $mediaLesson)
    {
        //
    }

    /**
     * Handle the media lesson "force deleted" event.
     *
     * @param  \App\MediaLesson  $mediaLesson
     * @return void
     */
    public function forceDeleted(MediaLesson $mediaLesson)
    {
        //
    }
}
