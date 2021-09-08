<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddYearToType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('academic_types', function (Blueprint $table) {
            $table->unsignedBigInteger('academic_year_id')->after('name')->nullable();
            $table->foreign('academic_year_id')->references('id')->on('academic_types')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('academic_types', function (Blueprint $table) {
            //
        });
    }
}
