<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CourseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        // return parent::toArray($request);
        $levels=[];
        $teacher = [];
        
            $teacher[] = $this->courseSegment->teachersEnroll;
            $start_date = $this->courseSegment->start_date;
            $end_date = $this->courseSegment->end_date;

            $levels[] =  isset($this->levels) ? $this->levels->name : null;
            $temp_course = $this->courseSegment->courses[0];
        // if(!isset($temp_course))
        //     continue;
        return [
            'id' => $temp_course->id ,
            'name' => $temp_course->name ,
            'short_name' => $temp_course->short_name ,
            'image' => isset($temp_course->image) ? $temp_course->attachment->path : null,
            'description' => $temp_course->description ,
            'mandatory' => $temp_course->mandatory,
            'level' => $levels,
            'teachers' => collect($teacher)->collapse()->unique()->values(),
            'start_date' => $start_date,
            'end_date' => $end_date,
            'progress' => round($temp_course->progress,2) ,
        ];
    }
}
