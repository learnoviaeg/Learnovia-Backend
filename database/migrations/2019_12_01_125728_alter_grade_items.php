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
            $table->dropColumn(['calculation','item_Entity']);
            $table->dropForeign('grade_items_scale_id_foreign');
            $table->dropColumn('scale_id');
            $table->dropForeign('grade_items_item_type_foreign');
            $table->dropColumn('item_type');
        });

        Schema::table('grade_items', function (Blueprint $table) {
            $table->unsignedBigInteger('scale_id')->nullable();
            $table->foreign('scale_id')->references('id')->on('scales')->onDelete('cascade')->onUpdate('cascade');
            $table->unsignedBigInteger('item_type')->nullable();
            $table->foreign('item_type')->references('id')->on('item_types')->onDelete('cascade')->onUpdate('cascade'); 
            $table->integer('item_Entity')->nullable();
        });

        Schema::table('grade_items' , function (Blueprint $table)
        {
            $table->longText('calculation')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('grade_items', function (Blueprint $table) {
            //
        });
    }
}
