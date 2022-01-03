<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class LetterDetails extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('letter_details', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->double('lower_boundary')->nullable();
            $table->double('higher_boundary')->nullable();
            $table->string('evaluation')->nullable();
            $table->unsignedBigInteger('letter_id');
            $table->foreign('letter_id')->references('id')->on('letters')->onDelete('cascade')->onUpdate('cascade');
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
        //
    }
}
