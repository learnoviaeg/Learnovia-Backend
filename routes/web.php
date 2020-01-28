<?php

use App\User;
use Spatie\Permission\Models\Role;

Route::get('test' , function(){
    return User::where('id' , '>' , 889)->get();
});
