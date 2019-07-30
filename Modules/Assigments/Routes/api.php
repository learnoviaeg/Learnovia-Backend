<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group(['prefix' => 'assignment', 'middleware' => 'auth:api'], function () {

    Route::get('install', function () {

        if (\Spatie\Permission\Models\Permission::whereName('assignment/add')->first() != null) {
            return \App\Http\Controllers\HelperController::api_response_format(400, null, 'This Component is installed before');
        }

        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'assignment/add']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'assignment/update']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'assignment/submit']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'assignment/grade']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'assignment/override']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'assignment/delete']);

        $role = \Spatie\Permission\Models\Role::find(1);
        $role->givePermissionTo('assignment/add');
        $role->givePermissionTo('assignment/update');
        $role->givePermissionTo('assignment/submit');
        $role->givePermissionTo('assignment/grade');
        $role->givePermissionTo('assignment/override');
        $role->givePermissionTo('assignment/delete');

        return \App\Http\Controllers\HelperController::api_response_format(200, null, 'Component Installed Successfully');
    });
    Route::post('create', 'AssigmentsController@createAssigment')->middleware('permission:assignment/add');
    Route::post('update', 'AssigmentsController@ubdateAssigment')->middleware('permission:assignment/update');
    Route::post('submit', 'AssigmentsController@submitAssigment')->middleware('permission:assignment/submit');
    Route::post('grade', 'AssigmentsController@gradeAssigment')->middleware('permission:assignment/grade');
    Route::post('override', 'AssigmentsController@override')->middleware('permission:assignment/override');
    Route::post('delete', 'AssigmentsController@deleteAssigment')->middleware('permission:assignment/delete');
    Route::post('GetAssignment','AssigmentsController@GetAssignment');

});
// Route::post('GetAssignment','AssigmentsController@GetAssignment');
