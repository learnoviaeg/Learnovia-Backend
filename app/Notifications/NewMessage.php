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

class NewMessage extends Notification
{
    use Queueable;
    public $mess;
 
    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Request $request)
    {
      //  dd($request->message);

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
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
 
    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
                    // the data that i will send in  the messege inn data table
    public function toDatabase()
    {
        return ([
            'message' =>$this->mess->message,
            'from'=>$this->mess->from,
            'to'=>$this->mess->to,
            'type'=>$this->mess->type,
            'course_id'=>$this->mess->course_id,
    
    
            ]);
    }
}