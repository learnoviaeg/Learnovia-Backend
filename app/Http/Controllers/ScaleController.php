<?php

namespace App\Http\Controllers;

use App\ScaleDetails;
use Illuminate\Http\Request;
use App\scale;
use stdClass;
use App\course_scales;
use App\GradeCategory;
use App\Course;
use App\Repositories\ChainRepositoryInterface;

class ScaleController extends Controller
{

    public function __construct(ChainRepositoryInterface $chain)
    {
        $this->chain = $chain;
        $this->middleware('auth');
        $this->middleware(['permission:grade/scale/get'],   ['only' => ['index','show']]);
        $this->middleware(['permission:grade/scale/add'],   ['only' => ['store']]);
        $this->middleware(['permission:grade/scale/update'],   ['only' => ['update']]);
        $this->middleware(['permission:grade/scale/course'],   ['only' => ['scales_per_course']]);
        $this->middleware(['permission:grade/scale/delete'],   ['only' => ['destroy']]);
    }


    public function index(Request $request)
    {
        $scale = scale::with(['details'])->get();
        return response()->json(['message' => __('messages.scale.list'), 'body' => $scale], 200);
    }
   
    public function store(Request $request)
    {
        $request->validate([
            'courses'    => 'nullable|array',
            'courses.*'  => 'nullable|integer|exists:courses,id',
            'levels'    => 'nullable|array',
            'levels.*'  => 'nullable|integer|exists:levels,id',
            'years' => 'array',
            'years.*' => 'exists:academic_years,id',
            'types' => 'array',
            'types.*' => 'exists:academic_types,id',
            'name' => 'required',
            'scale' => 'required|array',
            'scale.*evaluation'=> 'required|string',
        ]);
        
        $courses = $this->chain->getEnrollsByManyChain($request)->where('role_id',1)->distinct('course')->select('course')->pluck('course');
        $scale = scale::firstOrCreate([
                    'name' => $request->name,
                    'chain' => json_encode($request->except(['name', 'scale'])),
                ]);
        
        foreach($request->scale as $key => $scale_details)
        {
            $scale->details()->create([
                'evaluation' => $scale_details['evaluation'],
                'grade' => $key+1,        
            ]);
        }
        foreach($courses as $course)
        {
            course_scales::firstOrCreate([
                'course_id' => $course,
                'scale_id' => $scale->id,
            ]);
        }

        return response()->json(['message' => __('messages.scale.list'), 'body' => $scale], 200);
    }

    public function update(Request $request , $id)
    {
        $request->validate([
            'courses'    => 'nullable|array',
            'courses.*'  => 'nullable|integer|exists:courses,id',
            'levels'    => 'nullable|array',
            'levels.*'  => 'nullable|integer|exists:levels,id',
            'years' => 'array',
            'years.*' => 'exists:academic_years,id',
            'types' => 'array',
            'types.*' => 'exists:academic_types,id',
            'name' => 'nullable',
            'scale' => 'required|array',
            'scale.*evaluation'=> 'required|string',
            ]);

        $scale=scale::find($id);
        if($request->filled('name'))
            $scale->update(['name' => $request->name]);
        

        $check = GradeCategory::where('scale_id',$id)->count();

        if($check > 0)
            return response()->json(['message' => __('messages.scale.cannot_update'), 'body' => null], 200);
 
        $scale->details()->delete();
        course_scales::where('scale_id' , $id)->delete();

        foreach($request->scale as $key => $scale_details)
        {
            $scale->details()->create([
                'evaluation' => $scale_details['evaluation'],
                'grade' => $key+1,        
            ]);
        }

        $courses = $this->chain->getEnrollsByManyChain($request)->where('role_id',1)->distinct('course')->select('course')->pluck('course');
        $scale->update(['chain' => json_encode($request->except(['name', 'scale']))]);

        foreach($courses as $course)
        {
            course_scales::firstOrCreate([
                'course_id' => $course,
                'scale_id' => $scale->id,
            ]);
        }
        return response()->json(['message' => __('messages.scale.update'), 'body' => null], 200);
    }
    
    public function show($id)
    {
        $scale = scale::where('id',$id)->with(['details'])->first();
        return response()->json(['message' => __('messages.scale.list'), 'body' => $scale], 200);
    }

    public function scales_per_course(Request $request)
    {
        $request->validate([
            'course_id' => 'required|integer|exists:courses,id',
        ]);
        $scales = Course::whereId($request->course_id)->with(['Scale.Scale'])->first();
        return response()->json(['message' => __('messages.scale.course'), 'body' => $scales], 200);
    }

    public function destroy($id)
    {
        $scale = scale::find($id);
        $check = GradeCategory::where('scale_id',$id)->count();

        if($check > 0)
            return response()->json(['message' => 'messages.scale.cannot_delete ', 'body' => null], 200);   

        $scale->course_scale()->delete();
        $scale->delete();
        return response()->json(['message' => __('messages.scale.delete'), 'body' => null], 200);
    }

}
