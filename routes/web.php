<?php

use App\User;
use Spatie\Permission\Models\Role;

Route::get('test' , function(){
    User::where('id' , '>' , '889')->get()->each(function($user){
        $user->updated([
            'password' => bcrypt(123456),
            'real_password' => 123456
        ]
        );
    });
});
