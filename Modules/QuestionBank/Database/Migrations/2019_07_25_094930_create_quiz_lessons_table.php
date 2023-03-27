<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateQuizLessonsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('quiz_lessons', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('quiz_id');
            $table->foreign('quiz_id')->references('id')->on('quizzes')->onDelete('cascade')->onUpdate('cascade');

            $table->unsignedBigInteger('lesson_id');
            $table->foreign('lesson_id')->references('id')->on('lessons')->onDelete('cascade')->onUpdate('cascade');

            $table->timestamp('start_date')->nullable();
            $table->timestamp('due_date')->nullable();
            $table->boolean('visible')->default(1);
            $table->integer('max_attemp');
            $table->unsignedBigInteger('grading_method_id');
            $table->integer('grade');
            $table->date('publish_date');
            $table->unsignedBigInteger('grade_category_id');

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
        Schema::dropIfExists('quiz_lessons');
    }
}
