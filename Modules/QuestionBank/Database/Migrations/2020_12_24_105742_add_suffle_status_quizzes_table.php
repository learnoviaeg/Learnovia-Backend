<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSuffleStatusQuizzesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('quizzes', function (Blueprint $table) {
            // $table->enum('shuffle', array('No Shuffle', 'Questions','Answers','Questions and Answers'))->nullable()->change();
            DB::statement("ALTER TABLE quizzes MODIFY COLUMN shuffle ENUM('No Shuffle', 'Questions','Answers','Questions and Answers') DEFAULT 'No Shuffle' "); 

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('', function (Blueprint $table) {

        });
    }
}
