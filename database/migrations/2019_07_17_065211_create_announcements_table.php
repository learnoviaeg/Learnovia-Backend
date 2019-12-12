<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAnnouncementsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title');
            $table->text('description');
            $table->unsignedBigInteger('attached_file')->nullable();
            $table->foreign('attached_file')->references('id')->on('attachments')->onDelete('cascade')->onUpdate('cascade');
            $table->dateTime('start_date')->nullable();
            $table->dateTime('due_date')->nullable();
            $table->enum('assign', ['all', 'class','course','level','year','type','segment'])->nullable();
            $table->integer('class_id')->nullable();
            $table->integer('level_id')->nullable();
            $table->integer('course_id')->nullable();
            $table->integer('year_id')->nullable();
            $table->integer('type_id')->nullable();
            $table->integer('segment_id')->nullable();
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
        Schema::dropIfExists('announcements');
    }
}
