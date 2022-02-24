<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class SessionLogs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('session_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
        
            $table->unsignedBigInteger('session_id');
            $table->foreign('session_id')->references('id')->on('attendance_sessions')->onDelete('cascade')->onUpdate('cascade');
        
            $table->string('status')->nullable();

            $table->unsignedBigInteger('taken_by');
            $table->foreign('taken_by')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
        
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
        //
    }
}
