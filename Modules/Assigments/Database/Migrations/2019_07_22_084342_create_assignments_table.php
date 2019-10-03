<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAssignmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('assignments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->longText('content')->nullable();
            $table->unsignedBigInteger('attachment_id')->nullable();
            $table->foreign('attachment_id')->references('id')->on('attachments')->onDelete('cascade')->onUpdate('cascade');
            $table->timestamp('start_date');
            $table->timestamp('due_date')->nullable();
            $table->boolean('is_graded');
            $table->unsignedBigInteger('grade_category')->nullable();
            $table->integer('mark');
            $table->unsignedBigInteger('scale_id')->nullable();
            $table->tinyInteger('allow_attachment');
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('assignments');
    }
}
