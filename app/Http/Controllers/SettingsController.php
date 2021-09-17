<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\attachment;
use App\Settings;
use App\Repositories\SettingsReposiotry;

class SettingsController extends Controller
{
    protected $set;

    public function __construct(SettingsReposiotry $set)
    {
        $this->set = $set;

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
            'key' => 'array',
            'key.*' => 'string',
        ]);
        
        $settings = new Settings;

        if($request->filled('key'))
            $settings = $settings->whereIn('key',$request->key);

        $settings = $settings->get();

        $settings->map(function ($setting){

            if($setting->key == 'create_assignment_extensions'){

                //all the extensions that our system support for the assignment
                $all_create_extensions = collect(explode(',','txt,pdf,docs,jpg,doc,docx,mp4,avi,flv,mpga,ogg,ogv,oga,jpeg,png,gif,csv,mp3,mpeg,ppt,pptx,rar,rtf,zip,xlsx,xls'));
                
                //the extensions that the admin choose to use
                $values = explode(',',$setting->value);

                $new_values=collect();
                $setting['main_index']=true;

                //map every extension to see if it's choosen or not
                $all_create_extensions->map(function ($value) use ($new_values,$values){
                
                    $index = false;
                    if(in_array($value,$values)){
                       $index = true;
                    }

                    $new_values->push([
                        'name' => $value,
                        'index' => $index,
                        'type'=>$this->set->get_type($value)


                    ]);

                });
                if(in_array(false,$new_values->pluck('index')->toArray()))
                    $setting['main_index']=false;
                $new_values = $new_values->groupBy('type');

                $setting->value = $new_values;
            }

            if($setting->key == 'submit_assignment_extensions'){

                //all the extensions that our system support for the assignment submission
                $all_create_extensions = collect(explode(',','pdf,docs,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,mp4,avi,flv,mpeg,mpga,movie,mov,mp3,wav,amr'));

                //the extensions that the admin choose to use
                $values = explode(',',$setting->value);

                $new_values=collect();
                $setting['main_index']=true;

                //map every extension to see if it's choosen or not
                $all_create_extensions->map(function ($value) use ($new_values,$values){
                
                    $index = false;
                    if(in_array($value,$values)){
                       $index = true;
                    }

                    $new_values->push([
                        'name' => $value,
                        'index' => $index,
                        'type'=>$this->set->get_type($value)
                    ]);

                });
                if(in_array(false,$new_values->pluck('index')->toArray()))
                    $setting['main_index']=false;
                $new_values = $new_values->groupBy('type');

                $setting->value = $new_values;
            }

            if($setting->key == 'upload_file_extensions'){

                //all the extensions that our system support for the file upload
                $all_create_extensions = collect(explode(',','pdf,docx,doc,xls,xlsx,ppt,pptx,zip,rar,txt,TXT,odt,rtf,tex,wpd,rpm,z,ods,xlsm,pps,odp,7z,bdoc,cdoc,ddoc,gtar,tgz,gz,gzip,hqx,sit,tar,epub,gdoc,ott,oth,vtt,gslides,otp,pptm,potx,potm,ppam,ppsx,ppsm,pub,sxi,sti,csv,gsheet,ots,css,html,xhtml,htm,js,scss'));
                
                //the extensions that the admin choose to use
                $values = explode(',',$setting->value);

                $new_values=collect();
                $setting['main_index']=true;

                //map every extension to see if it's choosen or not
                $all_create_extensions->map(function ($value) use ($new_values,$values){
                
                    $index = false;
                    if(in_array($value,$values)){
                       $index = true;
                    }

                    $new_values->push([
                        'name' => $value,
                        'index' => $index,
                        'type'=>$this->set->get_type($value)

                    ]);

                });
                if(in_array(false,$new_values->pluck('index')->toArray()))
                    $setting['main_index']=false;
                $new_values = $new_values->groupBy('type');
                

                $setting->value = $new_values;
            }
            


            if($setting->key == 'upload_media_extensions'){

                //all the extensions that our system support for the media upload
                $all_create_extensions = collect(explode(',','mp4,avi,flv,mpga,ogg,ogv,oga,jpg,jpeg,png,gif,doc,mp3,wav,amr,mid,midi,mp2,aif,aiff,aifc,ram,rm,rpm,ra,rv,mpeg,mpe,qt,mov,movie,aac,au,flac,m3u,m4a,wma,ai,bmp,gdraw,ico,jpe,pct,pic,pict,svg,svgz,tif,tiff,3gp,dv,dif,f4v,m4v,mpg,rmvb,swf,swfl,webm,wmv,asf'));
                
                //the extensions that the admin choose to use
                $values = explode(',',$setting->value);

                $new_values=collect();
                $setting['main_index']=true;

                //map every extension to see if it's choosen or not
                $all_create_extensions->map(function ($value) use ($new_values,$values){
                
                    $index = false;
                    if(in_array($value,$values)){
                       $index = true;
                    }

                    $new_values->push([
                        'name' => $value,
                        'index' => $index,
                        'type'=>$this->set->get_type($value)

                    ]);

                });
                if(in_array(false,$new_values->pluck('index')->toArray()))
                    $setting['main_index']=false;
                $new_values = $new_values->groupBy('type');

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

    public function setLogo(Request $request)
    {
        $request->validate([
            'school_logo' => 'required|mimes:jpg,jpeg,png',
            'school_name' => 'required|string',
        ]);
        $check=attachment::where('type','Logo')->delete();
        // if($check)
        //     $check->delete();

        $attachment = attachment::upload_attachment($request->school_logo, 'Logo',null,$request->school_name);

        // return $attachment;
        return response()->json(['message' => __('messages.logo.set'), 'body' => $attachment], 200);
    }

    public function deleteLogo(Request $request)
    {
        $request->validate([
            'attachment_id' => 'required|exists:attachments,id',
        ]);
        $check=attachment::whereId($request->attachment_id)->first();
        if($check)
            $check->delete();

        return response()->json(['message' => __('messages.logo.delete'), 'body' => null], 200);
    }

    public function getLogo()
    {
        $attachment=attachment::where('type','Logo')->first();
        if(!$attachment)
            return response()->json(['message' => __('messages.logo.faild'), 'body' => null], 200);

        return response()->json(['message' => __('messages.logo.get'), 'body' => $attachment], 200);
    }

    public function updateLogo(Request $request)
    {
        $request->validate([
            'school_logo' => 'mimes:jpg,jpeg,png',
            'school_name' => 'required|string',
            'attachment_id' => 'required|exists:attachments,id'
        ]);
        $attachment=attachment::find($request->attachment_id);
        $attachment->description=$request->school_name;
        $attachment->save();

        if(isset($request->school_logo))
        {
            $check=attachment::where('type','Logo')->delete();
            // if($check)
            //     $check->delete();

            $attachment = attachment::upload_attachment($request->school_logo, 'Logo',null,$request->school_name);
        }

        // return $attachment;
        return response()->json(['message' => __('messages.logo.update'), 'body' => $attachment], 200);
    }
}
