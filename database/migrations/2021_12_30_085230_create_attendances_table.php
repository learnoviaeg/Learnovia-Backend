<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAttendancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->longText('name');
            $table->string('attendance_type');

            $table->unsignedBigInteger('year_id');
            $table->foreign('year_id')->references('id')->on('academic_years')->onDelete('cascade')->onUpdate('cascade');

            $table->unsignedBigInteger('type_id');
            $table->foreign('type_id')->references('id')->on('academic_types')->onDelete('cascade')->onUpdate('cascade');

            $table->unsignedBigInteger('segment_id');
            $table->foreign('segment_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');

            $table->unsignedBigInteger('level_id');
            $table->foreign('level_id')->references('id')->on('levels')->onDelete('cascade')->onUpdate('cascade');

            $table->unsignedBigInteger('course_id');
            $table->foreign('course_id')->references('id')->on('courses')->onDelete('cascade')->onUpdate('cascade');

            $table->integer('is_graded');

            $table->unsignedBigInteger('grade_cat_id');
            $table->foreign('grade_cat_id')->references('id')->on('grade_categories')->onDelete('cascade')->onUpdate('cascade');

            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->double('min_grade')->nullable();
            $table->double('gradeToPass')->nullable();
            $table->double('max_grade')->nullable();

            $table->unsignedBigInteger('created_by');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
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
        Schema::dropIfExists('attendances');
    }
}
