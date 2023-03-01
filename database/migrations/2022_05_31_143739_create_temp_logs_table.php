<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTempLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('temp_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->text('action');
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->text('properties')->nullable();
            $table->text('before')->nullable();
            $table->string('host', 45)->nullable();
            $table->text('year_id')->nullable();
            $table->text('type_id')->nullable();
            $table->text('level_id')->nullable();
            $table->text('class_id')->nullable();
            $table->text('segment_id')->nullable();
            $table->text('course_id')->nullable();
            $table->text('role_id')->nullable();
            $table->string('notes')->nullable();
            $table->string('item_name')->nullable();
            $table->string('item_id')->nullable();
            $table->text('hole_description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('temp_logs');
    }
}
