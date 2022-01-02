<?php

use Illuminate\Database\Seeder;
use App\Language;


class LanguageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Language::create([
            'name' => 'English',
            'default' => 1,
        ]);

        Language::create([
            'name' => 'Arabic',
            'default' => 0,
        ]);
    }
}
