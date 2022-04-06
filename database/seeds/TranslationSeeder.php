<?php

use Illuminate\Database\Seeder;
use App\Dictionary;

class TranslationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Dictionary::firstOrCreate(['key' => 'attendance/report-perSession', 'language' => 1, 'value' => 'Per Session Report']);
        Dictionary::firstOrCreate(['key' => 'attendance/report-perSession', 'language' => 2, 'value' => 'التقرير بالحصة']);
        Dictionary::firstOrCreate(['key' => 'edit_assigned_users', 'language' => 1, 'value' => 'Edit Assign Users']);
        Dictionary::firstOrCreate(['key' => 'edit_assigned_users', 'language' => 2, 'value' => 'تعديل المستخدمين']);
        Dictionary::firstOrCreate(['key' => 'assigned_users', 'language' => 2, 'value' => 'تحديد المستخدمين']);
        Dictionary::firstOrCreate(['key' => 'assigned_users', 'language' => 1, 'value' => 'Assign Users']);

        Dictionary::firstOrCreate(['key' => 'select_month', 'language' => 1, 'value' => 'Select Month']);
        Dictionary::firstOrCreate(['key' => 'choose_month', 'language' => 1, 'value' => 'Choose Month']);
        Dictionary::firstOrCreate(['key' => 'Feb', 'language' => 1, 'value' => 'February']);
        Dictionary::firstOrCreate(['key' => 'March', 'language' => 1, 'value' => 'March']);
        Dictionary::firstOrCreate(['key' => 'April', 'language' => 1, 'value' => 'April']);

        Dictionary::firstOrCreate(['key' => 'select_month', 'language' => 2, 'value' => 'اختار شهر']);
        Dictionary::firstOrCreate(['key' => 'choose_month', 'language' => 2, 'value' => 'اختار شهر']);
        Dictionary::firstOrCreate(['key' => 'Feb', 'language' => 2, 'value' => 'شهر فبراير']);
        Dictionary::firstOrCreate(['key' => 'March', 'language' => 2, 'value' => 'شهر مارس']);
        Dictionary::firstOrCreate(['key' => 'April', 'language' => 2, 'value' => 'شهر أبريل']);
    }
}
