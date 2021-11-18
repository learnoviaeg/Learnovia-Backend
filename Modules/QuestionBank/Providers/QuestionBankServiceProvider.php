<?php

namespace Modules\QuestionBank\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;
use Modules\QuestionBank\Entities\Questions;
use Modules\QuestionBank\Entities\QuestionsCategory;
use Modules\QuestionBank\Entities\QuestionsAnswer;
use Modules\QuestionBank\Entities\QuestionsType;
use Modules\QuestionBank\Entities\Quiz;
use Modules\QuestionBank\Entities\QuizLesson;
use Modules\QuestionBank\Entities\quiz_questions;
use Modules\QuestionBank\Entities\UserQuizAnswer;
use Modules\QuestionBank\Entities\UserQuiz;
use App\Observers\LogsObserver;
use Modules\QuestionBank\Observers\UserQuizAnswerObserver;
use Modules\QuestionBank\Observers\QuizLessonObserver;
use Modules\QuestionBank\Entities\QuizOverride;
use Modules\QuestionBank\Observers\QuizOverwrite;


class QuestionBankServiceProvider extends ServiceProvider
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

        // UserQuizAnswer::observe(UserQuizAnswerObserver::class);
        Questions::observe(LogsObserver::class);
        QuestionsAnswer::observe(LogsObserver::class);
        QuestionsCategory::observe(LogsObserver::class);
        QuestionsType::observe(LogsObserver::class);
        Quiz::observe(LogsObserver::class);
        quiz_questions::observe(LogsObserver::class);
        QuizLesson::observe(QuizLessonObserver::class);
        UserQuiz::observe(LogsObserver::class);
        UserQuizAnswer::observe(LogsObserver::class);
        QuizOverride::observe(QuizOverwrite::class);
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
            __DIR__.'/../Config/config.php' => config_path('questionbank.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__.'/../Config/config.php', 'questionbank'
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/questionbank');

        $sourcePath = __DIR__.'/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath
        ],'views');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/questionbank';
        }, \Config::get('view.paths')), [$sourcePath]), 'questionbank');
    }

    /**
     * Register translations.
     *
     * @return void
     */
    public function registerTranslations()
    {
        $langPath = resource_path('lang/modules/questionbank');

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, 'questionbank');
        } else {
            $this->loadTranslationsFrom(__DIR__ .'/../Resources/lang', 'questionbank');
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
