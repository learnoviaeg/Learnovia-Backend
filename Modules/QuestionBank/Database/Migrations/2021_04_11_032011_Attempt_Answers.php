<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AttemptAnswers extends Migration
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
            $table->dropColumn(['answer_id','and_why','mcq_answers_array','choices_array','content']);
            $table->longText('user_answers')->after('question_id');
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
