<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;

class ScalePermissions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Permission::where('name','course/toggle/letter')->delete();
        Permission::where('name','letter/add')->delete();
        Permission::where('name','letter/update')->delete();
        Permission::where('name','letter/delete')->delete();
        Permission::where('name','letter/get')->delete();
        Permission::where('name','letter/assign')->delete();
        Permission::where('name','letter/get-with-course')->delete();
        Permission::where('name','scale/add')->delete();
        Permission::where('name','scale/update')->delete();
        Permission::where('name','scale/delete')->delete();
        Permission::where('name','scale/get')->delete();
        Permission::where('name','scale/get-with-course')->delete();

        Artisan::call('db:seed', [
            '--class' => 'PermissionSeeder',
            '--force' => true 
        ]);
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
