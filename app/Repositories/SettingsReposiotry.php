<?php

namespace App\Repositories;
use Illuminate\Http\Request;
use App\Settings;

class SettingsReposiotry implements SettingsReposiotryInterface
{
    public function get_value($key){

        $setting = Settings::where('key',$key)->pluck('value')->first();
        return $setting;
   }
   public function get_type($exe){
       $type='';
        if(in_array($exe,['jpeg','jpg','png','gif','bmp','jpe']))
            return "Image";
        if(in_array($exe,['m3u','oga','flac','amr','mp2','wma','ai','mpeg','m4a','mpga','mp3','wav','ogg','mid','midi','aif','aiff','aifc','mpe']))
            return "Audio";
        if(in_array($exe,['mp4','f4v','rmvb','webm','wmv','au','dv','qt','ogv','asf','m4v','mpg','m3u8','ts','3gp','mov','avi','swf','swfl','wmv','flv','rm','movie']))
            return "Video";
        if(in_array($exe,explode(',','svg,svgz,dif,pct,gdraw,tif,tiff,pic,pict,ico,aac,ram,ra,rv,rpm,pdf,docx,doc,docs,xls,xlsx,ppt,pptx,zip,rar,txt,TXT,odt,rtf,tex,wpd,rpm,z,ods,xlsm,pps,odp,7z,bdoc,cdoc,ddoc,gtar,tgz,gz,gzip,hqx,sit,tar,epub,gdoc,ott,oth,vtt,gslides,otp,pptm,potx,potm,ppam,ppsx,ppsm,pub,sxi,sti,csv,gsheet,ots,css,html,xhtml,htm,js,scss')))
            return "File";      


   }
}