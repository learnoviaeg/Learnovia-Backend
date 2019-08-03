<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGradesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('grades', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('grade_category');
            $table->foreign('grade_category')->references('id')->on('grade_categories')->onDelete('cascade')->onUpdate('cascade');
            $table->integer('grademin');
            $table->integer('grademax');
            $table->text('calculation');
            $table->integer('item_no')->nullable();
            $table->unsignedBigInteger('scale_id');
            $table->foreign('scale_id')->references('id')->on('scales')->onDelete('cascade')->onUpdate('cascade');
            $table->integer('grade_pass');
            $table->decimal('multifactor')->default(1);
            $table->decimal('plusfactor')->default(1);
            $table->decimal('aggregationcoef')->nullable();
            $table->decimal('aggregationcoef2')->nullable();
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
        Schema::dropIfExists('grades');
    }
}
