<?php

use Illuminate\Database\Seeder;
use Modules\QuestionBank\Entities\Quiz;
use App\Http\Controllers\ExcelController;
use Maatwebsite\Excel\Facades\Excel;
use Modules\QuestionBank\Http\Controllers\QuestionBankController;
use Modules\UploadFiles\Http\Controllers\FilesController;
use Modules\Page\Http\Controllers\PageController;
use Modules\Bigbluebutton\Http\Controllers\BigbluebuttonController;
use Modules\Attendance\Http\Controllers\AttendanceSessionController;
use App\Http\Controllers\H5PLessonController;
use Modules\Assigments\Http\Controllers\AssigmentsController;
use App\Exports\ExportRoleWithPermissions;
use Illuminate\Support\Facades\Storage;
use App\Settings;
use GuzzleHttp\Client;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {

        

        $this->call(ContractSeeder::class);
        $this->call(PermissionSeeder::class);
        $this->call(RoleSeeder::class);
        $this->call(UserSeeder::class);
        $this->call(LanguageSeeder::class);
        $this->call(ItemTypeSeeder::class);
        $this->call(LetterSeeder::class);
        $this->call(ScaleSeeder::class);
        $this->call(SettingsSeeder::class);
        //$this->call(SeedLearnoviaDB::class);

        eval('$importer = new App\Imports\\LanguageImport();');
        $check = Excel::import($importer, public_path('translation/EngTranslate.xlsx'));
        $check1 = Excel::import($importer, public_path('translation/ArabTranslate.xlsx'));
        
        //install components
        \App::call('Modules\UploadFiles\Http\Controllers\FilesController@install_file');
        \App::call('Modules\QuestionBank\Http\Controllers\QuestionBankController@install_question_bank');
        \App::call('Modules\Attendance\Http\Controllers\AttendanceSessionController@install');
        \App::call('Modules\Assigments\Http\Controllers\AssigmentsController@install_Assignment');
        \App::call('Modules\Page\Http\Controllers\PageController@install');
        \App::call('Modules\Bigbluebutton\Http\Controllers\BigbluebuttonController@install');
        \App::call('App\Http\Controllers\H5PLessonController@install');

    }
}
