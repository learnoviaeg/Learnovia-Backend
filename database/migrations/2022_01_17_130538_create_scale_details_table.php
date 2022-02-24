<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateScaleDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('scale_details', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->double('grade')->nullable();
            $table->string('evaluation')->nullable();
            $table->unsignedBigInteger('scale_id');
            $table->foreign('scale_id')->references('id')->on('scales')->onDelete('cascade')->onUpdate('cascade');
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
        Schema::dropIfExists('scale_details');
    }
}
