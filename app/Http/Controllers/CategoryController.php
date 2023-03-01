<?php

namespace App\Http\Controllers;

use App\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
     /**
     *
     * @Description :creates new grade category. 
     * @param : name of grade category.
     * @return : returns all grade categories.
     */
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
     /**
     *
     * @Description :updates a grade category. 
     * @param : id and name of grade category.
     * @return : returns all grade categories.
     */
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
        return HelperController::api_response_format(200, $cats, 'Category edited successfully');
    }
     /**
     *
     * @Description :delete a grade category. 
     * @param : id of grade category.
     * @return : returns all grade categories.
     */
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
    /**
     *
     * @Description :list all grade categories or select one by id. 
     * @param : id of grade category as an optional parameter.
     * @return : returns all grade categories or a selected one.
     */
    public function get(Request $request)
    {
        $request->validate([
            'id' => 'exists:categories,id',
            'search' => 'nullable'
        ]);
        if (isset($request->id))
            return HelperController::api_response_format(200, Category::find($request->id));

        
        $categories=Category::paginate(HelperController::GetPaginate($request));
        if($request->filled('search'))
        {
            $categories=Category::where('name', 'LIKE' , "%$request->search%")->get()->paginate(HelperController::GetPaginate($request));
        }
        return HelperController::api_response_format(200, $categories);
    }
}
