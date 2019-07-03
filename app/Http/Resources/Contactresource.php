<?php

namespace App\Http\Resources;

use App\User;
use Illuminate\Http\Resources\Json\JsonResource;

class Contactresource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {   $MYFrind=User::find($this->Friend_id);
        return [
            'Friend' => $MYFrind,
         //   'Person_id'=> $request->Person_id

        ];
    }
}
