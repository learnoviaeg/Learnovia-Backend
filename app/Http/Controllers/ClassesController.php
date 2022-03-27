<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Repositories\ChainRepositoryInterface;
use App\SegmentClass;
use App\Classes;
use App\Level;
use App\Enroll;
use App\Course;

class ClassesController extends Controller
{
    protected $chain;

    /**
     * ChainController constructor.
     *
     * @param ChainRepositoryInterface $post
     */
    public function __construct(ChainRepositoryInterface $chain)
    {
        $this->chain = $chain;
        $this->middleware('auth');
        $this->middleware(['permission:course/layout' , 'ParentCheck'],   ['only' => ['index']]);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request,$option = null)
    {
        //validate the request
        $request->validate([
            'years'  => 'array',
            'years.*'  => 'nullable|exists:academic_years,id',
            'types'  => 'array|required_with:levels',
            'types.*'  => 'nullable|exists:academic_types,id',
            'levels' => 'array|required_with:types',
            'levels.*' => 'exists:levels,id',
            'courses'    => 'nullable|array',
            'courses.*'  => 'nullable|integer|exists:courses,id',
            'filter' => 'in:all,export' //all without enroll  //export for exporting
        ]);

        $classes=Classes::with('level.type.year')->where('type','class')->whereNull('deleted_at');
        if($request->filled('search'))
            $classes->where('name', 'LIKE' , "%$request->search%"); 

        $enrolls = $this->chain->getEnrollsByManyChain($request);

        if(!$request->has('user_id'))
            $enrolls->where('user_id',Auth::id());
            
        $classes->where('type','class')->whereIn('id',$enrolls->pluck('group'));

        return HelperController::api_response_format(201, $classes->paginate(HelperController::GetPaginate($request)), __('messages.class.list'));
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
            'name'  => 'required',
            'year'=>'array|required_with:type|required_with:level',
            'year.*'=>'exists:academic_years,id',
            'type'=>'array|required_with:year',
            'type.*'=> 'exists:academic_types,id',
            'level'=>'array|required_with:year',
            'level.*'=> 'exists:levels,id',
        ]);

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
        return HelperController::api_response_format(200, Classes::with('level')->get()->paginate(HelperController::GetPaginate($request)), __('messages.class.add'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $class = Classes::where('id', $id)->first();
        return HelperController::api_response_format(201, $class);
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
            'name' => 'required',
            'level_id' => 'exists:levels,id|required_with:year',
        ]);

        $class = Classes::find($id);
        $class->update($request->all());
        $class->save();

        return HelperController::api_response_format(200, Classes::get()->paginate(HelperController::GetPaginate($request)), __('messages.class.update'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id,Request $request)
    {
        $check = Enroll::where('group',$id)->get();
        if (count($check) > 0) 
            return HelperController::api_response_format(200, [] , __('messages.error.cannot_delete'));
        
        Classes::whereId($id)->first()->delete();

        return HelperController::api_response_format(200, Classes::get()->paginate(HelperController::GetPaginate($request)), __('messages.class.delete'));
    }
}
