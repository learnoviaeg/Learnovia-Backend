<?php

namespace App\Http\Controllers;

use App\AcademicYearType;
use App\YearLevel;
use Illuminate\Http\Request;
use App\Classes;
use App\CourseSegment;
use App\User;
use App\ClassLevel;
use App\Enroll;
use Carbon\Carbon;
use Auth;
use App\Http\Resources\Classes as Classs;
use Validator;
use App\AcademicYear;
class ClassController extends Controller
{
  /*
    * @Description :list all classes or select a class by id.
    * @param :year, type, level of class as required parameters
              id of class as an optional parameter.
    * @return : returns all classes or a selected class.
    */
    public function index(Request $request)
    {
        $request->validate([
            'year'  => 'required|exists:academic_years,id',
            'type'  => 'required|exists:academic_types,id',
            'level' => 'required|exists:levels,id',
            'id' => 'exists:classes,id'
        ]);
        if($request->id == null)
        {
            $yeartype = AcademicYearType::checkRelation($request->year , $request->type);
            $yearlevel = YearLevel::checkRelation($yeartype->id , $request->level);
            $class =collect([]);
            foreach ($yearlevel->classLevels as $classLevel){
                $class[] = $classLevel->classes[0];
            }
            return HelperController::api_response_format(200, $class->paginate(HelperController::GetPaginate($request)));
        }
        else
        {
            $class = Classes::find($request->id);
            if ($class)
                return HelperController::api_response_format(200, new Classes($class->paginate(HelperController::GetPaginate($request))));
            return HelperController::NOTFOUND();
        }

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
        ]);
        $class = new Classes;
        $class->name = $request->name;
        $class->save();
        return HelperController::api_response_format(200, new Classs($class), 'Class Created Successfully');
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

        $class = Classes::firstOrCreate([
            'name' => $request->name,
        ]);
        if($request->filled('year')&&$request->filled('type')&&$request->filled('level')){
            foreach ($request->year as $year) {
                # code...
                foreach ($request->type as $type) {
                    # code...
                    $yeartype = AcademicYearType::checkRelation($year , $type);
                    foreach ($request->level as $level) {
                        # code...
                        $yearlevel = YearLevel::checkRelation($yeartype->id , $level);
                        ClassLevel::firstOrCreate([
                            'year_level_id' => $yearlevel->id,
                            'class_id' => $class->id
                        ]);
                    }
                }
            }

        }
        return HelperController::api_response_format(200, Classes::get()->paginate(HelperController::GetPaginate($request)), 'Class Created Successfully');
    }

    /**
     * @Description :list all classes or select one by id or a filter .
     * @param : id of classes or search as an optional parameter.
     * @return : returns all classes or filtered ones or a class selected by id.
     */
    public function show(Request $request)
    {
        if($request->id == null)
        {
            $request->validate([
                'search' => 'nullable'
            ]);
            if($request->filled('search'))
            {
                $Classes = Classes::where('name', 'LIKE' , "%$request->search%")->get()
                ->paginate(HelperController::GetPaginate($request));
                return HelperController::api_response_format(202, $Classes);
            }
            $classes = Classes::paginate(HelperController::GetPaginate($request));
            return HelperController::api_response_format(200,$classes);
        }
        else
        {
            $request->validate([
                'id' => 'exists:classes,id',
            ]);
            $class = Classes::find($request->id);
            return HelperController::api_response_format(200,$class);

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
        ]);

        if ($valid->fails())
            return HelperController::api_response_format(400 , $valid->errors() , 'Something went wrong');
        $class = Classes::find($request->id);
        $class->update($request->all());
        return HelperController::api_response_format(200, Classes::get()->paginate(HelperController::GetPaginate($request)));
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
        $class->delete();
        return HelperController::api_response_format(200, Classes::get()->paginate(HelperController::GetPaginate($request)), 'Class Deleted Successfully');
    }
    /**
     * @Description :assigns a class to certain year, type and level.
     * @param : year, type, level and class.
     * @return : A string message which indicates if class assigned successfully or not.
     */
    public function Assign_class_to(Request $request)
    {
        $rules =[
            'year' => 'array',
            'year.*' => 'exists:academic_years,id',
            'type' => 'required|array',
            'type.*' => 'required|exists:academic_types,id',
            'level' => 'required|array',
            'level.*' => 'required|exists:levels,id',
            'class' => 'required|exists:classes,id'
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails())
            return ['result' => false, 'value' => $validator->errors()];

        $count=0;
        if(count($request->type) == count ($request->level))
        {
            while(isset($request->type[$count]))
                {
                    if (isset($request->year[$count])) {
                        $year = $request->year[$count];
                    }
                    else
                    {
                        $year = AcademicYear::Get_current();
                        if(!isset($year))
                            return HelperController::api_response_format(201, 'there is no current year');
                        else
                            $year=$year->id;
                    }
                    $academic_year_type = AcademicYearType::checkRelation($year, $request->type[$count]);
                    $Year_level=YearLevel::checkRelation($academic_year_type->id, $request->level[$count]);
                    $Class_level=ClassLevel::checkRelation($request->class, $Year_level->id);
                    $count++;
                }
        }
        else {
             return HelperController::api_response_format(201, 'Arrays must have same length');
        }
        return HelperController::api_response_format(201, 'Class Assigned Successfully');
    }

    public function get_lessons_of_class(Request $request){
        $request->validate([
            'class'    => 'required|integer|exists:classes,id',
        ]);
        $lessons = CourseSegment::GetWithClass($request->class)->lessons;
        return HelperController::api_response_format(200, $lessons,'Lessons are ....');
    }

    public function GetMyclasses(Request $request)
    {
        $request->validate([
            'type' => 'array',
            'type.*' => 'exists:academic_types,id',
            'level' => 'array',
            'level.*' => 'exists:levels,id',
        ]);
        $result=array();
        $class=array();
        $users = User::whereId(Auth::id())->with(['enroll.courseSegment' => function($query){
            //validate that course in my current course start < now && now < end
            $query->where('end_date', '>', Carbon::now())->where('start_date' , '<' , Carbon::now());
        },'enroll.courseSegment.segmentClasses.classLevel.yearLevels' => function($query) use ($request){
            if ($request->filled('level'))
                $query->whereIn('level_id', $request->level);
        },'enroll.courseSegment.segmentClasses.classLevel.yearLevels.yearType' => function($query) use ($request){
            if ($request->filled('type'))
                $query->whereIn('academic_type_id', $request->type);            
        }])->first();
        foreach($users ->enroll as $enrolls)
        if(isset($enrolls->courseSegment->segmentClasses)){
            foreach($enrolls->courseSegment->segmentClasses as $segmetClas)
                foreach($segmetClas->classLevel as $clas)
                    if(isset($clas->yearLevels))
                        foreach($clas->yearLevels as $level)
                            if(count($level->yearType) > 0)
                                if(!in_array($clas->class_id, $result))
                                {
                                    $result[]=$clas->class_id;
                                    $class[]=Classes::find($clas->class_id);
                                }
                            }
        if(count($class) > 0)
            return HelperController::api_response_format(201,$class, 'There are your Classes');
        
        return HelperController::api_response_format(201, 'You haven\'t Classes');
    }
}
