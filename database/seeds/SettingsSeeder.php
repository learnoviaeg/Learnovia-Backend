<?php

use Illuminate\Database\Seeder;
use App\Settings;


class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Settings::create([
            'key' => 'create_assignment_extensions',
            'title' => 'Create/Edit Assignment Supported Extensions',
            'value' => 'txt,pdf,docs,jpg,doc,docx,mp4,avi,flv,mpga,ogg,ogv,oga,jpeg,png,gif,csv,mp3,mpeg,ppt,pptx,rar,rtf,zip,xlsx,xls',
            'type' => 'extensions'
        ]);

        Settings::create([
            'key' => 'submit_assignment_extensions',
            'title' => 'Submit Assignment Supported Extensions',
            'value' => 'pdf,docs,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,mp4,avi,flv,mpeg,mpga,movie,mov,mp3,wav,amr',
            'type' => 'extensions'
        ]);

        Settings::create([
            'key' => 'upload_file_extensions',
            'title' => 'Upload File Supported Extensions',
            'value' => 'pdf,docx,doc,xls,xlsx,ppt,pptx,zip,rar,txt,TXT,odt,rtf,tex,wpd,rpm,z,ods,xlsm,pps,odp,7z,bdoc,cdoc,ddoc,gtar,tgz,gz,gzip,hqx,sit,tar,epub,gdoc,ott,oth,vtt,gslides,otp,pptm,potx,potm,ppam,ppsx,ppsm,pub,sxi,sti,csv,gsheet,ots,css,html,xhtml,htm,js,scss',
            'type' => 'extensions'
        ]);

        Settings::create([
            'key' => 'upload_media_extensions',
            'title' => 'Upload Media Supported Extensions',
            'value' => 'mp4,avi,flv,mpga,ogg,ogv,oga,jpg,jpeg,png,gif,doc,mp3,wav,amr,mid,midi,mp2,aif,aiff,aifc,ram,rm,rpm,ra,rv,mpeg,mpe,qt,mov,movie,aac,au,flac,m3u,m4a,wma,ai,bmp,gdraw,ico,jpe,pct,pic,pict,svg,svgz,tif,tiff,3gp,dv,dif,f4v,m4v,mpg,rmvb,swf,swfl,webm,wmv,asf',
            'type' => 'extensions'
        ]);
    }
}
