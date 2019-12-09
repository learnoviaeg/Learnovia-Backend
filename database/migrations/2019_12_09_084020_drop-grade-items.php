<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DropGradeItems extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('grade_items', function (Blueprint $table) {
            $table->dropColumn('grademin');
            $table->dropColumn('grademax');
            $table->dropColumn('calculation');
            $table->dropColumn('grade_pass');
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
