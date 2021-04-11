<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFileCourseSegmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('file_course_segments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('course_segment_id');
            $table->foreign('course_segment_id')->references('id')->on('course_segments')->onDelete('cascade')->onUpdate('cascade');

            $table->unsignedBigInteger('file_id');
            $table->foreign('file_id')->references('id')->on('files')->onDelete('cascade')->onUpdate('cascade');
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
        Schema::dropIfExists('file_course_segments');
    }
}
