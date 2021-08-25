<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFlagsGradeCategory extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('grade_categories', function (Blueprint $table) {
            $table->double('min')->default(0);
            $table->double('max')->nullable(); 
            $table->string('calculation_type')->nullable(); 
            $table->boolean('locked')->default(0);
            $table->boolean('exclude_empty_grades')->nullable();
            $table->boolean('weight_adjust')->nullable();
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
