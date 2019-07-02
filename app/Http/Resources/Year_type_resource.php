<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class Year_type_resource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $array = [
            'name' =>$this->name,
            "Types" => []
        ];
        foreach ($this->AC_Type as $type){
            $array['Types'][] = [
                'name' => $type->name,
                'Segment Number '=> $type->segment_no
            ];
        }
        return $array ;
    }
}
