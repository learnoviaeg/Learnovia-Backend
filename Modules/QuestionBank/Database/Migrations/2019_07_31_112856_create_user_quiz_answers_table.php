<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserQuizAnswersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_quiz_answers', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('user_quiz_id');
            $table->foreign('user_quiz_id')->references('id')->on('user_quizzes')->onDelete('cascade')->onUpdate('cascade');

            $table->unsignedBigInteger('question_id');
            $table->foreign('question_id')->references('id')->on('questions')->onDelete('cascade')->onUpdate('cascade');

            $table->unsignedBigInteger('answer_id')->nullable();
            $table->foreign('answer_id')->references('id')->on('questions_answers')->onDelete('cascade')->onUpdate('cascade');

            $table->mediumText('and_why')->nullable();

            $table->string('mcq_answers_array')->nullable();

            $table->mediumText('choices_array')->nullable();

            $table->mediumText('content')->nullable();

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
        Schema::dropIfExists('user_quiz_answers');
    }
}
