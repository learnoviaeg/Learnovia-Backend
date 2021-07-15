<?php

namespace App\Grader;

use Illuminate\Support\ServiceProvider;

class GraderServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(
            'App\Grader\gradingMethodsInterface','App\Grader\gradingMethods'
        );  
    }
}