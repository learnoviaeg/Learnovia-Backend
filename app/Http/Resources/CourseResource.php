<?php

namespace App\Http\Resources;
use App\Segment;
use App\Enroll;
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
        $levels=[];
        $teacher = [];
        $segment = Segment::where('id',$this->segment)->first();
        $start_date = $segment->start_date;
        $end_date = $segment->end_date;
        $levels[] =  isset($this->levels) ? $this->levels->name : null;
        return [
            'id' => $this->courses['id'],
            'name' => $this->courses['name'] ,
            'short_name' => $this->courses['short_name'] ,
            'image' => isset($this->courses['image']) ? $this->courses->attachment->path : null,
            'description' => $this->courses['description'] ,
            'mandatory' => $this->courses['mandatory'] == 1 ? true : false ,
            'level' => $levels,
            'teachers' => Enroll::where('role_id',4)->where('course',$this->courses['id'])->where('level', $this->levels->id)->where('type',$this['type'])
                    ->where('year',$this['year'])->with(array('users' => function($query) {
                        $query->addSelect(array('id', 'firstname', 'lastname', 'picture'))->with('attachment');
                    }))->get(),
            
            'start_date' => $start_date,
            'end_date' => $end_date,
            'progress' => round($this->courses['progress'],2) ,
        ];
    }
}
