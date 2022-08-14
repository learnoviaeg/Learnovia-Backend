<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class TakenBy extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_assigments', function (Blueprint $table) {
            $table->unsignedBigInteger('takenBy')->nullable();
            $table->foreign('takenBy')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
        });

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
        Schema::table('user_assigments', function (Blueprint $table) {
            //
        });
    }
}
