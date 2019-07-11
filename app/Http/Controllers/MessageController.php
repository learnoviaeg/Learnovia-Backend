<?php

namespace App\Http\Controllers;

use App\Contacts;
use Illuminate\Http\Request;
use File;
use Validator;
use Session;
use App\Http\Resources\Messageresource;
use App\Http\Resources\MessageFromToResource;
use App\Message_Role;
use Illuminate\Support\Facades\Auth;
use App\Message;
use DB;
use Illuminate\Support\Facades\Storage;

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
        $session_id = Auth::User()->id;
        $valid = Validator::make($req->all(), [
            'text' => 'required',
            'about' => 'exists:users,id',
            'users' => 'required|array',
            'users.*' => 'required|integer|exists:users,id',
            'file' => 'file|mimes:pdf,docx,doc,xls,xlsx,ppt,pptx,zip,rar,JPG,jpg,jpeg,png,PNG',
        ]);
        if ($valid->fails()) {
            return HelperController::api_response_format(404, null, $valid->errors());
        }
        foreach ($req->users as $userId) {
            if ($session_id != $userId) {
                $to_Role_id = DB::table('model_has_roles')->where('model_id', $userId)->first();
                $From_Role_id = DB::table('model_has_roles')->where('model_id', $session_id)->first();
                $permission = Message_Role::where('From_Role', $From_Role_id->role_id)->where('To_Role', $to_Role_id->role_id)->first();
                if ($permission) {
                    Message::Create(array(
                        'text' => $req->text,
                        'about' => ($req->about == null) ? $req->From : $req->about, /*replace  $req->From  to $session_id when you session  */
                        'From' => $session_id,
                        'seen' => false,
                        'deleted' => 0,
                        'To' => $userId,
                        'file' => $req->file->getClientOriginalName(),
                    ));
                } else {
                    return HelperController::api_response_format(404, null, 'Fail ,  you do not have a permission to send message to this user!');

                }
            } else {
                return HelperController::api_response_format(404, null, 'Fail , you can not send message for yourself!');


                /*   return response()->json([
                       'message' => 'Fail , you can not send message for yourself!'
                   ], 404);*/
            }
        }
        Storage::disk('public')->put(
            $req->file->getClientOriginalName(),
            $req->file
        );
        return HelperController::api_response_format(201, null, 'Successfully Sent Message!');


    }

    /**
     * @description:  delete message for all
     * @param Request $req => id of message that you want to delete
     * @return deleted if message was deleted  for all
     */

    public function deleteMessageForAll(Request $req)
    {
        $session_id = Auth::User()->id;
        $valid = Validator::make($req->all(), [
            'id' => 'required|exists:messages,id',
        ]);
        if ($valid->fails()) {
            return HelperController::api_response_format(404, null,  $valid->errors());
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
    public function deleteMessageforMe(Request $req)
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
    public function List_All_Message()
    {
        $Message = Messageresource::collection(Message::get());
        return HelperController::api_response_format(201, $Message, 'All messages');

    }

    /**
     * @param Request $req --> id for message that you want see it.
     * @return message was seen
     */
    public function SeenMessage(Request $req)
    {
        $session_id = Auth::User()->id;

        $valid = Validator::make($req->all(), [
            'id' => 'required | exists:messages,id',
        ]);
        if ($valid->fails()) {
            return response()->json(['msg' => $valid->errors()], 404);
        }
        $message = Message::find($req->id);
        if ($message) {
            if ($message->To == $session_id) {
                $message->update(array('seen' => true));
                $message->save();
                return HelperController::api_response_format(201, null, 'message was seen');
            } else {
                return HelperController::api_response_format(404, null, 'You do not have permission Seen this message');
            }
        }
    }

    public function ViewAllMSG_from_to(Request $req)
    {
        $session_id = Auth::User()->id;
        $check = Message::where('From',$req->id)->orWhere('To',$req->id)->first();
        if($check) {
            $req->validate([
                'id' => 'required|integer',
            ]);
            $msg = array();

            $messages = Message::where(function ($query) use ($req, $session_id) {
                $query->where('From', $req->id)->orWhere('To', $req->id);
            })->where(function ($query) use ($session_id) {
                $query->where('From', $session_id)->orWhere('To', $session_id);
            })->get();


            foreach ($messages as $message) {
                if ($message->deleted == 0) {
                    array_push($msg, new MessageFromToResource ($message));
                } elseif ($message->deleted == Message::$DELETE_FROM_ALL) {
                    $message->text = "this message was Deleted for All";
                    array_push($msg, new MessageFromToResource ($message));
                } elseif ($message->deleted == Message::$DELETE_FOR_RECEIVER && $session_id == $message->To) {
                    $message->text = "this message was Deleted";
                    array_push($msg, new MessageFromToResource ($message));
                } elseif ($message->deleted == Message::$DELETE_FOR_RECEIVER && $session_id == $message->From) {
                    array_push($msg, new MessageFromToResource ($message));
                } elseif ($message->deleted == Message::$DELETE_FOR_SENDER && $session_id == $message->From) {
                    $message->text = "this message was Deleted";
                    array_push($msg, new MessageFromToResource ($message));

                } elseif ($message->deleted == Message::$DELETE_FOR_SENDER && $session_id == $message->To) {
                    array_push($msg, new MessageFromToResource ($message));
                }
            }
            return HelperController::api_response_format(201, $msg);
        }
    }
}
