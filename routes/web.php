<?php

use App\CourseSegment;
use App\Lesson;
use App\User;
use Spatie\Permission\Models\Role;

Route::get('test', function () {
    $c = CourseSegment::get();
    foreach ($c as $x) {
        for ($i = 0; $i < 4; $i++) {
            $x->lessons()->firstOrCreate([
                'name' => 'Lesson ' . $i,
                'index' => $i,
            ]);
        }
    }
});
