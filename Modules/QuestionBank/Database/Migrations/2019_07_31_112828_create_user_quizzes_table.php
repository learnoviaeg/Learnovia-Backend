<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserQuizzesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_quizzes', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');

            $table->unsignedBigInteger('quiz_lesson_id');
            $table->foreign('quiz_lesson_id')->references('id')->on('quiz_lessons')->onDelete('cascade')->onUpdate('cascade');

            $table->boolean('override')->default(0);

            $table->unsignedBigInteger('status_id');
            $table->foreign('status_id')->references('id')->on('statuses')->onDelete('cascade')->onUpdate('cascade');

            $table->string('feedback');

            $table->integer('grade');

            $table->integer('attempt_index');

            $table->dateTime('open_time');

            $table->dateTime('submit_time')->nullable();

            $table->string('device_data');

            $table->string('ip',15);

            $table->string('browser_data');

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
        Schema::dropIfExists('user_quizzes');
    }
}
