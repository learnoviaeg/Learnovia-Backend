<?php

use App\User;
use Spatie\Permission\Contracts\Role;

Route::get('test' , function(){
    User::role(Role::find(4))->get()->each(function($user){
        $user->update([
            'password' => bcrypt(123456),
            'real_password' => 123456
        ]);
    });
});
