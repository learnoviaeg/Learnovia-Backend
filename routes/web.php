<?php

use App\GradeCategory;

Route::get('test' , function(){
$gc = GradeCategory::find(2);
return $gc->GradeItems[0]->weight();
});
