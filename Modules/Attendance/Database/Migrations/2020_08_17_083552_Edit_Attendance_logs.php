<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class EditAttendanceLogs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('attendance_logs', function (Blueprint $table) {
            $table->dropForeign(['session_id']);            
            $table->dropcolumn('session_id');
            $table->dropForeign(['status_id']);            
            $table->dropcolumn('status_id');
        });
        Schema::table('attendance_logs', function (Blueprint $table) {
            $table->integer('session_id');
            $table->enum('status',['Absent','Late','Present','Excuse'])->nullable();
            $table->enum('type',['online','offline']);
            $table->dateTime('entered_date')->nullable();
            $table->dateTime('left_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
