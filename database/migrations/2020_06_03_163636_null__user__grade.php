<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class NullUserGrade extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_grades', function (Blueprint $table) {
            $table->decimal('raw_grade')->nullable()->change();
            $table->decimal('raw_grade_max')->nullable()->change();
            $table->decimal('final_grade')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('usergrades', function (Blueprint $table) {
            //
        });
    }
}
