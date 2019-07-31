<?php

use Illuminate\Http\Request;

Route::group(['prefix' => 'attach', 'middleware' => 'auth:api'], function () {

    Route::get('install' , function (){
        if (\Spatie\Permission\Models\Permission::whereName('file/add')->first() != null) {
            return \App\Http\Controllers\HelperController::api_response_format(400, null, 'This Component is installed before');
        }
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'file/add']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'file/update']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'file/delete']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'file/toggle']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'media/add']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'media/update']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'media/delete']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'media/toggle']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'file-media/get']);

        $role = \Spatie\Permission\Models\Role::find(1);
        $role->givePermissionTo('file/add');
        $role->givePermissionTo('file/update');
        $role->givePermissionTo('file/delete');
        $role->givePermissionTo('file/toggle');
        $role->givePermissionTo('media/add');
        $role->givePermissionTo('media/update');
        $role->givePermissionTo('media/delete');
        $role->givePermissionTo('media/toggle');
        $role->givePermissionTo('file-media/get');

        return \App\Http\Controllers\HelperController::api_response_format(200, null, 'Component Installed Successfully');
    });
    /* File Routes */
    /* Upload array of files to specific course segment */
    Route::post('uploadFile', 'FilesController@store')->name('uploadFile')->middleware('permission:file/add');

    /* Update specific file */
    Route::post('updateFile', 'FilesController@update')->name('updateFile')->middleware('permission:file/update');

    /* Delete specific file */
    Route::post('deleteFile', 'FilesController@destroy')->name('deleteFile')->middleware('permission:file/delete');

    /* Toggle Visibility of specific file */
    Route::post('toggleFileVisibility', 'FilesController@toggleVisibility')->name('toggleFileVisibility')->middleware('permission:file/toggle');
    /* Media Routes */
    /* Upload array of Media to specific course segment */
    Route::post('uploadMedia', 'MediaController@store')->name('uploadMedia')->middleware('permission:media/add');
    /* Update specific Media */
    Route::post('updateMedia', 'MediaController@update')->name('updateMedia')->middleware('permission:media/update');

    /* Delete specific Media */
    Route::post('deleteMedia', 'MediaController@destroy')->name('deleteMedia')->middleware('permission:media/delete');;

    /* Toggle Visibility of specific Media */
    Route::post('toggleMediaVisibility', 'MediaController@toggleVisibility')->name('toggleMediaVisibility')->middleware('permission:media/toggle');

    /* Attach link as media */
    Route::post('storeMediaLink', 'MediaController@storeMediaLink')->name('storeMediaLink')->middleware('permission:link/add');

    /* update link as media */
    Route::post('updateMediaLink', 'MediaController@updateMediaLink')->name('updateMediaLink')->middleware('permission:link/update');

    /* Get All files and Media assigned to specific course segment */
    Route::get('getFileMedia', 'FilesController@show')->name('getFileMedia')->middleware('permission:file-media/get');;
});
