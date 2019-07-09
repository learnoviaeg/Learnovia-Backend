<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {


        Schema::create('messages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->text('text');
            $table->integer('about');
            $table->unsignedBigInteger('From');
            $table->foreign('From')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->unsignedBigInteger('To');
            $table->foreign('To')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->boolean('seen');
            $table->integer('deleted');
            $table->string('file')->nullable();

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
        Schema::dropIfExists('messages');
    }
}
