<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterGradeItems extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('grade_items', function (Blueprint $table) {
            $table->dropColumn('type');
        });
        Schema::table('grade_items', function (Blueprint $table) {
            $table->enum('type',['Quiz','Assignment','Attendance','Interactive','Attempts']);
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
