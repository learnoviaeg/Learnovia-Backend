<?php

namespace App\Providers;

use App\Enroll;
use App\Observers\EnrollObserver;
use App\GradeItems;
use App\Observers\GradeItemObserver;
use App\Observers\UserGradeObserver;
use App\Observers\AssignmentLessonObserver;
use App\Observers\QuizLessonObserver;
use App\UserGrade;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Modules\Assigments\Entities\AssignmentLesson;
use Modules\QuestionBank\Entities\QuizLesson;
use App\Repositories\BackendServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->register(BackendServiceProvider::class);
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

        // UserGrade::observe(UserGradeObserver::class);
        Enroll::observe(EnrollObserver::class);
        GradeItems::observe(GradeItemObserver::class);
        AssignmentLesson::observe(AssignmentLessonObserver::class);
        QuizLesson::observe(QuizLessonObserver::class);
        
    }
}
