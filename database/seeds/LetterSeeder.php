<?php

use Illuminate\Database\Seeder;
use App\Letter;


class LetterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $formateLetter = [
            ['name' => 'A+','boundary' => 96],
            ['name' => 'A','boundary' => 93],
            ['name' => 'A-','boundary' => 89],
            ['name' => 'B+','boundary' => 86],
            ['name' => 'B','boundary' => 83],
            ['name' => 'B-','boundary' => 79],
            ['name' => 'C+','boundary' => 76],
            ['name' => 'C','boundary' => 73],
            ['name' => 'C-','boundary' => 69],
            ['name' => 'D+','boundary' => 66],
            ['name' => 'D','boundary' => 63],
            ['name' => 'D-','boundary' => 60],
            ['name' => 'F','boundary' => 0 ]
        ];
        
        Letter::create([
            'name' => 'Default Letter',
            'formate' => serialize($formateLetter),
         ]);
    }
}
