<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddLevelSegmentToCourse extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::disableForeignKeyConstraints();

        Schema::table('courses', function (Blueprint $table) {
            $table->unsignedBigInteger('level_id')->after('mandatory');
            $table->foreign('level_id')->references('id')->on('levels')->onDelete('cascade')->onUpdate('cascade');
            $table->unsignedBigInteger('segment_id')->after('level_id');
            $table->foreign('segment_id')->references('id')->on('segments')->onDelete('cascade')->onUpdate('cascade');
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('courses', function (Blueprint $table) {
            //
        });
    }
}
