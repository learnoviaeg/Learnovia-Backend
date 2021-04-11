<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use App\Contacts;
use Validator;
use App\Http\Resources\Contactresource;
use Auth;

class ContactController extends Controller

{
    /**
     *
     * @Description : add Contact
     * @param : Request to Access his id ("Person_id") and ("Friend_id")
     * @return : if addition succeeded ->  return MSG : 'Contact insertion sucess'
     *           if not -> return MSG: 'Contact insertion Fail'
     *
    ``
     */
    public function addContact(Request $req)
    {
        $session_id = Auth::User()->id;

        $valid = Validator::make($req->all(), [
            'Friend_id' => 'required | exists:users,id',
        ]);
        if ($valid->fails()) {
            return HelperController::api_response_format(200, $valid->errors());
        }
        if ($req->Friend_id != $req->Person_id) {
            $contacts = Contacts::firstOrCreate([
                'Friend_id' => $req->Friend_id,
                'Person_id' => $session_id,
            ]);
            if ($contacts)
                return HelperController::api_response_format(200, null, 'Contact insertion sucess');
        }

        return HelperController::api_response_format(200, null, 'Contact insertion Fail');

//        return response()->json(['msg' => 'Contact insertion Fail'], 404);

    }

    /*
     * @Description: Get All MY Friends
     * @param: no take parameter
     * @return: List of my friends
     *
     * */
    public function ViewMyContact(Request $request)
    {
        $session_id = Auth::User()->id;
        $user = User::whereId($session_id)->with(['contacts' => function($query)use ($request){
            if($request->filled('search'))
                $query->where('firstname', 'like', '%'.$request->search.'%')->orwhere('lastname', 'like', '%'.$request->search.'%');
        }])->first();
        $contacts = $user->contacts;
        return HelperController::api_response_format(200, Contactresource::Collection($contacts), 'My Contact ');
    }
}
