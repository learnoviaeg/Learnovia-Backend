<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ZoomModel extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('zoom_model', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('class')->nullable();
            $table->foreign('class')->references('id')->on('classes')->onDelete('cascade')->onUpdate('cascade');
            $table->unsignedBigInteger('course')->nullable();
            $table->foreign('course')->references('id')->on('courses')->onDelete('cascade')->onUpdate('cascade');
            $table->string('password');
            $table->string('topic');
            $table->enum('status',['future','past','current']);
            $table->dateTime('start_date');
            $table->dateTime('actual_start_date');
            $table->integer('duration');
            $table->integer('actual_duration');
            $table->enum('record',['local','cloud','none']);
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
