<?php

namespace App\Http\Controllers;

use App\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function add(Request $request)
    {
        $request->validate([
            'name' => 'required'
        ]);
        $cat = Category::create([
            'name' => $request->name
        ]);
        $cats=Category::get()->paginate(HelperController::GetPaginate($request));
        return HelperController::api_response_format(201, $cats, 'Category Created Successfully');
    }

    public function edit(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:categories,id',
            'name' => 'required'
        ]);
        $cat = Category::find($request->id);
        $cat->name = $request->name;
        $cat->save();
        $cats=Category::get()->paginate(HelperController::GetPaginate($request));
        return HelperController::api_response_format(200, $cats, 'Category Updated Successfully');
    }

    public function delete(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:categories,id'
        ]);
        $cat = Category::find($request->id);

        $cat->delete();
        $cats=Category::get()->paginate(HelperController::GetPaginate($request));
        return HelperController::api_response_format(200, $cats, 'Category Deleted Successfully');
    }

    public function get(Request $request)
    {
        $request->validate([
            'id' => 'exists:categories,id'
        ]);
        if (isset($request->id))
            return HelperController::api_response_format(200, Category::find($request->id));
        return HelperController::api_response_format(200, Category::paginate(HelperController::GetPaginate($request)));
    }
}
