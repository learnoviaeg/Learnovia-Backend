<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RestricetedComponents extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->integer('restricted')->default(0);
        });
        Schema::table('quizzes', function (Blueprint $table) {
            $table->integer('restricted')->default(0);
        });
        Schema::table('assignments', function (Blueprint $table) {
            $table->integer('restricted')->default(0);
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
