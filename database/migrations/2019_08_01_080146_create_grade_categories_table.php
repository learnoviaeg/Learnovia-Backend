<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGradeCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('grade_categories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->unsignedBigInteger('course_segment_id');
            $table->foreign('course_segment_id')->references('id')->on('course_segments')->onDelete('cascade')->onUpdate('cascade');
            $table->unsignedBigInteger('parent')->nullable();
            $table->foreign('parent')->references('id')->on('grade_categories')->onDelete('cascade')->onUpdate('cascade');
            $table->integer('aggregation')->nullable();
            $table->boolean('aggregatedOnlyGraded')->nullable();
            $table->boolean('hidden')->default(0);
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
        Schema::dropIfExists('grade_categories');
    }
}
