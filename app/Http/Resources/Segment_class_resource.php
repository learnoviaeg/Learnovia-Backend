<?php

namespace App\Http\Resources;

use App\Classes;
use Illuminate\Http\Resources\Json\JsonResource;
use App\ClassLevel;
class Segment_class_resource extends JsonResource
{

    public function toArray($request)
    {   $Class = Classes::find($this->id);

        $array = [
            'id' =>$this->id,
            'Class id' =>$this->class_id,
            'Class name'=>$this->name,
            "Segments" => []
        ];
        foreach ($this->Segment_class as $type){
            $array['Segments'][] = [
                'name' => $type->name,
            ];
        }
        return $array ;    }
}
