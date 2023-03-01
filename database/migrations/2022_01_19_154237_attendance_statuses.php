<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use App\AttendanceStatus;

class AttendanceStatuses extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('attendance_statuses', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->longText('values');
            $table->timestamps();
        });

        $details=['Present'=>1,'Late'=>0.5,'Excuse'=>0.5,'Absent'=>0];

        $AttendanceStatus=AttendanceStatus::firstOrCreate([
            'name' => 'attendance_details',
            'values' => json_encode($details)
        ]);

        Schema::table('attendance_sessions', function (Blueprint $table) {
            $table->unsignedBigInteger('course_id')->after('class_id')->nullable();
            $table->foreign('course_id')->references('id')->on('courses')->onDelete('cascade')->onUpdate('cascade');
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
