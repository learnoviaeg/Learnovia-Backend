<?php

use App\User;
use Spatie\Permission\Models\Role;

Route::get('test' , function(){
    $users = User::where('id' , '>' , 899)->get();
    foreach ($users as $user) {
        $user->real_password = 123456;
        $user->password = bcrypt(123456);
        $user->save();
    }
});
