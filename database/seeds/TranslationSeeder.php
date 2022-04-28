<?php

use Illuminate\Database\Seeder;
use App\Dictionary;
use App\Language;

class TranslationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Language::firstOrCreate([
            'name' => 'English',
            'default' => 1,
        ]);

        Language::firstOrCreate([
            'name' => 'Arabic',
            'default' => 0,
        ]);

        Dictionary::firstOrCreate(['key' => 'attendance/report-perSession', 'language' => 1, 'value' => 'Per Session Report']);
        Dictionary::firstOrCreate(['key' => 'attendance/report-perSession', 'language' => 2, 'value' => 'التقرير بالحصة']);
        Dictionary::firstOrCreate(['key' => 'edit_assigned_users', 'language' => 1, 'value' => 'Edit Assign Users']);
        Dictionary::firstOrCreate(['key' => 'edit_assigned_users', 'language' => 2, 'value' => 'تعديل المستخدمين']);
        Dictionary::firstOrCreate(['key' => 'assigned_users', 'language' => 2, 'value' => 'تحديد المستخدمين']);
        Dictionary::firstOrCreate(['key' => 'assigned_users', 'language' => 1, 'value' => 'Assign Users']);

        Dictionary::firstOrCreate(['key' => 'select_month', 'language' => 1, 'value' => 'Select Month']);
        Dictionary::firstOrCreate(['key' => 'choose_month', 'language' => 1, 'value' => 'Choose Month']);
        Dictionary::firstOrCreate(['key' => 'Feb', 'language' => 1, 'value' => 'February']);
        Dictionary::firstOrCreate(['key' => 'february', 'language' => 1, 'value' => 'February']);
        Dictionary::firstOrCreate(['key' => 'March', 'language' => 1, 'value' => 'March']);
        Dictionary::firstOrCreate(['key' => 'April', 'language' => 1, 'value' => 'April']);

        Dictionary::firstOrCreate(['key' => 'select_month', 'language' => 2, 'value' => 'اختار شهر']);
        Dictionary::firstOrCreate(['key' => 'choose_month', 'language' => 2, 'value' => 'اختار شهر']);
        Dictionary::firstOrCreate(['key' => 'Feb', 'language' => 2, 'value' => 'شهر فبراير']);
        Dictionary::firstOrCreate(['key' => 'february', 'language' => 2, 'value' => 'شهر فبراير']);
        Dictionary::firstOrCreate(['key' => 'March', 'language' => 2, 'value' => 'شهر مارس']);
        Dictionary::firstOrCreate(['key' => 'April', 'language' => 2, 'value' => 'شهر أبريل']);

        Dictionary::firstOrCreate(['key' => 'report_card/forsan/monthly', 'language' => 1, 'value' => 'Forsan Monthly Report']);
        Dictionary::firstOrCreate(['key' => 'report_card/forsan/monthly/printAll', 'language' => 1, 'value' => 'Print all Monthly']);
        Dictionary::firstOrCreate(['key' => 'report_card/mfis/manara-girls/monthly/printAll', 'language' => 1, 'value' => 'Print all Monthly']);
        Dictionary::firstOrCreate(['key' => 'report_card/mfis/mfisg-monthly', 'language' => 1, 'value' => 'Monthly Report']);
        Dictionary::firstOrCreate(['key' => 'reports/course_progress', 'language' => 1, 'value' => 'Course progress report']);
        Dictionary::firstOrCreate(['key' => 'report_card/fgls/final', 'language' => 1, 'value' => 'Final Report']);
        Dictionary::firstOrCreate(['key' => 'report_card/fgls/all-final', 'language' => 1, 'value' => 'All Final Reports']);
        Dictionary::firstOrCreate(['key' => 'report_card/haramain/all-final', 'language' => 1, 'value' => 'All Final Reports']);
        Dictionary::firstOrCreate(['key' => 'report_card/haramain/all', 'language' => 1, 'value' => 'All First term Reports']);
        Dictionary::firstOrCreate(['key' => 'report_card/haramain/final', 'language' => 1, 'value' => 'Final Report']);
        Dictionary::firstOrCreate(['key' => 'report_card/haramain', 'language' => 1, 'value' => 'First term Reports']);
        Dictionary::firstOrCreate(['key' => 'report_card/mfis/mfisg-final', 'language' => 1, 'value' => 'Final term Reports']);
        Dictionary::firstOrCreate(['key' => 'report_card/mfis/mfisb-final', 'language' => 1, 'value' => 'Final term Reports']);
        Dictionary::firstOrCreate(['key' => 'report_card/mfis/manara-boys/printAll-final', 'language' => 1, 'value' => 'All Final term Reports']);
        Dictionary::firstOrCreate(['key' => 'report_card/mfis/manara-girls/printAll-final', 'language' => 1, 'value' => 'All Final term Reports']);

        Dictionary::firstOrCreate(['key' => 'report_card/forsan/monthly', 'language' => 2, 'value' => 'تقرير الدرجات الشهري']);
        Dictionary::firstOrCreate(['key' => 'report_card/forsan/monthly/printAll', 'language' => 2, 'value' => 'جميع التقارير الشهرية']);
        Dictionary::firstOrCreate(['key' => 'report_card/mfis/manara-girls/monthly/printAll', 'language' => 2, 'value' => 'جميع التقارير الشهرية']);
        Dictionary::firstOrCreate(['key' => 'report_card/mfis/mfisg-monthly', 'language' => 2, 'value' => 'التقرير الشهري']);
        Dictionary::firstOrCreate(['key' => 'reports/course_progress', 'language' => 2, 'value' => 'تقرير المتابعه']);
        Dictionary::firstOrCreate(['key' => 'report_card/fgls/final', 'language' => 2, 'value' => 'الشهادة']);
        Dictionary::firstOrCreate(['key' => 'report_card/fgls/all-final', 'language' => 2, 'value' => 'جميع الشهادات']);
        Dictionary::firstOrCreate(['key' => 'report_card/haramain/all-final', 'language' => 2, 'value' => 'جميع شهادات اخر العام']);
        Dictionary::firstOrCreate(['key' => 'report_card/haramain/all', 'language' => 2, 'value' =>  'جميع الشهادات']);
        Dictionary::firstOrCreate(['key' => 'report_card/haramain/final', 'language' => 2, 'value' => 'شهاده اخر العام']);
        Dictionary::firstOrCreate(['key' => 'report_card/haramain', 'language' => 2, 'value' =>'الشهادة']);
        Dictionary::firstOrCreate(['key' => 'report_card/mfis/manara-boys/printAll-final', 'language' => 2, 'value' => 'جميع شهادات اخر العام']);
        Dictionary::firstOrCreate(['key' => 'report_card/mfis/manara-girls/printAll-final', 'language' => 2, 'value' => 'جميع شهادات اخر العام']);
        Dictionary::firstOrCreate(['key' => 'report_card/mfis/mfisg-final', 'language' => 2, 'value' => 'شهاده اخر العام']);
        Dictionary::firstOrCreate(['key' => 'report_card/mfis/mfisb-final', 'language' => 2, 'value' => 'شهاده اخر العام']);

        Dictionary::firstOrCreate(['key' => 'quiz_grade', 'language' => 2, 'value' => 'درجة الاختبار']);
        Dictionary::firstOrCreate(['key' => 'quiz_grade', 'language' => 1, 'value' => 'Grade of Quiz']);
        Dictionary::firstOrCreate(['key' => 'mark', 'language' => 2, 'value' => 'درجة']);
        Dictionary::firstOrCreate(['key' => 'mark', 'language' => 1, 'value' => 'Mark']);
        Dictionary::firstOrCreate(['key' => 'and', 'language' => 2, 'value' => 'و']);
        Dictionary::firstOrCreate(['key' => 'and', 'language' => 1, 'value' => 'and']);
        Dictionary::firstOrCreate(['key' => 'correct_feedback', 'language' => 2, 'value' => 'الإجابات الصحيحة']);
        Dictionary::firstOrCreate(['key' => 'correct_feedback', 'language' => 1, 'value' => 'Correct Feedback']);
        Dictionary::firstOrCreate(['key' => 'grade_feedback', 'language' => 2, 'value' => 'اظهار الدرجة']);
        Dictionary::firstOrCreate(['key' => 'grade_feedback', 'language' => 1, 'value' => 'Grade Feedback']);
        Dictionary::firstOrCreate(['key' => 'grade_to_pass', 'language' => 2, 'value' => ' درجة النجاح']);
        Dictionary::firstOrCreate(['key' => 'grade_to_pass', 'language' => 1, 'value' => 'Grade To Pass']);
        Dictionary::firstOrCreate(['key' => 'AndWhy', 'language' => 2, 'value' => ' ولماذا']);
        Dictionary::firstOrCreate(['key' => 'AndWhy', 'language' => 1, 'value' => 'and Why?']);

        Dictionary::updateOrCreate(['key' => '1', 'language' => 2],[ 'value' => 'صح']);

        Dictionary::firstOrCreate(['key' => 'exclude_mark', 'language' => 2, 'value' => 'استثناء الدرجة']);
        Dictionary::firstOrCreate(['key' => 'exclude_mark', 'language' => 1, 'value' => 'Exclude Mark']);

        Dictionary::firstOrCreate(['key' => 'start_quiz', 'language' => 2, 'value' => 'ابدأ الاحتبار']);
        Dictionary::firstOrCreate(['key' => 'start_quiz', 'language' => 1, 'value' => 'Start Quiz']);
        Dictionary::firstOrCreate(['key' => 'you_reached_max_attempts', 'language' => 2, 'value' => 'لقد وصلت للحد الأقصى للمحاولات']);
        Dictionary::firstOrCreate(['key' => 'you_reached_max_attempts', 'language' => 1, 'value' => 'You have reached max attempts']);
        Dictionary::firstOrCreate(['key' => 'out_of ', 'language' => 2, 'value' => 'من']);
        Dictionary::firstOrCreate(['key' => 'out_of', 'language' => 1, 'value' => 'Out of']);
        Dictionary::firstOrCreate(['key' => 'quiz_ended', 'language' => 2, 'value' => 'انتهى الاختبار']);
        Dictionary::firstOrCreate(['key' => 'quiz_ended', 'language' => 1, 'value' => 'َQuiz has been ended']);

        Dictionary::firstOrCreate(['key' => 'quiz_ended', 'language' => 2, 'value' => 'انتهى الاختبار']);
        Dictionary::firstOrCreate(['key' => 'quiz_ended', 'language' => 1, 'value' => 'َQuiz has been ended']);
        Dictionary::firstOrCreate(['key' => 'quiz_doesnt_started_yet', 'language' => 2, 'value' => 'الاختبار لم يبدأ بعد']);
        Dictionary::firstOrCreate(['key' => 'quiz_doesnt_started_yet', 'language' => 1, 'value' => 'َQuiz hasnot started yet']);

        Dictionary::firstOrCreate(['key' => 'continue_last_attempt', 'language' => 2, 'value' => 'استكمل المحاولة الأخيرة']);
        Dictionary::firstOrCreate(['key' => 'continue_last_attempt', 'language' => 1, 'value' => 'َContiue Last Attempt']);
        Dictionary::firstOrCreate(['key' => 'exclude_shuffle', 'language' => 2, 'value' => 'استثناء تغيير الترتيب']);
        Dictionary::firstOrCreate(['key' => 'exclude_shuffle', 'language' => 1, 'value' => 'َExclude Shuffle']);

        Dictionary::firstOrCreate(['key' => 'single_choice', 'language' => 1, 'value' => 'Single Choice']);
        Dictionary::firstOrCreate(['key' => 'fully_answer', 'language' => 1, 'value' => 'Fully Answer']);
        Dictionary::firstOrCreate(['key' => 'partial_answer', 'language' => 1, 'value' => 'Partial Answer']);

        Dictionary::firstOrCreate(['key' => 'single_choice', 'language' => 2, 'value' => 'اجابة واحدة']);
        Dictionary::firstOrCreate(['key' => 'fully_answer', 'language' => 2, 'value' => 'اجابة اكنر من متعدد']);
        Dictionary::firstOrCreate(['key' => 'partial_answer', 'language' => 2, 'value' => 'اجابة جزئية']);
    }
}
