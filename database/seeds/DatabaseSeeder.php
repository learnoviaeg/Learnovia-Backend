<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(ContractSeeder::class);
        $this->call(PermissionSeeder::class);
        $this->call(RoleSeeder::class);
        $this->call(UserSeeder::class);
        $this->call(LanguageSeeder::class);
        $this->call(ItemTypeSeeder::class);
        $this->call(LetterSeeder::class);
        $this->call(ScaleSeeder::class);
        $this->call(SettingsSeeder::class);
       // $this->call(SeedLearnoviaDB::class);

    }
}
