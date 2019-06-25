<?php

namespace App\Http\Controllers;
use App\Course;
use Illuminate\Http\Request;
use App\Http\Resources\Course as CourseResource;

class CourseController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $course = Course::paginate(5); 
        return CourseResource::collection($course);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
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

        // USE Valdation 

        $request->validate([
            'name' => 'required'
        ]);

        if($file = $request->file('file')){

            $imgstore = $file->getClientOriginalName();
            $name = $request->input('name');
            $description = $request->input('description');
            $start_date = $request->input('start_date');
            $end_date = $request->input('end_date');
            $picture = $request->input('picture');

            if($file->move('image' , $imgstore)){
                $course = new Course();
                $course->picture = $imgstore;
                $course->name = $request->input('name');
                $course->description = $request->input('description');
                $course->start_date = $request->input('start_date');
                $course->end_date = $request->input('end_date');
                $course->save();
                return;
                // return redirect()->route('login');
            };
                return"Somthing Wrong";
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $course = Course::findOrFail($id);
        return $course;
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {

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
        $course = Course::find($id);
        $course->update($request->all());
        return $course;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $course = Course::whereId($id)->delete();
        return;
    }
}
