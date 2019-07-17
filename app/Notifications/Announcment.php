<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
use App\Announcement;
use Illuminate\Support\Facades\Input;


class Announcment extends Notification
{

    use Queueable;
    public $req;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Request $requst)
    {
        $this->req=$requst;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database'];
    }


    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function todatabase($notifiable)
    {
        $returnobj = [
            'title'=> $this->req->title,
            'description'=>$this->req->description,
        ];

        if (Input::hasFile('attached_file')){
            $file=$this->req->file('attached_file');
            $name = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $fileName = $name.'.'.uniqid($this->req->id).'.'.$extension;
            $returnobj['attached_file']= $fileName;
        }
        else
        {
            $fileName='';
        }
        return $returnobj;
    }

}
