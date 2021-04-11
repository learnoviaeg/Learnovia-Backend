<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEventsTable extends Migration
{
    /**
     * Run the migrations
     * @return void
     */
    public function up()
    {
        Schema::create('events', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->text('description');
            $table->unsignedBigInteger('attached_file')->nullable();
            $table->foreign('attached_file')->references('id')->on('attachments')->onDelete('cascade')->onUpdate('cascade');
            $table->dateTime('from');
            $table->dateTime('to')->nullable();
            $table->unsignedBigInteger('cover')->nullable();
            $table->foreign('cover')->references('id')->on('attachments')->onDelete('cascade')->onUpdate('cascade');
            $table->integer('id_number')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
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
        Schema::dropIfExists('events');
    }
}
