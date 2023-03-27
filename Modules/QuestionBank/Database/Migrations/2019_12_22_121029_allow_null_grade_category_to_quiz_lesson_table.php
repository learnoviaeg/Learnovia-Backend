<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AllowNullGradeCategoryToQuizLessonTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('quiz_lessons', function (Blueprint $table) {
            $table->dropColumn('grade_category_id');
        });

        Schema::table('quiz_lessons', function (Blueprint $table) {
            $table->unsignedBigInteger('grade_category_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('quiz_lesson', function (Blueprint $table) {
            //
        });
    }
}
