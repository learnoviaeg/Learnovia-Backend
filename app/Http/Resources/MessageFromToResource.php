<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Message;
use  App\User;
use Illuminate\Support\Facades\Auth;

class MessageFromToResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */

    public function toArray($request)
    {
        $session_id = Auth::User()->id;
        $from = User::find($this->From);
        $To = User::find($this->To);
        $arr = [
            'id' => $this->id,
            'Message' => $this->text,
            'about' => User::find($this->about),
            'From' => $from,
            'To' => $To,
            'Seen'=>$this->seen,
            'file' => url('/public/storage/' . $this->file),
        ];
        if ($this->deleted == 0) {
            return $arr;
        } elseif ($this->deleted == Message::$DELETE_FROM_ALL) {
            $arr['Message'] = "this message was Deleted for All";
            return $arr;
        } elseif ($this->deleted == Message::$DELETE_FOR_RECEIVER && $session_id == $this->To) {
            $arr['Message'] = "this message was Deleted";
            return $arr;
        } elseif ($this->deleted == Message::$DELETE_FOR_RECEIVER && $session_id == $this->From) {
            return $arr;
        } elseif ($this->deleted == Message::$DELETE_FOR_SENDER && $session_id == $this->From) {
            $arr['Message'] = "this message was Deleted";
            return $arr;
        } elseif ($this->deleted == Message::$DELETE_FOR_SENDER && $session_id == $this->To) {
            return $arr;
        }
    }
}
