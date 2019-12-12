<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterUserGrades extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_grades', function (Blueprint $table) {
            $table->dropColumn('feedback');
            $table->dropColumn('hidden');
            $table->dropColumn('locked');
            $table->dropForeign('user_grades_letter_id_foreign');
            $table->dropColumn('letter_id');
            $table->dropForeign('user_grades_raw_scale_id_foreign');
            $table->dropColumn('raw_scale_id');
            //$table->mediumText('feedback')->nullable();
        });

        Schema::table('user_grades', function (Blueprint $table) {
            $table->mediumText('feedback')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_grades', function (Blueprint $table) {
            //
        });
    }
}
