<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Storage;
use Nwidart\Modules\Collection;
use App\Events\MassLogsEvent;
use App\AcademicType;
use App\AcademicYear;
use App\Level;
use App\Enroll;
use App\Segment;  
use App\User;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Resources\Year_type_resource;
use Validator;
use App\Exports\TypesExport;
use Maatwebsite\Excel\Facades\Excel;
use App\YearLevel;
use App\SystemSetting;
class AC_year_type extends Controller
{
    /**
     * @Description: Get all Years with its types
     * @param: no take parameters
     * @return : response of all Years with its Typs
     *
     */
    public function List_Years_with_types(Request $request)
    {
        $request->validate([
            'year' => 'exists:academic_years,id',
            'dropdown' => 'boolean'
        ]);

        if($request->id != null)
        {
            $request->validate([
                'id' => 'exists:academic_types,id'
            ]);
            $types = AcademicType::where('id',$request->id)->first();
            return HelperController::api_response_format(200, $types);
        }
        else {
            // $cat = AcademicYear::whereId($request->year)->first()->AC_Type->pluck('id');
            // $types = AcademicType::with('yearType.academicyear')->whereIn('id',$cat);     

            $types = AcademicType::query();     
            
            if($request->has('year')){
                $types->where('academic_year_id',$request->year);
            }
            // if(isset($request->dropdown) && $request->dropdown == true)       
            //     return HelperController::api_response_format(200, $types->get());
            // else
            return HelperController::api_response_format(200, $types->get()->paginate(HelperController::GetPaginate($request)));
        }
    }

    public function get(Request $request,$call=0){

        $request->validate([
            'search' => 'nullable',
            'years' => 'array',
            'years.*' => 'exists:academic_years,id',
        ]);
    
        $all_types = AcademicType::whereNull('deleted_at');
        if(isset($request->years))
            $all_types->whereIn('academic_year_id',$request->years);
        if(isset($request->search))
            $all_types->where('name', 'LIKE' , "%$request->search%");

        if($call==1)
            return $all_types->get();

        return HelperController::api_response_format(200, $all_types->paginate(HelperController::GetPaginate($request)));
    }

    /**
     * @Description:Remove type
     * @param: request to access id of the type
     * @return : MSG 'Type Deleted Successfully' if deleted
     *          if not : return 'Type Deleted Fail'
     *
     **/
    public function deleteType(Request $req)
    {
        $req->validate([
            'id' => 'required|exists:academic_types,id'
        ]);

        $segment= Segment::where('academic_type_id',$req->id)->get();
        $level= Level::where('academic_type_id',$req->id)->get();
        if((count($segment) > 0 || count($level) > 0))
            return HelperController::api_response_format(404, [], __('messages.error.cannot_delete'));
        
        AcademicType::whereId($req->id)->first()->delete();

        return HelperController::api_response_format(201, AcademicType::paginate(HelperController::GetPaginate($req)), __('messages.type.delete'));
    }

    /**
     *
     * @Description : add type "Like National and its NUM of terms " to specific year
     * @param : Request to Access id of Year , name of Type and its segment no
     * @return : if addition succeeded ->  return all Years with its Type
     *           if not -> return MSG: 'Type insertion Fail'
     *
     * ``
     */
    public function Add_type_to_Year(Request $req)
    {
        $req->validate([
            'name' => 'required',
            'segment_no' => 'required'
        ]);

        AcademicType::firstOrCreate([
            'name' => $req->name,
            'segment_no' => $req->segment_no
        ]);

        return HelperController::api_response_format(201, AcademicType::paginate(HelperController::GetPaginate($req)), __('messages.type.add'));
    }

    /**
     *
     * @Description : update specific Type
     * @param : Request to access  id ,  new name or new segment_no of this Type
     * @return :  if modify succeeded ->  return all Years with its Type
     *            if not -> return MSG: 'Something went worng'
     *
     */
    public function updateType(Request $req)
    {
        $req->validate([
            'name' => 'required',
            'segment_no' => 'required',
            'id' => 'required|exists:academic_types,id',
            // 'year' => 'exists:academic_years,id',
        ]);

        $AC = AcademicType::Find($req->id);
        // if (!$AC) 
        //     return HelperController::api_response_format(404, [], __('messages.error.not_found'));

        $AC->update($req->all());
        // if ($req->filled('year'))
        //     $yearType = AcademicYearType::where('academic_type_id', $AC->id)->update(['academic_year_id' => $req->year]);
        
        $AC->save();
        // if ($AC) {
        //     $AC->AC_year;
        //     $output= AcademicType::paginate(HelperController::GetPaginate($req));
        //     $req['returnmsg'] = 'update';
        //     $print = self::get($req);
        //     return $print;
        //     // return HelperController::api_response_format(200, $output, 'Type edited successfully');
        // }
        return HelperController::api_response_format(200, AcademicType::paginate(HelperController::GetPaginate($req)),__('messages.type.update'));

        // return HelperController::api_response_format(400, [], __('messages.error.try_again'));
    }

    /**
     * @Description :assign specific Type to specific Year
     * @param : request to access id_type of Type and id_year of year
     * @return : if Assignment succeeded ->  return all Years with its Type
     *           if not -> return MSG 'Assignment Fail'
     *
     */
    public function Assign_to_anther_year(Request $req)
    {
        $valid = Validator::make($req->all(), [
            'id_type' => 'required|exists:academic_types,id',
            'id_year'=>'required|array',
            'id_year.*' => 'required|exists:academic_years,id'
        ]);
        if ($valid->fails()) {
            return HelperController::api_response_format(400, $valid->errors(), __('messages.error.try_again'));
        }
        $counter=0;
        while(isset($req->id_year[$counter]))
        {
            $academic_year_type=AcademicYearType::checkRelation($req->id_year[$counter],$req->id_type);
            $counter++;
        }
         return HelperController::api_response_format(200, 'Academic Year-Type Relation Created Succssesfully');
    }

    public function GetMytypes(Request $request)
    {
        $types = AcademicType::whereNull('deleted_at');

        $currentSegment=Segment::where('start_date', '<=',Carbon::now())
                            ->where('end_date','>=',Carbon::now())->pluck('academic_type_id');
        $myTypes=Enroll::where('user_id',Auth::id())->whereIn('type',$currentSegment)->pluck('type');
        $types->whereIn('id',$myTypes);
        // return HelperController::api_response_format(200, $types->paginate(HelperController::GetPaginate($request)),__('messages.type.list'));
        return HelperController::api_response_format(201,$types->get(), __('messages.type.list'));
    }
    
    public function export(Request $request)
    {
        // return Excel::download(new TypesExport, 'types.xls');
        $typeIDs = self::get($request,1);
        
        $filename = uniqid();
        $file = Excel::store(new TypesExport($typeIDs), 'Type'.$filename.'.xlsx','public');
        $file = url(Storage::url('Type'.$filename.'.xlsx'));
        return HelperController::api_response_format(201,$file, __('messages.success.link_to_file'));
    }

    public function uploadsTrys(Request $request)
    {
        $request->validate([
            'content' => 'required',
            // 'id' => 'exists:.....,id',
        ]);
  
        if(!$request->filled('id')){            
            $setting  = SystemSetting::create([
                'key' => uniqid(),
                'data'=> json_encode($request->content),
            ]);
        }

        if($request->filled('id')){
            $setting = SystemSetting::whereId($request->id)->first();
            $array = json_decode($setting->content) ;
            $setting->data =  json_encode($setting->data.$request->content);
            $setting->save();
        }

        if($request->filled('last') && $request->last == 1){
            $base64_encoded_string = base64_decode(json_decode($setting->data));
            $extension = finfo_buffer(finfo_open(), $base64_encoded_string, FILEINFO_MIME_TYPE);
            $ext = substr($extension,strrpos($extension,"/")+1);
            Storage::append($setting->key.$ext ,$base64_encoded_string);
        }


        // $extension = finfo_buffer(finfo_open(), $base64_encoded_string, FILEINFO_MIME_TYPE);

        // $path = public_path() . "/img/designs/" . $png_url;
        // $success = file_put_contents($path, $base64_encoded_string);

        // Storage::disk('public')->putFileAs('assignment', $base64_encoded_string, 'salooka.png');

        // return $extension;



        // $file = $folderPath . uniqid() . '.png';

        // Storage::put('salooka.png', $base64_encoded_string);

        // $b64image = base64_encode(file_get_contents( url(Storage::url('assignment/'.$name))));

        // Storage::disk('public')->putFileAs($folderPath, $base64_encoded_string, 'salooka.png');


        // file_put_contents($file, $base64_encoded_string);



        return 'done';

        if($request->filled('id')){
            $setting = SystemSetting::whereId($request->id)->first();
            $b64image = base64_decode(file_get_contents( url(Storage::url('assignment/'.$name))));

        Storage::disk('public')->putFileAs('hamada', $b64image, 'salooka.png');

            

            $img64 = 
        $array = json_decode($setting->data) ;
            $setting->data = json_encode(array_merge($array , $request->array));
            $setting->save();
        return $setting;
        }

        // SystemSetting::firstOrCreate([
        //         'key' => 'test',
        //         'data'=> json_encode($request->array)
        // ]);


    }


    public function uploads(Request $request)
    {
        $request->validate([
            'content' => 'required',
            // 'id' => 'exists:.....,id',
        ]);
  
        if(!$request->filled('id')){            
            $setting  = SystemSetting::create([
                'key' => uniqid(),
                'data'=> $request->content,
            ]);
        }

        if($request->filled('id')){
            $setting = SystemSetting::whereId($request->id)->first();
            $array = ($setting->content) ;
            $setting->data =  ($setting->data.$request->content);
            $setting->save();
        }

        if($request->filled('last') && $request->last == 1){
            $base64_encoded_string = base64_decode(($setting->data));
            $extension = finfo_buffer(finfo_open(), $base64_encoded_string, FILEINFO_MIME_TYPE);
            $ext = substr($extension,strrpos($extension,"/")+1);
            Storage::append($setting->key.'.'.$ext ,$base64_encoded_string);
            
            return response()->json(['message' =>null, 'body' => url(Storage::url($setting->key.'.'.$ext)) ], 200);
        }
        return response()->json(['message' =>null, 'body' => $setting ], 200);

    }


}
