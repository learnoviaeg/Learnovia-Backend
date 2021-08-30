<?php

use Illuminate\Database\Seeder;
use App\scale;


class ScaleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $formateScale = [
            ['name' => 'Fair','grade' => 0 ],
            ['name' => 'Good','grade' => 1],
            ['name' => 'Very Good','grade' => 2],
            ['name' => 'Excellent','grade' => 3]
        ];
        scale::create([
           'name' => 'Default Scale',
           'formate' => serialize($formateScale),
        ]);
    }
}
