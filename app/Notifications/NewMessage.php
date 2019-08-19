<?php
// app/Notifications/NewMessage.php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use App\User;
use Illuminate\Http\Request;
use App\Message;
use Illuminate\Support\Facades\Validator;

class NewMessage extends Notification
{
    use Queueable;
    public $mess;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($request)
    {
        //
        $this->mess = $request;

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


    public function toDatabase($notifiable)
    {

        if($this->mess['type']=='announcement')
        {
            if(isset($this->mess['attached_file'])){
            $returnobj = [
                'title'=> $this->mess['title'],
                'type'=>$this->mess['type'],
                'description'=>$this->mess['description'],
                'attached_file'=>$this->mess['attached_file']
            ];
        }
        else
        {
            $returnobj = [
                'title'=> $this->mess['title'],
                'type'=>$this->mess['type'],
                'description'=>$this->mess['description'],
            ];
        }

        return $returnobj;
        }
        else
        {
            return ([
                'message' =>$this->mess['message'],
                'from'=>$this->mess['from'],
                'type'=>$this->mess['type'],
                'course_id'=>$this->mess['course_id'],
            ]);
        }

    }
}
