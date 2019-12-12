<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAttendanceLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('attendance_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('ip_address');
            $table->unsignedBigInteger('session_id');
            $table->foreign('session_id')->references('id')->on('attendance_sessions')->onDelete('cascade')->onUpdate('cascade');
            $table->unsignedBigInteger('student_id');
            $table->foreign('student_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->unsignedBigInteger('status_id');
            $table->foreign('status_id')->references('id')->on('attendance_statuses')->onDelete('cascade')->onUpdate('cascade');
            $table->unsignedBigInteger('taker_id');
            $table->foreign('taker_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->dateTime('taken_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('attendance_logs');
    }
}
