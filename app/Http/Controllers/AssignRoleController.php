<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use App\User;

class AssignRoleController extends Controller
{

    /**
     * AssignRoleController constructor.
     *
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(['permission:roles/assign'],   ['only' => ['store']]);
        $this->middleware(['permission:roles/revoke-from-user'],   ['only' => ['destroy']]);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //validate the request
        $request->validate([
            'role_id' => 'required|exists:roles,id',
            'users'    => 'required|array',
            'users.*'  => 'integer|exists:users,id',
        ]);

        $Role = Role::find($request->role_id);

        foreach ($request->users as $user) {

            $User = User::find($user);

            $User->assignRole($Role->name);
        }

        return response()->json(['message' => __('messages.role.assign'), 'body' => null ], 200);
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
        //validate the request
        $request->validate([
            'role_id' => 'required|exists:roles,id',
            'users'    => 'required|array',
            'users.*'  => 'integer|exists:users,id',
        ]);

        $Role = Role::find($request->role_id);

        foreach ($request->users as $user) {

            $User = User::find($user);

            $User->removeRole($Role->name);
        }

        return response()->json(['message' => __('messages.role.revoke'), 'body' => null ], 200);
    }
}
