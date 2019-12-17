<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class BooleanGradeCat extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('grade_categories', function (Blueprint $table) {
            $table->dropColumn('hidden');
            $table->dropColumn('weight');
            $table->dropColumn('exclude_flag');
            $table->dropColumn('type');
            $table->dropColumn('locked');
        });

        Schema::table('grade_categories', function (Blueprint $table) {
            $table->double('weight')->nullable();
            $table->boolean('hidden');
            $table->boolean('locked');
            $table->boolean('exclude_flag');
            $table->boolean('type');
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
