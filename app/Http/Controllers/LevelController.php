<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Repositories\ChainRepositoryInterface;
use App\Level;
use App\Course;
use App\AcademicType;
use App\Classes;
use App\Exports\LevelsExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;

class LevelController extends Controller
{
    protected $chain;

    public function __construct(ChainRepositoryInterface $chain)
    {
        $this->chain = $chain;
        $this->middleware('auth');
        $this->middleware(['permission:course/layout'],   ['only' => ['index']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request,$status=null)
    {
        $request->validate([
            'years' => 'array',
            'years.*' => 'nullable|exists:academic_years,id',
            'types' => 'array',
            'types.*' => 'nullable|exists:academic_types,id',
            'search' => 'nullable',
            'filter' => 'in:all,export' //all without enroll  //export for exporting
        ]);
        $levels=Level::with('type.year')->whereNull('deleted_at');
        if($request->filled('search'))
            $levels->where('name', 'LIKE' , "%$request->search%");

        if($request->user()->can('site/show-all-courses'))
        {
            if(isset($request->types))
                $levels->whereIn('academic_type_id',$request->types)->with('type');

            return HelperController::api_response_format(201, $levels->paginate(HelperController::GetPaginate($request)), __('messages.level.list'));
        }

        $enrolls = $this->chain->getEnrollsByManyChain($request);
        $levels->whereIn('id',$enrolls->pluck('level'));  
        
        if($request->filled('search'))
            $levels=$levels->where('name', 'LIKE' , "%$request->search%");

        return HelperController::api_response_format(200, $levels->paginate(HelperController::GetPaginate($request)), __('messages.level.list'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'year' => 'array',
            'year.*' => 'exists:academic_years,id',
            'type' => 'array|required',
            'type.*' => 'exists:academic_types,id',
        ]);

        if ($request->filled('year') || $request->filled('type')) {
            foreach ($request->type as $type) {
                # code...
                $level = Level::firstOrCreate([
                    'name' => $request->name,
                    'academic_type_id' => $type
                ]);
            }
        }
        return HelperController::api_response_format(201, Level::paginate(HelperController::GetPaginate($request)), __('messages.level.add'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $level = Level::where('id', $id)->first();
        return HelperController::api_response_format(201, $level);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            // 'name' => 'required',
            // 'year' => 'exists:academic_years,id',
            'type' => 'exists:academic_types,id',
        ]);

        $level = Level::find($id);
        if(isset($request->name))
            $level->name = $request->name;
        $level->save();

        if ($request->filled('type'))
            Level::where('id',$id)->update(['academic_type_id' => $request->type]);
                
        return HelperController::api_response_format(200, Level::paginate(HelperController::GetPaginate($request)), __('messages.level.update'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id,Request $request)
    {
        $courses= Course::where('level_id',$id)->get();
        $classes = Classes::whereIn('level_id',$id)->get();
        if (count($courses) > 0 || count($classes) > 0)
            return HelperController::api_response_format(200, [] , __('messages.error.cannot_delete'));

        Level::whereId($id)->first()->delete(); //it's not mass delete

        return HelperController::api_response_format(200, Level::paginate(HelperController::GetPaginate($request)), __('messages.level.delete'));
    }
}
