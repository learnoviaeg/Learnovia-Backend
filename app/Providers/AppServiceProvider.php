<?php

namespace App\Providers;

use App\Observers\UserGradeObserver;
use App\UserGrade;
use App\Observers\AssignmentObserver;
use App\Observers\FileObserver;
use App\Observers\MediaObserver;
use App\Observers\PageObserver;
use App\Observers\QuizObserver;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Modules\Assigments\Entities\AssignmentLesson;
use Modules\Page\Entities\pageLesson;
use Modules\QuestionBank\Entities\QuizLesson;
use Modules\UploadFiles\Entities\FileLesson;
use Modules\UploadFiles\Entities\MediaLesson;
use Nwidart\Modules\Collection;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {

    }

    public function boot()
    {
        Schema::defaultStringLength(191);
        Collection::macro('paginate', function ($perPage, $total = null, $page = null, $pageName = 'page') {
            $page = $page ?: LengthAwarePaginator::resolveCurrentPage($pageName);
            return new LengthAwarePaginator(
                array_values($this->forPage($page, $perPage)->toArray()),
                $total ?: $this->count(),
                $perPage,
                $page,
                [
                    'path' => LengthAwarePaginator::resolveCurrentPath(),
                    'pageName' => $pageName,
                ]
            );
        });

        QuizLesson::observe(QuizObserver::class);
        AssignmentLesson::observe(AssignmentObserver::class);
        PageLesson::observe(PageObserver::class);
        MediaLesson::observe(MediaObserver::class);
        FileLesson::observe(FileObserver::class);
        UserGrade::observe(UserGradeObserver::class);
    }
}
