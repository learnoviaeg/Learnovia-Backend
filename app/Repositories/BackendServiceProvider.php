<?php

namespace App\Repositories;

use Illuminate\Support\ServiceProvider;

class BackendServiceProvider extends ServiceProvider
{

    public function register()
    {
        $this->app->bind(
            'App\Repositories\ChainRepositoryInterface',
            'App\Repositories\ChainRepository'
        );            

        $this->app->bind(
            'App\Repositories\EnrollmentRepositoryInterface',
            'App\Repositories\EnrollmentRepository'
        ); 

        $this->app->bind(
            'App\Repositories\RepportsRepositoryInterface',
            'App\Repositories\RepportsRepository'
        );

        $this->app->bind(
            'App\Repositories\SettingsReposiotryInterface',
            'App\Repositories\SettingsReposiotry'
        ); 

        $this->app->bind(
            'App\Repositories\NotificationRepoInterface',
            'App\Repositories\NotificationRepo'
        ); 
    }
}