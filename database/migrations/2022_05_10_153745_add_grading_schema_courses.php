<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddGradingSchemaCourses extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('grading_schema_courses', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('grading_schema_id');
            $table->unsignedBigInteger('level_id');
            $table->unsignedBigInteger('course_id');
            $table->timestamps();

            $table->foreign('grading_schema_id')->references('id')->on('grading_schema')->onDelete('cascade');
            $table->foreign('level_id')->references('id')->on('levels')->onDelete('cascade');
            $table->foreign('course_id')->references('id')->on('grading_schema')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('grading_schema_course');
    }
}
