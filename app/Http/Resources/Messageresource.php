<?php

namespace App\Http\Resources;

use App\User;
use Illuminate\Http\Resources\Json\JsonResource;

class Messageresource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        $from = User::find($this->From);
        $To = User::find($this->To);
        $MSG = null;
        if ($this->deleted == 1) {
            $MSG = 'this Message is deleted of all';
        } elseif ($this->deleted == 2) {
            $MSG = "this Message is deleted for me";
        } else {
            $MSG = $this->text;
        }
        return [
            'id' => $this->id,
            'Message' => $MSG,
            'Child' => $this->about,
            'From' => $from->name,
            "To" => $To->name
        ];
    }
}
