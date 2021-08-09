<?php

use Illuminate\Database\Seeder;
use App\ItemType;


class ItemTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        ItemType::create([
            'name' => 'Quiz',
        ]);
        ItemType::create([
            'name' => 'Assignment',
        ]);
    }
}
