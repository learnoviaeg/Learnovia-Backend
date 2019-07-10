<?php

namespace App\Http\Controllers;

use App\Contacts;
use Illuminate\Http\Request;
use File;
use Validator;
use App\Http\Resources\Messageresource;
use App\Http\Resources\MessageFromToResource;
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
        //$session_id = session()->getId() /* unComment please when you use session*/

        $valid = Validator::make($req->all(), [
            'text' => 'required',
            'about' => 'exists:users,id',
            'From' => 'exists:users,id',
            'users' => 'required|array',
            'users.*' => 'required|integer|exists:users,id',
            'file' => 'file|mimes:pdf,docx,doc,xls,xlsx,ppt,pptx,zip,rar,JPG,jpg,jpeg,png,PNG',
        ]);
        if ($valid->fails()) {
            return HelperController::api_response_format(404, null, $valid->errors());
        }

        foreach ($req->users as $userId) {
            // if ($session_id != $userId ) if used session
            if ($req->From != $userId) {
                Message::Create(array(
                    'text' => $req->text,
                    'about' => ($req->about == null) ? $req->From : $req->about, /*replace  $req->From  to $session_id when you session  */
                    'From' => $req->From, /*Comment this Line please when you use session*/
                    // 'From' => $session_id, /* unComment please when you use session*/
                    'seen' => false,
                    'deleted' => 0,
                    'To' => $userId,
                    'file' => $req->file->getClientOriginalName(),
                ));
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
        $valid = Validator::make($req->all(), [
            'id' => 'required|exists:messages,id',
        ]);
        if ($valid->fails()) {
            return response()->json(['msg' => $valid->errors()], 404);
        }

        $message = Message::find($req->id);

        if ($message) {
            $message->update(array(
                'deleted' => 1
            ));
            $message->save();

            return HelperController::api_response_format(201, null, 'message was deleted');

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
        //$session_id = session()->getId() /* unComment please when you use session*/

        $valid = Validator::make($req->all(), [
            'id' => 'required|exists:messages,id',
            'my_id' => 'required|exists:users,id',

        ]);
        if ($valid->fails()) {
            return response()->json(['msg' => $valid->errors()], 404);
        }

        $message = Message::find($req->id);
        // if ($session_id== $message->To){ /* unComment please when you use session*/
        if ($req->my_id == $message->To) {
            if ($message) {
                if ($message->deleted == 3) {
                    $message->update(array(
                        'deleted' => 1
                    ));
                    // elseif ($session_id== $message->From){ /* unComment please when you use session*/

                } else {
                    $message->update(array(
                        'deleted' => 2
                    ));
                }
            }
        } elseif ($req->my_id == $message->From) {

            if ($message) {
                if ($message->deleted == 2) {
                    $message->update(array(
                        'deleted' => 1
                    ));
                } else {
                    $message->update(array(
                        'deleted' => 3
                    ));
                }
            }
        }
        $message->save();
        return HelperController::api_response_format(201, null, 'message was deleted');
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
        $valid = Validator::make($req->all(), [
            'id' => 'required | exists:messages,id',
        ]);
        if ($valid->fails()) {
            return response()->json(['msg' => $valid->errors()], 404);
        }
        $message = Message::find($req->id);
        if ($message) {
            $message->update(array('seen' => true));
            $message->save();
            return HelperController::api_response_format(201, null, 'message was seen');
        }
    }

    public function ViewAllMSG_from_to(Request $req)
    {
        $req->validate([
            'From' => 'required|integer|exists:messages,From',
            'To' => 'required|integer|exists:messages,To',
            'my_id' => 'required|exists:users,id',
        ]);
        $msg = array();
        $messages = DB::table('messages')->where('From', $req->From)->where('To', $req->To)->get();
        foreach ($messages as $message) {
            if ($message->deleted == 0) {
                array_push($msg, new MessageFromToResource ($message));
            } elseif ($message->deleted == 1) {
                $message->text = "this message was Deleted";
                array_push($msg, new MessageFromToResource ($message));
            } elseif ($message->deleted == 2 && $req->my_id == $message->To) {
                $message->text = "this message was Deleted";
                array_push($msg, new MessageFromToResource ($message));
            } elseif ($message->deleted == 2 && $req->my_id == $message->From) {
                array_push($msg, new MessageFromToResource ($message));
            } elseif ($message->deleted == 3 && $req->my_id == $message->From) {
                $message->text = "this message was Deleted";
                array_push($msg, new MessageFromToResource ($message));

            } elseif ($message->deleted == 3 && $req->my_id == $message->To) {
                array_push($msg, new MessageFromToResource ($message));
            }
        }
        return HelperController::api_response_format(201, $msg);

    }
}
