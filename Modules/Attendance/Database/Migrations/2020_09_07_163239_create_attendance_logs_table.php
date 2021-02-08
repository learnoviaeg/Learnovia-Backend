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
            $table->integer('session_id');
            $table->unsignedBigInteger('student_id');
            $table->foreign('student_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->unsignedBigInteger('taker_id');
            $table->foreign('taker_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->dateTime('taken_at');
            $table->enum('status',['Absent','Late','Present','Excuse'])->nullable();
            $table->enum('type',['online','offline']);
            $table->dateTime('entered_date')->nullable();
            $table->dateTime('left_date')->nullable();
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
