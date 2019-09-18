<?php

namespace App\Http\Controllers;

use App\Contacts;
use App\User;
use Illuminate\Http\Request;
use File;
use Spatie\Permission\Traits\HasRoles;
use Validator;
use Session;
use App\Http\Resources\Messageresource;
use App\Http\Resources\MessageFromToResource;
use App\Message_Role;
use Illuminate\Support\Facades\Auth;
use App\Message;
use DB;
use Illuminate\Support\Facades\Storage;
use App\attachment;

class MessageController extends Controller
{
    /**
     * => Function send_message_of_all_user sends message for all ids which get from request
     * @param: => from request
     *         - text of message
     *         - file and it's optional
     *         - id/s of users as an array
     *         - about what this mssage
     *         - from who for now \\next will be with session
     * @return: => Successfully Sent Message! if will success
     */
    // please before  excute this fun  run  php artisan Storage:link


    public function Send_message_of_all_user(Request $req)
    {
        return response()->json($req->file->getClientOriginalExtension());
        $session_id = Auth::User()->id;
        $valid = Validator::make($req->all(), [
            'text' => 'nullable',
            'about' => 'exists:users,id',
            'users' => 'required|array',
            'users.*' => 'required|integer|exists:users,id',
            'file' => 'file|mimes:pdf,docx,doc,xls,xlsx,ppt,pptx,zip,rar,jpg,jpeg,png,gif',
        ]);
        if ($valid->fails()) {
            return HelperController::api_response_format(404, null, $valid->errors());
        }
        foreach ($req->users as $userId) {
            if ($session_id != $userId) {
                $user = User::find($userId);
                $to_Role_ids = $user->roles->pluck('id');
                $loged_user = User::find($session_id);
                $From_Role_ids = $loged_user->roles->pluck('id');
                $is_send = false;
                foreach ($to_Role_ids as $to_Role_id) {
                    foreach ($From_Role_ids as $From_Role_id) {
                        $permission = Message_Role::where('From_Role', $From_Role_id)->where('To_Role', $to_Role_id)->first();
                        if ($permission) {
                            $message = Message::Create(array(
                                'text' => $req->text,
                                'about' => (!$req->filled('about')) ? $req->user()->id : $req->about,
                                'From' => $req->user()->id,
                                'seen' => false,
                                'deleted' => 0,
                                'To' => $userId,
                            ));
                            if($req->hasFile('file')){
                                $attachment = attachment::upload_attachment($req->file , 'message');
                                $message->file = $attachment->path;
                            }
                            $message->save();
                            $is_send = true;
                            break;
                        }
                    }
                    if ($is_send) {
                        break;
                    }
                }
                if ($is_send == false) {
                    return HelperController::api_response_format(404, null, 'Fail ,  you do not have a permission to send message to this user!');
                }
            } else {
                return HelperController::api_response_format(404, null, 'Fail , you can not send message for yourself!');
            }
        }
        return HelperController::api_response_format(201, null, 'Successfully Sent Message!');


    }


    /**
     * @description:  delete message for all
     * @param Request $req => id of message that you want to delete
     * @return deleted if message was deleted  for all
     */

    public
    function deleteMessageForAll(Request $req)
    {
        $session_id = Auth::User()->id;
        $valid = Validator::make($req->all(), [
            'id' => 'required|exists:messages,id',
        ]);
        if ($valid->fails()) {
            return HelperController::api_response_format(404, null, $valid->errors());
        }
        $message = Message::find($req->id);
        if ($message->From == $session_id || $message->To == $session_id) {
            $message->update(array(
                'deleted' => Message::$DELETE_FROM_ALL
            ));
            $message->save();

            return HelperController::api_response_format(201, null, 'message was deleted');

        } else {
            return HelperController::api_response_format(404, null, 'You do not have permission delete this message');
        }
    }

    /*
    @Description: delete Message for user
                           0=default
                           1=deleted for all
                           2=deleted by receiver
                           3=deleted by sender
     @param: id of message and my_id if not use session
    @return: 'message' => 'message was deleted'


    */
    public
    function deleteMessageforMe(Request $req)
    {
        $session_id = Auth::User()->id;


        $valid = Validator::make($req->all(), [
            'id' => 'required|exists:messages,id',

        ]);
        if ($valid->fails()) {
            return response()->json(['msg' => $valid->errors()], 404);
        }

        $message = Message::find($req->id);
        if ($message->From == $session_id || $message->To == $session_id) {

            if ($session_id == $message->To) {
                if ($message) {
                    if ($message->deleted == Message::$DELETE_FOR_SENDER || $message->deleted == Message::$DELETE_FROM_ALL) {
                        $message->update(array(
                            'deleted' => Message::$DELETE_FROM_ALL
                        ));

                    } else {
                        $message->update(array(
                            'deleted' => Message::$DELETE_FOR_RECEIVER
                        ));
                    }
                }

            } elseif ($session_id == $message->From) {

                if ($message) {
                    if ($message->deleted == Message::$DELETE_FOR_RECEIVER || $message->deleted == Message::$DELETE_FROM_ALL) {
                        $message->update(array(
                            'deleted' => Message::$DELETE_FROM_ALL
                        ));
                    } else {
                        $message->update(array(
                            'deleted' => Message::$DELETE_FOR_SENDER
                        ));
                    }
                }
            }

            $message->save();
            return HelperController::api_response_format(201, null, 'message was deleted');
        } else {
            return HelperController::api_response_format(404, null, 'You do not have permission delete this message');
        }
    }

    /**
     * Function list all messages take no parameter
     * @return all messages
     */
    public
    function List_All_Message()
    {
        $Message = Messageresource::collection(Message::get());
        return HelperController::api_response_format(200, $Message, 'All messages');
    }

    /**
     * @param Request $req --> id for message that you want see it.
     * @return message was seen
     */
    public
    function SeenMessage(Request $req)
    {
        $session_id = Auth::User()->id;
        $req->validate([
            'from' => 'required|exists:users,id'
        ]);
        $messages = Message::where('From', $req->from)->where('To',$session_id)->where('seen',0)->get();
        foreach($messages as $msg){
            $msg->seen = 1;
            $msg->save();
        }
        return HelperController::api_response_format(200, $messages, 'message was seen');
    }

    public
    function ViewAllMSG_from_to(Request $req)
    {
        $req->validate([
            'id' => 'required|exists:users,id'
        ]);
        $session_id = Auth::User()->id;
        $check = Message::where('From', $req->id)->orWhere('To', $req->id)->first();
        if ($check) {
            $messages = Message::where(function ($query) use ($req, $session_id) {
                $query->where('From', $req->id)->orWhere('To', $req->id);
            })->where(function ($query) use ($session_id) {
                $query->where('From', $session_id)->orWhere('To', $session_id);
            })->get();
            $msg = MessageFromToResource::collection($messages);
            return HelperController::api_response_format(200, $msg);
        }
        return HelperController::api_response_format(200, [], 'No Messages to this user');
    }

    /**
     * @Description: add Roles to send Message Permission
     * @param: Request  to access From_Role and To_Role
     * @return : if succss -> return MSG -> 'Message Role insertion sucess'
     *          IF NOT ->   return MSG -> 'Message Role insertion Fail'
     *
     */
    public function add_send_Permission_for_role(Request $req)
    {

        $valid = Validator::make($req->all(), [
            'From_Role' => 'required | exists:roles,id',
            'To_Role' => 'required | exists:roles,id'
        ]);
        if ($valid->fails()) {
            return response()->json(['msg' => $valid->errors()], 404);
        }
        $Message_Role = Message_Role::firstOrCreate([
            'From_Role' => $req->From_Role,
            'To_Role' => $req->To_Role,
        ]);
        if ($Message_Role) {
            return HelperController::api_response_format(200, null, 'Message Role insertion sucess');

        }
        return HelperController::api_response_format(404, null, 'Message Role insertion Fail');

    }

    public function GetMyThreads(Request $request){
        $messages = Message::where('From', $request->user()->id)->orWhere('To', $request->user()->id)->orderBy('created_at','desc')->get();
        $users = Message::GetMessageDetails($messages , $request->user()->id);
        return HelperController::api_response_format(200 , $users);
    }
}
