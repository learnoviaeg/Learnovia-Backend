<?php

namespace App\Http\Controllers;

use App\Contacts;
use Illuminate\Http\Request;
use File;
use Validator;
use App\Http\Resources\Messageresource;
use App\Message;
use Illuminate\Support\Facades\Storage;

class MessageController extends Controller
{
        /* please before  excute this fun  run  php artisan Storage:link */
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
            return response()->json(['msg' => $valid->errors()], 404);
        }

        foreach ($req->users as $userId) {
            Message::Create(array(
                'text' => $req->text,
                'about' => ($req->about == null) ? $req->From : $req->about, /*replace  $req->From  to $session_id when you session  */
                'From' => $req->From, /*Comment this Line please when you use session*/
                // 'From' => $session_id, /* unComment please when you use session*/
                'seen' => false,
                'deleted' => false,
                'To' => $userId,
                'file' => $req->file->getClientOriginalName(),
            ));
        }
        Storage::disk('public')->put(
            $req->file->getClientOriginalName(),
            $req->file
        );
        return response()->json([
            'message' => 'Successfully Sent Message!'
        ], 201);
    }

    public function deleteMessage(Request $req)
    {
        $valid = Validator::make($req->all(), [
            'id' => 'required' | 'exists:messages,id',

        ]);
        if ($valid->fails()) {
            return response()->json(['msg' => $valid->errors()], 404);
        }

        $message = Message::find($req->id);

        if ($message) {
            $message->update(array(
                'deleted' => true
            ));
            $message->save();
            return $this->List_All_Message();
        }
    }

    public function List_All_Message()
    {
        $Message = Messageresource::collection(Message::get());
        return ($Message);
    }

    public function SeenMessage(Request $req)
    {
        $valid = Validator::make($req->all(), [
            'id' => 'required' | 'exists:messages,id',

        ]);
        if ($valid->fails()) {
            return response()->json(['msg' => $valid->errors()], 404);
        }

        $message = Message::find($req->id);

        if ($message) {
            $message->update(array(
                'seen' => true
            ));
            $message->save();
            return $this->List_All_Message();
        }
    }




}
