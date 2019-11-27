<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAssigmentLessonTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('assignment_lessons', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('assignment_id');
            $table->foreign('assignment_id')->references('id')->on('assignments')->onDelete('cascade')->onUpdate('cascade');
            $table->unsignedBigInteger('lesson_id');
            $table->foreign('lesson_id')->references('id')->on('lessons')->onDelete('cascade')->onUpdate('cascade');
            $table->boolean('visible')->default(1);
            $table->date('publish_date');
            $table->timestamp('start_date');
            $table->timestamp('due_date')->nullable();
            $table->boolean('is_graded');
            $table->unsignedBigInteger('grade_category')->nullable();
            $table->integer('mark');
            $table->unsignedBigInteger('scale_id')->nullable();
            $table->tinyInteger('allow_attachment');
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
        Schema::dropIfExists('assigment_lesson');
    }
}
