<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOverrideAssignmentQuizzesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('override_assignment_quizzes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');  
            $table->unsignedBigInteger('quiz_lesson_id')->nullable();
            $table->foreign('quiz_lesson_id')->references('id')->on('quiz_lessons')->onDelete('cascade')->onUpdate('cascade');
            $table->unsignedBigInteger('assignment_lesson_id')->nullable();
            $table->foreign('assignment_lesson_id')->references('id')->on('assignment_lessons')->onDelete('cascade')->onUpdate('cascade');
            $table->timestamp('start_date');
            $table->timestamp('due_date')->nullable();
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
        Schema::dropIfExists('override_assignment_quizzes');
    }
}
