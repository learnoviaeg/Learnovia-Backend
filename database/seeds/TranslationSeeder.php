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
        Dictionary::firstOrCreate(['key' => 'edit_restrection', 'language' => 1, 'value' => 'Edit Restriction']);
        Dictionary::firstOrCreate(['key' => 'edit_restrection', 'language' => 2, 'value' => 'تعديل المستخدمين']);
        Dictionary::firstOrCreate(['key' => 'restrict_users', 'language' => 2, 'value' => 'تحديد المستخدمين']);
        Dictionary::firstOrCreate(['key' => 'restrict_users', 'language' => 1, 'value' => 'Restrict Users']);
    }
}
