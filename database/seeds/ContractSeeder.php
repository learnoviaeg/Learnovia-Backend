<?php

use Illuminate\Database\Seeder;
use App\Contract;
use Carbon\Carbon;



class ContractSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Contract::create(['attachment_id' => null, 'start_date' => Carbon::now(), 'end_date' => Carbon::now()->addYear(), 'numbers_of_users' => 500000000, 'total' => null, 'allowance_period' => null]);
        
    }
}
