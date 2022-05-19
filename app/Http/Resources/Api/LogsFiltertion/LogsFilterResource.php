<?php

namespace App\Http\Resources\Api\LogsFiltertion;

use Illuminate\Http\Resources\Json\JsonResource;

class LogsFilterResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id'              => $this->id,
            'action'          => $this->action,
            'subject_type'    => $this->notes = "link" ? 'link' : $this->subject_type,
            'subject_id'      => $this->subject_id,
            'user_id'         => $this->user_id,
            'created_at'      => $this->created_at,
            'host'            => $this->host,
            'description'     => $this->hole_description,
            // 'since'           => \Carbon\Carbon::parse($this->created_at)->diffForHumans(),
            'username'        => $this->user->fullname,
            'item_name'       => $this->item_name,
            'item_id'         => $this->item_id,
       ];
    }
}
