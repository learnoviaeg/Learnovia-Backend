<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterAttendance extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn('grade');
            $table->text('allowed_levels');
            $table->text('allowed_classes');
            $table->text('allowed_courses');
            $table->boolean('graded');

        });
    }
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('attendances', function (Blueprint $table) {

        });
    }
}
