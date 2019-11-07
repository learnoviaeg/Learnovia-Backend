<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateCourseSegments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('course_segments', function (Blueprint $table) {
            $table->boolean('letter')->default(0);
            $table->unsignedBigInteger('letter_id')->nullable();
            $table->foreign('letter_id')->references('id')->on('letters')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('course_segments', function (Blueprint $table) {
            //
        });
    }
}
