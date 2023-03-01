<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class NullDeviceData extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_quizzes', function (Blueprint $table) {
            $table->text('device_data')->nullable()->change();
            $table->string('ip',15)->nullable()->change();
            $table->text('browser_data')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_quizzes', function (Blueprint $table) {
            //
        });
    }
}
