<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Contacts;
use Validator;
use App\Http\Resources\Contactresource;


class ContactController extends Controller
{
    public function addContact(Request $req)
    {
        /*$session_id = session()->getId()*//*when you used session please   uncomment this Line*/

        $valid = Validator::make($req->all(), [
            'Friend_id' => 'required | exists:users,id',
            'Person_id' => 'exists:users,id' ///*when you used session please  comment this Line
        ]);
        if ($valid->fails()) {
            return response()->json(['msg' => $valid->errors()], 404);
        }
        if ($req->Friend_id != $req->Person_id) {
            $contacts = Contacts::firstOrCreate([
                'Friend_id' => $req->Friend_id,
                'Person_id' => $req->Person_id,/*when you used session please  comment this Line*/
                // 'Person_id' =>$session_id /*when you used session please   uncomment this Line*/
            ]);
            if ($contacts) {
                return response()->json(['msg' => 'Contact insertion sucess'], 200);
            }
        }


        return response()->json(['msg' => 'Contact insertion Fail'], 404);

    }

    public function ViewMyContact(Request $req)
    {
        /*$session_id = session()->getId()*//*when you used session please   uncomment this Line*/

        $valid = Validator::make($req->all(), [
            'Person_id' => 'required|exists:users,id' ///*when you used session please  comment this Line
        ]);
        if ($valid->fails()) {
            return response()->json(['msg' => $valid->errors()], 404);
        }
        $contacts = Contacts::wherePerson_id($req->Person_id)->get();
        return Contactresource::collection($contacts);
    }


}
