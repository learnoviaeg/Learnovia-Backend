<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterGradeCategories extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('grade_categories', function (Blueprint $table) {
            $table->boolean('type')->default(1);
            // $table->integer('grademin')->nullable();
            // $table->integer('grademax')->nullable();
            $table->integer('exclude_flag')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('grade_categories', function (Blueprint $table) {
            //
        });
    }
}
