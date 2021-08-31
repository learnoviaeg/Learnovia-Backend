<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Repositories\ChainRepositoryInterface;
use App\SegmentClass;
use App\Classes;

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
            'types'  => 'array',
            'types.*'  => 'nullable|exists:academic_types,id',
            'levels' => 'array',
            'levels.*' => 'exists:levels,id',
            // 'courses'    => 'nullable|array',
            // 'courses.*'  => 'nullable|integer|exists:courses,id',
            'filter' => 'in:all,export' //all without enroll  //export for exporting
        ]);

        $enrolls = $this->chain->getEnrollsByManyChain($request);
        $classes=Classes::with('level')->where('type','class')->whereIn('id',$enrolls->pluck('group')); 

        if($request->filter == 'all')
        {
            $classes=Classes::with('level')->where('type','class')->whereNull('deleted_at');
            return HelperController::api_response_format(201, $classes->paginate(HelperController::GetPaginate($request)), __('messages.class.list'));
        }

        if($request->filled('search'))
            $classes->where('name', 'LIKE' , "%$request->search%"); 

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
        //
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
        //
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
