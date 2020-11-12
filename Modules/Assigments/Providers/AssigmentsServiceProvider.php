<?php

namespace Modules\Assigments\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;
use Modules\Assigments\Entities\AssignmentLesson;
use Modules\Assigments\Entities\Assignment;
use Modules\Assigments\Entities\UserAssigment;
use Modules\Assigments\Observers\AssignmentLessonObserver;
use App\Observers\LogsObserver;
use Modules\Assigments\Entities\assignmentOverride;
use Modules\Assigments\Observers\AssignmentOverwrite;

class AssigmentsServiceProvider extends ServiceProvider
{
    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->registerFactories();
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');

        Assignment::observe(LogsObserver::class);
        UserAssigment::observe(LogsObserver::class);
        AssignmentLesson::observe(AssignmentLessonObserver::class);
        assignmentOverride::observe(AssignmentOverwrite::class);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->register(RouteServiceProvider::class);
    }

    /**
     * Register config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->publishes([
            __DIR__.'/../Config/config.php' => config_path('assigments.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__.'/../Config/config.php', 'assigments'
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/assigments');

        $sourcePath = __DIR__.'/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath
        ],'views');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/assigments';
        }, \Config::get('view.paths')), [$sourcePath]), 'assigments');
    }

    /**
     * Register translations.
     *
     * @return void
     */
    public function registerTranslations()
    {
        $langPath = resource_path('lang/modules/assigments');

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, 'assigments');
        } else {
            $this->loadTranslationsFrom(__DIR__ .'/../Resources/lang', 'assigments');
        }
    }

    /**
     * Register an additional directory of factories.
     *
     * @return void
     */
    public function registerFactories()
    {
        if (! app()->environment('production')) {
            app(Factory::class)->load(__DIR__ . '/../Database/factories');
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }
}
