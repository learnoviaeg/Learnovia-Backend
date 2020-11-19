<?php

namespace Modules\Attendance\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;
use Modules\Attendance\Entities\AttendanceLog;
use Modules\Attendance\Entities\AttendanceSession;
use App\Observers\LogsObserver;

class AttendanceServiceProvider extends ServiceProvider
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
        $this->loadMigrationsFrom(module_path('Attendance', 'Database/Migrations'));

        // AttendanceLog::observe(LogsObserver::class);
        // AttendanceSession::observe(LogsObserver::class);
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
            module_path('Attendance', 'Config/config.php') => config_path('attendance.php'),
        ], 'config');
        $this->mergeConfigFrom(
            module_path('Attendance', 'Config/config.php'), 'attendance'
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/attendance');

        $sourcePath = module_path('Attendance', 'Resources/views');

        $this->publishes([
            $sourcePath => $viewPath
        ],'views');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/attendance';
        }, \Config::get('view.paths')), [$sourcePath]), 'attendance');
    }

    /**
     * Register translations.
     *
     * @return void
     */
    public function registerTranslations()
    {
        $langPath = resource_path('lang/modules/attendance');

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, 'attendance');
        } else {
            $this->loadTranslationsFrom(module_path('Attendance', 'Resources/lang'), 'attendance');
        }
    }

    /**
     * Register an additional directory of factories.
     *
     * @return void
     */
    public function registerFactories()
    {
        if (! app()->environment('production') && $this->app->runningInConsole()) {
            app(Factory::class)->load(module_path('Attendance', 'Database/factories'));
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
