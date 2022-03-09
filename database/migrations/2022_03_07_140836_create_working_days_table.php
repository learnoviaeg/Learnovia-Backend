<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use App\WorkingDay;

class CreateWorkingDaysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('working_days', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->longText('day');
            $table->boolean('status');
            $table->timestamps();
        });

        $workingDays = [
            ['day' => 'Saturday','status' => false ],
            ['day' => 'Sunday','status' => true],
            ['day' => 'Monday','status' => true],
            ['day' => 'Tuesday','status' => true ],
            ['day' => 'Wendesday','status' => true],
            ['day' => 'Thuresday','status' => true],
            ['day' => 'Friday','status' => false],
        ];
        WorkingDay::insert($workingDays);

        Artisan::call('db:seed', [
            '--class' => 'PermissionSeeder',
            '--force' => true 
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('working_days');
    }
}
