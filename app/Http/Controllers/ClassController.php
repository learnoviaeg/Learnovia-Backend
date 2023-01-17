<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use App\Events\MassLogsEvent;
use App\AcademicYearType;
use App\YearLevel;
use App\Level;
use App\SegmentClass;
use Illuminate\Http\Request;
use App\Classes;
use App\Segment;
use App\User;
use App\ClassLevel;
use App\Enroll;
use App\Lesson;
use Carbon\Carbon;
use Auth;
use App\Http\Resources\Classes as Classs;
use Validator;
use App\AcademicType;
use App\AcademicYear;
use App\Exports\ClassesExport;
use Maatwebsite\Excel\Facades\Excel;

class ClassController extends Controller
{
  /*
    * @Description :list all classes or select a class by id.
    * @param :year, type, level of class as required parameters
              id of class as an optional parameter.
    * @return : returns all classes or a selected class.
    */
    public function index(Request $request ,$call = 0)
    {
        $request->validate([
            'years'  => 'array',
            'years.*'  => 'nullable|exists:academic_years,id',
            'types'  => 'array',
            'types.*'  => 'nullable|exists:academic_types,id',
            'levels' => 'array',
            'levels.*' => 'nullable|exists:levels,id',
            'id' => 'exists:classes,id',
            'search' => 'nullable'
        ]);
        
        if($request->filled('id')){
            $class = Classes::find($request->id);
            if ($class)
                return HelperController::api_response_format(200, $class);
            return HelperController::NOTFOUND();
        }

        $classes = Classes::with('level')->whereNull('deleted_at');
        
        if(isset($request->types))
            $classes->whereIn('level_id',Level::where('academic_type_id',$request->types)->pluck('id'));

        if(isset($request->levels))
            $classes->whereIn('level_id',$request->levels);

        if($request->filled('search'))
            $classes = $classes->where('name', 'LIKE' , "%$request->search%");

        $classes = $classes->get();
        // $all_classes=collect([]);
        // foreach($classes as $class){ 
        //     $levels_id= $class->level->pluck('id')->collapse()->unique();
        //     // $class['levels']= Level::whereIn('id',$levels_id)->pluck('name');
        //     // $academic_year_id= array_values( $class->classlevel->pluck('yearLevels.*.yearType.*.academic_year_id')->collapse()->unique()->toArray());
        //     // $class['academicYear']= AcademicYear::whereIn('id',$academic_year_id)->pluck('name');
        //     // $academic_type_id = array_values($class->classlevel->pluck('yearLevels.*.yearType.*.academic_type_id')->collapse()->unique()->toArray());
        //     // $class['academicType']= AcademicType::whereIn('id',$academic_type_id)->pluck('name');
        //     // unset($class->classlevel);
        //     $all_classes->push($class);
        // }

        if($call == 1){
            $classesIds = $classes->pluck('id');
            return $classesIds;
        }
        return HelperController::api_response_format(200, $classes->paginate(HelperController::GetPaginate($request)));

    }

   /*
    * @Description :creates a new class.
    * @param :name of class as required parameters.
    * @return : returns the created class.
    */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'level' => 'required',
        ]);
        $class = new Classes;
        $class->name = $request->name;
        $class->level_id = $request->level;
        $class->save();
        return HelperController::api_response_format(200, new Classs($class), __('messages.class.add'));
    }
   /*
    * @Description :creates a new class to a given year, type and level.
    * @param :name of class as required parameters.
              year, level and type are optional parameters but required to each other.
    * @return : returns all the classes.
    */
    public function AddClassWithYear(Request $request)
    {
        $request->validate([
            'name'  => 'required',
            'year'=>'array|required_with:type|required_with:level',
            'year.*'=>'exists:academic_years,id',
            'type'=>'array|required_with:year',
            'type.*'=> 'exists:academic_types,id',
            'level'=>'array|required_with:year',
            'level.*'=> 'exists:levels,id',
        ]);

        // $class = Classes::create([
        //     'name' => $request->name,
        // ]);
        if($request->filled('year')&&$request->filled('type')&&$request->filled('level')){
            foreach ($request->year as $year) {
                # code...
                foreach ($request->type as $type) {
                    # code...
                    foreach ($request->level as $level) {
                        # code...
                        $class = Classes::create([
                            'name' => $request->name,
                            'level_id' => $level
                        ]);
                    }
                }
            }

        }
        return HelperController::api_response_format(200, Classes::get()->paginate(HelperController::GetPaginate($request)), __('messages.class.add'));
    }

    /**
     * @Description :list all classes or select one by id or a filter .
     * @param : id of classes or search as an optional parameter.
     * @return : returns all classes or filtered ones or a class selected by id.
     */
    public function show(Request $request ,$call = 0)
    {
        $request->validate([
            'year'  => 'required|exists:academic_years,id',
            'type'  => 'required|exists:academic_types,id',
            'level' => 'required|exists:levels,id',
            'id' => 'exists:classes,id'
        ]);
        if($request->id == null)
        {
            $Classes = Classes::whereNull('deleted_at')
            ->whereHas('level', function($q)use ($request){ 
                    if ($request->has('level')) 
                        $q->where('level_id',$request->level);
            })->get();
            // ->whereHas('classlevel.yearLevels.yearType' , function($q)use ($request){ 
            //     if ($request->has('year'))
            //         $q->where('academic_year_id',$request->year);
            //     if ($request->has('type'))
            //         $q->where('academic_type_id',$request->type);
            // })->get();

            $all_classes=collect([]);
            foreach($Classes as $class){ 
                $levels_id= $class->level->pluck('id')->collapse()->unique();
                // $class['levels']= Level::whereIn('id',$levels_id)->pluck('name');
                // $academic_year_id= array_values( $class->classlevel->pluck('yearLevels.*.yearType.*.academic_year_id')->collapse()->unique()->toArray());
                // $class['academicYear']= AcademicYear::whereIn('id',$academic_year_id)->pluck('name');
                // $academic_type_id = array_values($class->classlevel->pluck('yearLevels.*.yearType.*.academic_type_id')->collapse()->unique()->toArray());
                // $class['academicType']= AcademicType::whereIn('id',$academic_type_id)->pluck('name');
                // unset($class->classlevel);
                $all_classes->push($class);
            }
            return HelperController::api_response_format(200, $all_classes->paginate(HelperController::GetPaginate($request)));
        }
        else
        {
            $class = Classes::find($request->id);
            if ($class)
                return HelperController::api_response_format(200, $class);
            return HelperController::NOTFOUND();
        }
    }

    /**
     * @Description :update a class .
     * @param : id and new name of class.
     * @return : returns all classes .
     */
    public function update(Request $request)
    {
        $valid = Validator::make($request->all(), [
            'name' => 'required',
            'id' => 'required|exists:classes,id',
            'year' => 'exists:academic_years,id',
            'type' => 'exists:academic_types,id|required_with:year',
            'level' => 'exists:levels,id|required_with:year',
        ]);

        if ($valid->fails())
            return HelperController::api_response_format(400 , $valid->errors() , __('messages.error.try_again'));
        $class = Classes::find($request->id);
        $class->update($request->all());
        $class->save();
        if ($request->filled('year'))
        {
            $year_type= AcademicYearType::where('academic_year_id',$request->year)->where('academic_type_id',$request->type)->first();
            $year_level=YearLevel::where('level_id',$request->level)->where('academic_year_type_id',$year_type->id)->first();
            ClassLevel::where('class_id',$request->id)->update(['year_level_id' => $year_level->id]);
        }
        return HelperController::api_response_format(200, Classes::get()->paginate(HelperController::GetPaginate($request)), __('messages.class.update'));
    }

    /**
     * @Description :delete a class .
     * @param : id of class.
     * @return : returns all classes .
     */
    public function destroy(Request $request)
    {
        $request->validate(['id' => 'required|exists:classes,id']);
        $class = Classes::find($request->id);
        $Segment_class = SegmentClass::whereIn("class_level_id",ClassLevel::where('class_id',$request->id)->pluck('id'))->get();
        
        if(count($Segment_class)>0)
            return HelperController::api_response_format(400, [], __('messages.error.cannot_delete'));
        
        ClassLevel::where('class_id',$request->id)->first()->delete();
       
        //for log event
        $logsbefore=User::where('class_id',$request->id)->get();
        $returnValue=User::where('class_id',$request->id)->update(["class_id"=>null]);
        // if($returnValue > 0)
        //     event(new MassLogsEvent($logsbefore,'updated'));
       
        //for log event
        $logsbefore=Enroll::where('class',$request->id)->get();
        $returnValue=Enroll::where('class',$request->id)->update(["class"=>null]);
        // if($returnValue > 0)
        //     event(new MassLogsEvent($logsbefore,'updated'));
        
        $class->delete();
        return HelperController::api_response_format(200, Classes::get()->paginate(HelperController::GetPaginate($request)), __('messages.class.delete'));
    }

    public function get_lessons_of_class(Request $request){
        $request->validate([
            'course'    => 'required|integer|exists:courses,id',
        ]);
        $lessons = Lesson::where('course_id',$request->course)->get();
        return HelperController::api_response_format(200, $lessons,__('messages.lesson.list'));
    }

    public function export(Request $request)
    {
        $classesIDs = self::index($request,1);
        $filename = uniqid();
        $file = Excel::store(new ClassesExport($classesIDs), 'Class'.$filename.'.xlsx','public');
        $file = url(Storage::url('Class'.$filename.'.xlsx'));
        return HelperController::api_response_format(201,$file, __('messages.success.link_to_file'));
    }
}
