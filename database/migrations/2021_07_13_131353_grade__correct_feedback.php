<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class GradeCorrectFeedback extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Schema::table('quizzes', function (Blueprint $table) {
        //     $table->dropColumn(['feedback']);
        // });

        Schema::table('quizzes', function (Blueprint $table) {
            $table->enum('grade_feedback',['After submission', 'After due_date', 'Never']);
            $table->enum('correct_feedback',['After submission', 'After due_date', 'Never']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('quizzes', function (Blueprint $table) {
            //
        });
    }
}
