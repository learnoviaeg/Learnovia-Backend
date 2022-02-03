<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use App\Dictionary;
use Illuminate\Database\Migrations\Migration;

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

        eval('$importer = new App\Imports\\LanguageImport();');
        $check = Excel::import($importer, public_path('translation/EngTranslate.xlsx'));
        $check1 = Excel::import($importer, public_path('translation/ArabTranslate.xlsx'));

        Dictionary::where('value','=TRUE()')->update(['value' => 'True']);
        Dictionary::where('key','=TRUE()')->update(['key' => 'True']);
        Dictionary::where('key','=FALSE()')->update(['key' => 'False']);
        Dictionary::where('value','=FALSE()')->update(['value' => 'False']);

        // Dictionary::firstOrCreate([
        //     'key' => 0,
        //     'value' => (string)'False',
        //     'language' => 1
        // ]);
        // Dictionary::firstOrCreate([
        //     'key' => 1,
        //     'value' => (string)'True',
        //     'language' => 1
        // ]);
        // Dictionary::firstOrCreate([
        //     'key' => (string)'0',
        //     'value' => 'خطأ',
        //     'language' => 2
        // ]);
        // Dictionary::firstOrCreate([
        //     'key' => (string)'1',
        //     'value' => 'صحيح',
        //     'language' => 2
        // ]);
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
