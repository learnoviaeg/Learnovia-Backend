<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTopicChainsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('topic_chains', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->longText('years')->nullable();
            $table->longText('types')->nullable();
            $table->longText('levels')->nullable();
            $table->longText('classes')->nullable();
            $table->longText('segments')->nullable();
            $table->longText('courses')->nullable();
            $table->string('topic_title');
            $table->unsignedBigInteger('created_by');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
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
        Schema::dropIfExists('topic_chains');
    }
}
