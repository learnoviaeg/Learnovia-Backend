<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Settings;

class SettingsController extends Controller
{

    public function __construct()
    {
        $this->middleware(['permission:settings/general'],   ['only' => ['index']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        //validate the request
        $request->validate([
            'key' => 'string',
        ]);
        
        $settings = new Settings;

        if($request->filled('key'))
            $settings = $settings->where('key',$request->key);

        $settings = $settings->get();

        $settings->map(function ($setting){

            if($setting->key == 'create_assignment_extensions'){

                //all the extensions that our system support for the assignment
                $all_create_extensions = collect(explode(',','txt,pdf,docs,jpg,doc,docx,mp4,avi,flv,mpga,ogg,ogv,oga,jpg,jpeg,png,gif,csv,doc,docx,mp3,mpeg,ppt,pptx,rar,rtf,zip,xlsx,xls'));
                
                //the extensions that the admin choose to use
                $values = explode(',',$setting->value);

                $new_values=collect();

                //map every extension to see if it's choosen or not
                $all_create_extensions->map(function ($value) use ($new_values,$values){
                
                    $index = false;
                    if(in_array($value,$values)){
                       $index = true;
                    }

                    $new_values->push([
                        'name' => $value,
                        'index' => $index
                    ]);

                });

                $setting->value = $new_values;
            }

            if($setting->key == 'submit_assignment_extensions'){

                //all the extensions that our system support for the assignment submission
                $all_create_extensions = collect(explode(',','pdf,docs,doc,docx,xls,xlsx,ppt,pptx'));

                //the extensions that the admin choose to use
                $values = explode(',',$setting->value);

                $new_values=collect();

                //map every extension to see if it's choosen or not
                $all_create_extensions->map(function ($value) use ($new_values,$values){
                
                    $index = false;
                    if(in_array($value,$values)){
                       $index = true;
                    }

                    $new_values->push([
                        'name' => $value,
                        'index' => $index
                    ]);

                });
                
                $setting->value = $new_values;
            }

            if($setting->key == 'upload_file_extensions'){

                //all the extensions that our system support for the file upload
                $all_create_extensions = collect(explode(',','pdf,docx,doc,xls,xlsx,ppt,pptx,zip,rar,txt,TXT,odt,rtf,tex,wpd,rpm,z,ods,xlsm,pps,odp,7z,bdoc,cdoc,ddoc,gtar,tgz,gz,gzip,hqx,sit,tar,epub,gdoc,ott,oth,vtt,gslides,otp,pptm,potx,potm,ppam,ppsx,ppsm,pub,sxi,sti,csv,gsheet,ots,css,html,xhtml,htm,js,scss'));
                
                //the extensions that the admin choose to use
                $values = explode(',',$setting->value);

                $new_values=collect();

                //map every extension to see if it's choosen or not
                $all_create_extensions->map(function ($value) use ($new_values,$values){
                
                    $index = false;
                    if(in_array($value,$values)){
                       $index = true;
                    }

                    $new_values->push([
                        'name' => $value,
                        'index' => $index
                    ]);

                });
                
                $setting->value = $new_values;
            }

            if($setting->key == 'upload_media_extensions'){

                //all the extensions that our system support for the media upload
                $all_create_extensions = collect(explode(',','mp4,avi,flv,mpga,ogg,ogv,oga,jpg,jpeg,png,gif,doc,mp3,wav,amr,mid,midi,mp2,aif,aiff,aifc,ram,rm,rpm,ra,rv,mpeg,mpe,qt,mov,movie,aac,au,flac,m3u,m4a,wma,ai,bmp,gdraw,ico,jpe,pct,pic,pict,svg,svgz,tif,tiff,3gp,dv,dif,f4v,m4v,mpg,rmvb,swf,swfl,webm,wmv,asf'));
                
                //the extensions that the admin choose to use
                $values = explode(',',$setting->value);

                $new_values=collect();

                //map every extension to see if it's choosen or not
                $all_create_extensions->map(function ($value) use ($new_values,$values){
                
                    $index = false;
                    if(in_array($value,$values)){
                       $index = true;
                    }

                    $new_values->push([
                        'name' => $value,
                        'index' => $index
                    ]);

                });
                
                $setting->value = $new_values;
            }

            return $setting;
        });
        
        return response()->json(['message' => 'settings List.','body' => $settings], 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //            
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $settings = Settings::pluck('key');

        //validate the request
        $request->validate([
            'object' => 'array|required',
            'object.*.key' => 'required|in:'.implode(',',$settings->toArray()),
            'object.*.values' => 'array|required',
            'object.*.values.*' => 'string',
        ]);

        foreach($request->object as $object){

            if(!$request->user()->can('settings/'.$object['key']))
                return response()->json(['message' => 'you dont have the permission to update that content.','body' => null], 400);

            $setting = Settings::where('key',$object['key'])->update([
                'value' => implode(',',$object['values'])
            ]);
        }

        return response()->json(['message' => 'setting updated.','body' => null], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
