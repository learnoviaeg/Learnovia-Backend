<?php

namespace Modules\Page\Http\Controllers;

use App\Http\Controllers\HelperController;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Page\Entities\Page;

class PageController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function install()
    {
        if (\Spatie\Permission\Models\Permission::whereName('page/add')->first() != null) {
            return \App\Http\Controllers\HelperController::api_response_format(400, null, 'This Component is installed before');
        }

        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'page/add']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'page/update']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'page/delete']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'page/grade']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'assignment/override']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'assignment/delete']);
        \Spatie\Permission\Models\Permission::create(['guard_name' => 'api', 'name' => 'assignment/get']);

        $role = \Spatie\Permission\Models\Role::find(1);
        $role->givePermissionTo('assignment/add');
        $role->givePermissionTo('assignment/update');
        $role->givePermissionTo('assignment/submit');
        $role->givePermissionTo('assignment/grade');
        $role->givePermissionTo('assignment/override');
        $role->givePermissionTo('assignment/delete');
        $role->givePermissionTo('assignment/get');

        return \App\Http\Controllers\HelperController::api_response_format(200, null, 'Component Installed Successfully');
    }

    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index()
    {
        return view('page::index');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Response
     */
    public function show($id)
    {
        return view('page::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Response
     */
    public function edit($id)
    {
        return view('page::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:pages,id'
        ]);

        $page = Page::find($request->id);

        $request->validate([
            'title' => 'required|string',
            'content' => 'required|string',
            'visible' => 'nullable|boolean'
        ]);

        $data=[
                'title' => $request->title,
                'content' => $request->content
            ];
            if(isset($request->visible)) {
                $data['visible']=$request->visible;
            }

        $page->update($data);
        return HelperController::api_response_format(200, $page,'Page Updated Successfully');
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function destroy(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:pages,id',
        ]);

        $page =Page::find($request->id);
        if ($page->delete()) {
            return HelperController::api_response_format(200, $page,'Page Deleted Successfully');
        }
        return HelperController::api_response_format(404, [], 'Not Found');
    }

    public function toggle(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:pages,id',
        ]);

        $page =Page::find($request->id);
        if($page->visible == 0)
        {
            $page->update([
                'visible' => 1
            ]);
        }
        else{
            $page->update([
                'visible' => 0
            ]);
        }
        return HelperController::api_response_format(200, $page,'Page Toggled Successfully');
    }

}
