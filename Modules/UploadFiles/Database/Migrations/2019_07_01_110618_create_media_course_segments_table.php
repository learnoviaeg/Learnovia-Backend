<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMediaCourseSegmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('media_course_segments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('course_segment_id');
            $table->foreign('course_segment_id')->references('id')->on('course_segments')->onDelete('cascade')->onUpdate('cascade');

            $table->unsignedBigInteger('media_id');
            $table->foreign('media_id')->references('id')->on('media')->onDelete('cascade')->onUpdate('cascade');

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
        Schema::dropIfExists('media_course_segments');
    }
}
