<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CourseInQuestCat extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('questions_categories', function (Blueprint $table) {
            $table->dropForeign(['course_segment_id']);
            $table->dropColumn(['course_segment_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('questions_categories', function (Blueprint $table) {
            //
        });
    }
}
