<?php
/**
 * Created by PhpStorm.
 * User: Huda
 * Date: 6/23/2019
 * Time: 9:51 AM
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Resources\AdminReasource;
use App\Http\Resources\UserReasources;
use App\User;
use Validator;
use File;



class UserController extends Controller

{

    public function insert_users()
    {
        file_get_contents(__DIR__ . '/data/users.json');
        $json = File::get(__DIR__ . '/data/users.json');
        $data = \GuzzleHttp\json_decode($json, true);
        //print_r($data);

        foreach ($data as $obj) {
            $valid = Validator::make($obj, [
                'name' => 'required',
                'email' => 'required',
                'password' => 'required'
            ]);
            if ($valid->fails()) {
                return response()->json([
                    'message' => $valid->errors()->all()
                ], 404);
            }
            User::create([
                'id' => $obj['id'],
                'name' => $obj['name'],
                'email' => $obj['email'],
                'password' => bcrypt($obj['password']),
                'Realpassword' => $obj['password']
            ]);
        }

        return response()->json([
            'message' => 'Successfully created user!'
        ], 201);

    }
    public function showRealPass()
    {
        $users = AdminReasource::collection(User::all());
        return response($users, 200);
    }

    public function getAllUser(){
        $request=request()->user()->hasPermissionTo('Show RealPassword');
        if($request){
            return $this->showRealPass();
        }
        $users = UserReasources::collection(User::all());
        return response($users, 200);
    }
}