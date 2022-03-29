<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use App\Dictionary;
use Illuminate\Database\Migrations\Migration;
use Maatwebsite\Excel\Facades\Excel;

class TrasulatePermissionAndSetIT extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Artisan::call('db:seed', [
            '--class' => 'PermissionSeeder',
            '--force' => true 
        ]);

<<<<<<< HEAD
        // eval('$importer = new App\Imports\\LanguageImport();');
        // $check = Excel::import($importer, public_path('translation/EngTranslate.xlsx'));
        // $check1 = Excel::import($importer, public_path('translation/ArabTranslate.xlsx'));
=======
>>>>>>> development

        Dictionary::where('value','=TRUE()')->update(['value' => 'True']);
        Dictionary::where('key','=TRUE()')->update(['key' => 'True']);
        Dictionary::where('key','=FALSE()')->update(['key' => 'False']);
        Dictionary::where('value','=FALSE()')->update(['value' => 'False']);
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
