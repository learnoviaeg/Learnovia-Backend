<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Alter2QuizOverride extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('quiz_overrides', function (Blueprint $table) {
            $table->dropColumn(['attemps']);

        }); 
        
        Schema::table('quiz_overrides', function (Blueprint $table) {
            $table->integer('attemps');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

    }
}
