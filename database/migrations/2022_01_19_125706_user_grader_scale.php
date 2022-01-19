<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UserGraderScale extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_graders', function (Blueprint $table) {
            $table->longtext('scale')->nullable();
            $table->unsignedBigInteger('scale_id')->nullable();
            $table->foreign('scale_id')->references('id')->on('scale_details')->onDelete('set null')->onUpdate('set null');
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
