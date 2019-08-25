<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserGradesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_grades', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('grade_item_id');
            $table->foreign('grade_item_id')->references('id')->on('grade_items')->onDelete('cascade')->onUpdate('cascade');
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->decimal('raw_grade');
            $table->decimal('raw_grade_max');
            $table->decimal('raw_grade_min')->default(0);
            $table->unsignedBigInteger('raw_scale_id');
            $table->foreign('raw_scale_id')->references('id')->on('scales')->onDelete('cascade')->onUpdate('cascade');
            $table->decimal('final_grade');
            $table->boolean('hidden')->default(0);
            $table->boolean('locked')->default(0);
            $table->mediumText('feedback');
            $table->unsignedBigInteger('letter_id');
            $table->foreign('letter_id')->references('id')->on('letters')->onDelete('cascade')->onUpdate('cascade');
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
        Schema::dropIfExists('user_grades');
    }
}
