<?php

Route::get('/' , function (){
    dd(\App\CourseSegment::first()->lessons);
});