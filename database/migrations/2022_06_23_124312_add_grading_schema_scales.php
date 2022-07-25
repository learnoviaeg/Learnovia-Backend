<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddGradingSchemaScales extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('grading_schema_scales', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('scale_id');
            $table->unsignedBigInteger('grading_schema_id');
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
        Schema::dropIfExists('grading_schema_scales');
    }
}
