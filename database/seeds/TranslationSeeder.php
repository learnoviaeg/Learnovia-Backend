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
    }
}
