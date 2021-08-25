<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ItemDetails extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('item_details', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->enum('type',['Question','Assignment','Attendance','Iteractive']);
            $table->integer('item_id'); //id of previous type
            $table->unsignedBigInteger('parent_item_id');
            $table->foreign('parent_item_id')->references('id')->on('grade_items')->onDelete('cascade')->onUpdate('cascade');
            $table->longText('weight_details');
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
