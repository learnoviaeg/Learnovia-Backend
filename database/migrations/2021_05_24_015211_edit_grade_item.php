<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class EditGradeItem extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::disableForeignKeyConstraints();
        Schema::drop('grade_items');
        Schema::enableForeignKeyConstraints();
        Schema::create('grade_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->enum('type',['Quiz','Assignment','Attendance','Iteractive']);
            $table->integer('item_id');
            $table->unsignedBigInteger('grade_category_id');
            $table->foreign('grade_category_id')->references('id')->on('grade_categories')->onDelete('cascade')->onUpdate('cascade');
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
        Schema::table('grade_items', function (Blueprint $table) {
            //
        });
    }
}
