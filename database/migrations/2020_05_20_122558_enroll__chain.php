<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class EnrollChain extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('enrolls', function (Blueprint $table) {
            $table->unsignedBigInteger('level')->nullable();
            $table->foreign('level')->references('id')->on('levels')->onDelete('cascade')->onUpdate('cascade');
            $table->unsignedBigInteger('type')->nullable();
            $table->foreign('type')->references('id')->on('academic_types')->onDelete('cascade')->onUpdate('cascade');
            $table->unsignedBigInteger('class')->nullable();
            $table->foreign('class')->references('id')->on('classes')->onDelete('cascade')->onUpdate('cascade');
            $table->unsignedBigInteger('year')->nullable();
            $table->foreign('year')->references('id')->on('academic_years')->onDelete('cascade')->onUpdate('cascade');
            $table->unsignedBigInteger('course')->nullable();
            $table->foreign('course')->references('id')->on('courses')->onDelete('cascade')->onUpdate('cascade');
            $table->unsignedBigInteger('segment')->nullable();
            $table->foreign('segment')->references('id')->on('segments')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
