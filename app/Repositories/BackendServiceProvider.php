<?php

namespace App\Repositories;

use Illuminate\Support\ServiceProvider;

class BackendServiceProvider extends ServiceProvider
{

    public function register()
    {
        $this->app->bind(
            'App\Repositories\ChainRepositoryInterface',
            'App\Repositories\ChainRepository',
        );            

        $this->app->bind(
            'App\Repositories\EnrollmentRepositoryInterface',
            'App\Repositories\EnrollmentRepository'
        ); 

    }
}