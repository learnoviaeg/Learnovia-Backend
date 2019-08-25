<?php

use App\CourseSegment;

Route::get('/' , function(){
    return CourseSegment::GetWithClassAndCourse(10 , 9);
});
