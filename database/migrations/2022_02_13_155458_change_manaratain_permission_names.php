<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use App\Dictionary;
use Maatwebsite\Excel\Facades\Excel;

class ChangeManaratainPermissionNames extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Permission::where('name','report_card/mfis/boys')->update([
            'name' => 'report_card/mfis/mfisb'
        ]);
        Permission::where('name','report_card/mfis/girls')->update([
            'name' => 'report_card/mfis/mfisg'
        ]);
        Permission::where('name','report_card/fgl')->update([
            'name' => 'report_card/fgls'
        ]);

        Artisan::call('db:seed', [
            '--class' => 'PermissionSeeder',
            '--force' => true 
        ]);

        // eval('$importer = new App\Imports\\LanguageImport();');
        // $check = Excel::import($importer, public_path('translation/EngTranslate.xlsx'));
        // $check1 = Excel::import($importer, public_path('translation/ArabTranslate.xlsx'));

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
