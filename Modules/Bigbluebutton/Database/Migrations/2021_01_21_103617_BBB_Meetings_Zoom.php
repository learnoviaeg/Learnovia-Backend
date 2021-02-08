<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class BBBMeetingsZoom extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('bigbluebutton_models', function (Blueprint $table) {
            $table->dropColumn(['attendee_password','moderator_password']);
        });

        Schema::table('bigbluebutton_models', function (Blueprint $table) {
            $table->longText('join_url')->nullable();
            $table->string('type')->nullable();
            $table->string('host_id')->nullable();
            $table->string('attendee_password')->nullable();
            $table->string('moderator_password')->nullable();
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
