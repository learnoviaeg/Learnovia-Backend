<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateAttendance extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->unsignedBigInteger('year_id');
            $table->foreign('year_id')->references('id')->on('academic_years')->onDelete('cascade')->onUpdate('cascade');
            $table->unsignedBigInteger('type_id');
            $table->foreign('type_id')->references('id')->on('academic_types')->onDelete('cascade')->onUpdate('cascade');
            $table->unsignedBigInteger('segment_id');
            $table->foreign('segment_id')->references('id')->on('segments')->onDelete('cascade')->onUpdate('cascade');
            $table->dateTime('start_date');
            $table->dateTime('end_date');



        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('', function (Blueprint $table) {

        });
    }
}
