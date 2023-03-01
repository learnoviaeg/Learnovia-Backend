<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('amount')->nullable();
            $table->dateTime('date')->nullable();
            $table->text('note')->nullable();
            $table->unsignedBigInteger('contract_id')->nullable();
            $table->foreign('contract_id')->references('id')->on('contracts')->onDelete('cascade')->onUpdate('cascade');
            $table->unsignedBigInteger('status_id')->nullable();
            $table->foreign('status_id')->references('id')->on('statuses')->onDelete('cascade')->onUpdate('cascade');
            $table->unsignedBigInteger('child_id')->nullable();
            $table->foreign('child_id')->references('id')->on('payments')->onDelete('cascade')->onUpdate('cascade');
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
        Schema::dropIfExists('payments');
    }
}
