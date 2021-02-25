<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateQMCQChoicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('q__m_c_q__choices', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('q_mcq_id');
            $table->foreign('q_mcq_id')->references('id')->on('q__m_c_q_s')->onDelete('cascade')->onUpdate('cascade');
            $table->longText('content');
            $table->integer('is_true');            
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
        Schema::dropIfExists('q__m_c_q__choices');
    }
}
