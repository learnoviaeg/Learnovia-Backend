<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CourseInGradeCat extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('grade_categories', function (Blueprint $table) {
            $table->dropForeign(['course_segment_id']);
            $table->dropColumn(['course_segment_id']);
        });

        Schema::table('grade_categories', function (Blueprint $table) {
            $table->unsignedBigInteger('course_id')->after('name')->nullable();
            $table->foreign('course_id')->references('id')->on('courses')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('grade_categories', function (Blueprint $table) {
            //
        });
    }
}
