<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBigbluebuttonModelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bigbluebutton_models', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->integer('class_id');
            $table->integer('course_id');
            $table->string('attendee_password');
            $table->string('moderator_password');
            $table->time('duration');
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
        Schema::dropIfExists('bigbluebutton_models');
    }
}
