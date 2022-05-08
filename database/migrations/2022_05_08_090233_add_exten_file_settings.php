<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use App\Settings;

class AddExtenFileSettings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Settings::where('key','upload_file_extensions')->update(['value' => 'pdf,docx,doc,xls,xlsx,ppt,pptx,zip,rar,txt,TXT,odt,rtf,tex,wpd,rpm,z,ods,xlsm,pps,odp,7z,bdoc,cdoc,ddoc,gtar,tgz,gz,gzip,hqx,sit,tar,epub,gdoc,ott,oth,vtt,gslides,otp,pptm,potx,potm,ppam,ppsx,ppsm,pub,sxi,sti,csv,gsheet,ots,css,html,xhtml,htm,js,scss,odt,ods,xlsm,odp,potm,potx,ppam,pps,ppsm,ppsx,pptm,bin']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('settings', function (Blueprint $table) {
            //
        });
    }
}
