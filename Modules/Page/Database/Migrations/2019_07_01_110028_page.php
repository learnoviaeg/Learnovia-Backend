<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Page extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
       Schema::create('pages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->longText('page_content');
            $table->string('attached_file')->nullable();
            $table->boolean('visability')->nullable();
            $table->string('class_id')->nullable();
            $table->unsignedBigInteger('segment_id')->nullable();
            $table->foreign('segment_id')->references('id')->on('segments')->inDelete('cascade')->onUpdate('cascade');
            $table->dateTime('start_date')->nullable();
            $table->dateTime('due_date')->nullable();
            $table->integer('group_id')->nullable();
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
