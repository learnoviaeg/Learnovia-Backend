<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeLevelToPivote extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('grading_schema_courses', function (Blueprint $table) {
            $table->dropForeign(['level_id']);
            $table->dropColumn(['level_id']);

        });

        Schema::create('grading_schema_levels', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('grading_schema_id');
            $table->foreign('grading_schema_id')->references('id')->on('grading_schema')->onDelete('cascade')->onUpdate('cascade');
            $table->unsignedBigInteger('level_id');
            $table->foreign('level_id')->references('id')->on('levels')->onDelete('cascade')->onUpdate('cascade');
            $table->unsignedBigInteger('segment_id');
            $table->foreign('segment_id')->references('id')->on('segments')->onDelete('cascade')->onUpdate('cascade');
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
        Schema::table('grading_schema_courses', function (Blueprint $table) {
            //
        });
    }
}
