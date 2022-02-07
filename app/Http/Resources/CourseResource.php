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
            'image' => isset($this->courses['image']) ? $this->courses['image'] : null,
            'description' => $this->courses['description'] ,
            'mandatory' => $this->courses['mandatory'] ,
            'is_template' => $this->courses['is_template'] ,
            'level' => $levels,
            // 'teachers' => $this->SecondaryChain,
            'teachers' => Enroll::where('role_id',4)->where('course',$this->courses['id'])->with(array('users' => function($query) {
                        $query->addSelect(array('id', 'firstname', 'lastname', 'picture'))->with('attachment');
                    }))->get(),
            
            'start_date' => $start_date,
            'end_date' => $end_date,
            'progress' => round($this->courses['progress'],2) ,
            'shared_lesson'=>$this->course['shared_lesson']
        ];
    }
}
