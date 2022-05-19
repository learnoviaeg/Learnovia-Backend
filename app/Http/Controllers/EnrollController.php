<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Enroll;
use App\Events\MassLogsEvent;
use App\Parents;

class EnrollController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(['permission:enroll/get' , 'ParentCheck'],   ['only' => ['index']]);
        $this->middleware(['permission:enroll/delete' , 'ParentCheck'],   ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
        ]);

        $chains=Enroll::where('user_id',$request->user_id)->with('roles','levels','courses','classes','year','type','segment')->get();
        return response()->json(['message' => 'Chains are', 'body' => $chains], 200);
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
    public function destroy(Request $request)
    {
        $request->validate([
            'enroll_ids' => 'required|array|exists:enrolls,id',
        ]);

        $chains=Enroll::whereIn('id',$request->enroll_ids);

        //for logs
        $logsbefore=$chains->get();
        // event(new MassLogsEvent($logsbefore,'deleted'));

        foreach($logsbefore as $enroll){
            $parent = Parents::where('child_id',$enroll->user_id)->pluck('parent_id');
            Enroll::whereIn('user_id',$parent)->where('role_id',7)->where('course',$enroll->course)->delete();
        }

        // updated ahmed
        foreach ($logsbefore as $key => $chain) {
            $chain->delete();
        }

        // $chains->delete();
        return response()->json(['message' => __('messages.enroll.delete'), 'body' => null], 200);
    }
}
