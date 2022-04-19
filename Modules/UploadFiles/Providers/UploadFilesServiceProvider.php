<?php

namespace Modules\UploadFiles\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;
use Modules\UploadFiles\Entities\FileLesson;
use Modules\UploadFiles\Entities\File;
use Modules\UploadFiles\Entities\Media;
use Modules\UploadFiles\Entities\MediaLesson;
use Modules\UploadFiles\Observers\FileObserver;
use Modules\UploadFiles\Observers\MediaObserver;
use Modules\UploadFiles\Observers\FileLessonObserver;
use Modules\UploadFiles\Observers\MediaLessonObserver;
use App\Observers\LogsObserver;

class UploadFilesServiceProvider extends ServiceProvider
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

        Media::observe(MediaObserver::class);
        File::observe(FileObserver::class);

        File::observe(LogsObserver::class);
        FileLesson::observe(LogsObserver::class);
        
        Media::observe(LogsObserver::class);
        MediaLesson::observe(LogsObserver::class);

        //FileLesson::observe(FileLessonObserver::class);
        //MediaLesson::observe(MediaLessonObserver::class);
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
            __DIR__.'/../Config/config.php' => config_path('uploadfiles.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__.'/../Config/config.php', 'uploadfiles'
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/uploadfiles');

        $sourcePath = __DIR__.'/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath
        ],'views');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/uploadfiles';
        }, \Config::get('view.paths')), [$sourcePath]), 'uploadfiles');
    }

    /**
     * Register translations.
     *
     * @return void
     */
    public function registerTranslations()
    {
        $langPath = resource_path('lang/modules/uploadfiles');

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, 'uploadfiles');
        } else {
            $this->loadTranslationsFrom(__DIR__ .'/../Resources/lang', 'uploadfiles');
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
