<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UserQuizAnswerNew extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_quiz_answers', function (Blueprint $table) {
            $table->dropForeign(['answer_id']);
            $table->dropColumn('answer_id');
            $table->dropColumn('and_why');
            $table->dropColumn('mcq_answers_array');
            $table->dropColumn('choices_array');
            $table->dropColumn('user_grade');
            $table->dropColumn('content');
            $table->longText('user_answer');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_quiz_answers', function (Blueprint $table) {
            //
        });
    }
}
