<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Migrations\Migration;

class RouteallAttendance extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Permission::where('title', 'Get Attendances')->update(['name' => 'attendance/viewAllAttendance']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('permissions', function (Blueprint $table) {
            //
        });
    }
}
