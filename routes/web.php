<?php

use App\User;
use Spatie\Permission\Models\Role;

Route::get('test' , function(){
    return Role::find(4);
    User::role('Teacher')->get()->each(function($user){
        $user->update([
            'password' => bcrypt(123456),
            'real_password' => 123456
        ]);
    });
});
