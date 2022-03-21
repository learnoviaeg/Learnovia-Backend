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
    }
}
