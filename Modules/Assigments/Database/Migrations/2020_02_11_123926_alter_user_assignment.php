<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterUserAssignment extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
            Schema::table('user_assigments', function (Blueprint $table) {
                $table->dropForeign(['assignment_id']);
                $table->dropColumn(['assignment_id']);
                $table->unsignedBigInteger('assignment_lesson_id');
                $table->foreign('assignment_lesson_id')->references('id')->on('lessons')->onDelete('cascade')->onUpdate('cascade');
    
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
