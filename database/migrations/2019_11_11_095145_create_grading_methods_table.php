<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateGradingMethodsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('grading_methods', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name',45);
            $table->timestamps();
        });
        DB::table('grading_methods')->insert([
                ['id' => 1,'name' => 'Natural'],
                ['id' => 2,'name' => 'Simple weighted mean'],
                ['id' => 3,'name' => 'Weighted mean']
            ]
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('grading_methods');
    }
}
