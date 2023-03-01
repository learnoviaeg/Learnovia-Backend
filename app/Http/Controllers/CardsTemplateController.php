<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\UserGrader;
use App\User;
use Auth;
use App\Repositories\ChainRepositoryInterface;


class CardsTemplateController extends Controller
{
    public function __construct(ChainRepositoryInterface $chain)
    {
        $this->chain = $chain;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $request->validate([
            // 'month'   => 'required|in:October,November,December',
            'years'    => 'nullable|array',
            'years.*' => 'exists:academic_years,id',
            'types'    => 'nullable|array',
            'types.*' => 'exists:academic_types,id',
            'levels'    => 'nullable|array',
            'levels.*' => 'exists:levels,id',
            'trimester' => 'required|in:T2,T3'
        ]);
        $result_collection = collect([]);
        $user_ids = $this->chain->getEnrollsByManyChain($request)->distinct('user_id')->pluck('user_id');

        $grade_CatsT2=['T2-Quiz','T2-Homework','T2-Assignment','T2-Classwork','T2-Project'];
        $grade_CatsT3=['T3-Quiz','T3-Homework','T3-Assignment','T3-Classwork','T3-Project'];

        foreach($user_ids as $user_id){
            $GLOBALS['user_id'] = $user_id;
            $grade_category_callback = function ($qu) use ($user_id , $request) {
                $qu->where('name','LIKE',"%$request->trimester%");
                $qu->where('name','NOT LIKE',"%Total coursework%");
                $qu->where('name','NOT LIKE',"%Trimester exam%");
                $qu->with(['userGrades' => function($query) use ($user_id , $request){
                    $query->where("user_id", $user_id);
                }
            ]); 
            };

            $callback = function ($qu) use ($request ,$grade_category_callback) {
                $qu->where('role_id', 3);
                $qu->whereHas('courses.gradeCategory' , $grade_category_callback)
                    ->with(['courses.gradeCategory' => $grade_category_callback]); 
            };
            $result = User::select('id','username','lastname', 'firstname')->whereId($user_id)->whereHas('enroll' , $callback)
                            ->with(['enroll' => $callback, 'enroll.levels:id,name' ,'enroll.year:id,name' , 'enroll.type:id,name' , 'enroll.classes:id,name'])->first();
            if($result != null)
                $result_collection->push($result);
        }
        // return response()->json(['message' => null, 'body' => $result_collection ], 200);

        $usergrades=UserGrader::select('id','item_id','user_id','grade')->where('user_id',Auth::id())->with(['user:id,firstname,lastname,username','category'=> function($query){
            $query->where('parent','!=',null);
            // $query->where('parent','!=',null);
        }])->get();
        return $usergrades;
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
