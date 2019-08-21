<?php

use App\User;

Route::get('/' , function(){
    dd(User::all());
});
