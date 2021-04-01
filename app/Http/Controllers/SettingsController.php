<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Settings;

class SettingsController extends Controller
{

    public function __construct()
    {
        $this->middleware(['permission:settings/general'],   ['only' => ['index']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $settings = Settings::get();

        $settings->map(function ($setting){
            $setting->value = explode(',',$setting->value);
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
            'key' => 'required|in:'.implode(',',$settings->toArray()),
            'value' => 'required'
        ]);

        if(!$request->user()->can('settings/'.$request->key))
            return response()->json(['message' => 'you dont have the permission to update that content.','body' => null], 400);

        $setting = Settings::where('key',$request->key)->update([
            'value' => $request->value
        ]);

        return response()->json(['message' => 'setting updated.','body' => $setting], 200);
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
