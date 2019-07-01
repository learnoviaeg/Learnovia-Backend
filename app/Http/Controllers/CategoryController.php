<?php

namespace App\Http\Controllers;

use App\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function add(Request $request)
    {
        $cat = Category::create([
            'name' => $request->name
        ]);
        return response()->json(['msg' => 'Category Created Successfully', 'body' => $cat], 201);
    }

    public function edit(Request $request)
    {
        $cat = Category::find($request->category);
        $cat->name = $request->name;
        $cat->save();
        return response()->json(['msg' => 'Category Updated Successfully', 'body' => $cat], 203);
    }

    public function delete(Request $request)
    {
        $cat = Category::find($request->category);
        $cat->delete();
        return response()->json(['msg' => 'Category Deleted Successfully', 'body' => $cat], 203);
    }

    public function get(Request $request)
    {
        if (isset($request->id))
            return response()->json(['msg' => 'Category Deleted Successfully', 'body' => Category::find($request->id)], 203);
        return response()->json(['body' => Category::all()], 203);
    }
}
