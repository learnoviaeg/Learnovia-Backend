<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use App\Repositories\BackendServiceProvider;
use App\Enroll;
use App\Observers\EnrollObserver;
use App\GradeItems;
use App\Observers\GradeItemObserver;
use App\UserGrade;
use App\Observers\UserGradeObserver;

use Modules\Assigments\Entities\Assignment;
use Modules\Assigments\Entities\AssignmentLesson;
use App\Observers\AssignmentLessonObserver;
use Modules\QuestionBank\Entities\Questions;
use Modules\QuestionBank\Entities\QuestionsCategory;
use Modules\QuestionBank\Entities\QuestionsAnswer;
use Modules\QuestionBank\Entities\QuestionsType;
use Modules\QuestionBank\Entities\Quiz;
use Modules\QuestionBank\Entities\QuizLesson;
use Modules\QuestionBank\Entities\UserQuizAnswer;
use Modules\QuestionBank\Entities\UserQuiz;
use App\Observers\QuizLessonObserver;
use Modules\UploadFiles\Entities\File;
use Modules\UploadFiles\Entities\FileLesson;
use Modules\UploadFiles\Entities\Media;
use Modules\UploadFiles\Entities\MediaLesson;
use Modules\Page\Entities\Page;
use Modules\Page\Entities\PageLesson;
use App\h5pLesson;
use App\Observers\LogsObserver;

use App\User;
use App\AcademicType;
use App\Announcement;
use App\AcademicYear;
use App\classes;
use App\Course;
use App\Level;
use App\Segment;
use App\YearLevel;
use App\ClassLevel;
use App\AcademicYearType;
use App\CourseSegment;

use App\Timeline;
use Modules\Bigbluebutton\Entities\BigbluebuttonModel;
use Modules\Attendance\Entities\AttendanceLog;
use Modules\Attendance\Entities\AttendanceSession;

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

        Assignment::observe(LogsObserver::class);
        AssignmentLesson::observe(AssignmentLessonObserver::class);

        Questions::observe(LogsObserver::class);
        QuestionsAnswer::observe(LogsObserver::class);
        QuestionsCategory::observe(LogsObserver::class);
        QuestionsType::observe(LogsObserver::class);
        Quiz::observe(LogsObserver::class);
        QuizLesson::observe(QuizLessonObserver::class);
        UserQuiz::observe(LogsObserver::class);
        UserQuizAnswer::observe(LogsObserver::class);

        File::observe(LogsObserver::class);
        FileLesson::observe(LogsObserver::class);

        Media::observe(LogsObserver::class);
        MediaLesson::observe(LogsObserver::class);

        Page::observe(LogsObserver::class);
        PageLesson::observe(LogsObserver::class);
        h5pLesson::observe(LogsObserver::class);

        AcademicType::observe(LogsObserver::class);
        AcademicYear::observe(LogsObserver::class);
        classes::observe(LogsObserver::class);
        Course::observe(LogsObserver::class);
        Level::observe(LogsObserver::class);
        Segment::observe(LogsObserver::class);
        AcademicYearType::observe(LogsObserver::class);
        ClassLevel::observe(LogsObserver::class);
        YearLevel::observe(LogsObserver::class);
        User::observe(LogsObserver::class);
        CourseSegment::observe(LogsObserver::class);
        Enroll::observe(EnrollObserver::class);

        // UserGrade::observe(UserGradeObserver::class);
        GradeItems::observe(GradeItemObserver::class);
        BigbluebuttonModel::observe(LogsObserver::class);
        Announcement::observe(LogsObserver::class);
        Timeline::observe(LogsObserver::class);
        AttendanceLog::observe(LogsObserver::class);
        AttendanceSession::observe(LogsObserver::class);
    }
}
