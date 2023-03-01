<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AllowComment extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('grade_categories', function (Blueprint $table) {
            $table->integer('allow_comment')->defult(0);
        });

        Schema::table('user_graders', function (Blueprint $table) {
            $table->longText('comment')->nullable();
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

        Schema::table('user_graders', function (Blueprint $table) {
            //
        });
    }
}
