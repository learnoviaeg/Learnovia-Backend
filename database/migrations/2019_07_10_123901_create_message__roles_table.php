<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMessageRolesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('message__roles', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('From_Role')->unsigned()->index();
            $table->foreign('From_Role')->references('id')->on('roles')->onDelete('cascade')->onUpdate('cascade');
            $table->unsignedInteger('To_Role')->unsigned()->index();
            $table->foreign('To_Role')->references('id')->on('roles')->onDelete('cascade')->onUpdate('cascade');
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
        Schema::dropIfExists('message__roles');
    }
}
