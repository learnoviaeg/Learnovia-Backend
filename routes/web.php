<?php

use App\User;
use Spatie\Permission\Models\Role;

Route::get('test' , function(){
    User::role('Teacher')->get()->each(function($user){
        $user->update([
            'password' => bcrypt(123456),
            'real_password' => 123456
        ]);
    });
});
