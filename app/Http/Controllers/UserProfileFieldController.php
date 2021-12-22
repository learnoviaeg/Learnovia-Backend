<?php

namespace App\Http\Controllers;

use App\UserProfileField;
use Illuminate\Http\Request;

class UserProfileFieldController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return HelperController::api_response_format(201, UserProfileField::all(), __('messages.field.list'));
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
        ]);

        $profile=UserProfileField::firstOrCreate(['name' => $request->name]);
        return HelperController::api_response_format(201, $profile, __('messages.field.add'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return HelperController::api_response_format(201, UserProfileField::find($id), __('messages.field.get'));
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
            'name' => 'nullable',
        ]);

        $profile=UserProfileField::whereId($id)->update(['name' => $request->name]);
        return HelperController::api_response_format(201, $profile, __('messages.field.update'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $profile=UserProfileField::whereId($id)->delete();
        return HelperController::api_response_format(201, null, __('messages.field.delete'));
    }
}
