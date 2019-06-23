<?php
/**
 * Created by PhpStorm.
 * User: Huda
 * Date: 6/23/2019
 * Time: 9:51 AM
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use Validator;
use File;



class UserController extends Controller

{


    public function insert_users()
    {
        file_get_contents(__DIR__ . '/data/users.json');
        $json = File::get(__DIR__ . '/data/users.json');
        $data = \GuzzleHttp\json_decode($json);
        foreach ($data as $obj) {
            User::Create(array(
                'id' => $obj->id,
                'email' => $obj->email,
                'password' => bcrypt($obj->password),
                'name' => $obj->name
            ));
        }
        return response()->json([
            'message' => 'Successfully created user!'
        ], 201);

    }

}