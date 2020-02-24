<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserSurveyAnswersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_survey_answers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_survey_id');
            $table->foreign('user_survey_id')->references('id')->on('user_surveys')->onDelete('cascade')->onUpdate('cascade');
            $table->unsignedBigInteger('question_id');
            $table->foreign('question_id')->references('id')->on('questions')->onDelete('cascade')->onUpdate('cascade');
            $table->unsignedBigInteger('answer_id')->nullable();
            $table->foreign('answer_id')->references('id')->on('questions_answers')->onDelete('cascade')->onUpdate('cascade');
            $table->mediumText('and_why')->nullable();
            $table->string('mcq_answers_array')->nullable();
            $table->mediumText('choices_array')->nullable();
            $table->mediumText('content')->nullable();
            $table->boolean('answered');
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
        Schema::dropIfExists('user_survey_answers');
    }
}
