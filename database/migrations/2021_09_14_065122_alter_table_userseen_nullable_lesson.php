<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AlterTableUserseenNullableLesson extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_seens', function (Blueprint $table) {
            $table->dropForeign(['lesson_id']);
            $table->dropColumn(['lesson_id']);
        });

        Schema::table('user_seens', function (Blueprint $table) {
            $table->unsignedBigInteger('lesson_id')->nullable();
            $table->foreign('lesson_id')->references('id')->on('lessons')->onDelete('cascade')->onUpdate('cascade');
        });

        DB::statement("ALTER TABLE user_seens MODIFY COLUMN type ENUM('page', 'file','media','assignment','quiz','meeting','h5p','link','announcement')");
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
