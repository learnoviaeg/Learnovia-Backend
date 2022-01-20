<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Maatwebsite\Excel\Facades\Excel;

class ImportDictionary extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Permission::where('name','attendance/get-session')->where('title', 'Get Sessions')->delete();
        Permission::where('name','attendance/get-daily')->where('title', 'Daily')->delete();

        Artisan::call('db:seed', [
            '--class' => 'PermissionSeeder',
            '--force' => true 
        ]);

        eval('$importer = new App\Imports\\LanguageImport();');
        $check = Excel::import($importer, public_path('translation/EngTranslate.xlsx'));
        $check1 = Excel::import($importer, public_path('translation/ArabTranslate.xlsx'));
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
