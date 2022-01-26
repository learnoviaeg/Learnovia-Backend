<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use App\AttendanceStatus;

class UpdateStatusAttendance extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $details=['Present'=>['value'=>1,'color'=> '#28a74580', 'hover' => '#28a74526'],
            'Late'=>['value'=>0.5,'color'=> '#3f51b580', 'hover' => '#3f51b526'],
            'Excuse'=>['value'=>0,5,'color'=> '#ffc10780', 'hover' => '#ffc10726'],
            'Absent'=>['value'=>0,'color'=> '#f44336a3', 'hover' => '#f443362b']
        ];

        $AttendanceStatus=AttendanceStatus::whereId(1)->update([
            'values' => json_encode($details)
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('attendance_statuses', function (Blueprint $table) {
            //
        });
    }
}
