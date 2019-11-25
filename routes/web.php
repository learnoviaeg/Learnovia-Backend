<?php

use App\GradeItems;

Route::get('test' , function(){
    $grade = GradeItems::where('grade_category' , 3)->get();
    foreach ($grade as $item) {
        echo $item->id .' => ' .$item->weight() . '<br>';
    }
});
