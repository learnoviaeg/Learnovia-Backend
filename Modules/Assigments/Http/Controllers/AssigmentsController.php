<?php

namespace Modules\Assigments\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Assigments\Entities\assigment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use File;
use App\Http\Controllers\HelperController;
class AssigmentsController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index()
    {
        return view('assigments::index');
    }

    /**
     * Show the form for creating a new resource.
     * @return Response
     */
    public function create(Request $request)
    {
        $filecheck=0;
        if($request->submit_option!=1 &&$request->submit_option!=2&&$request->submit_option!=0)
        {
            return HelperController::api_response_format(400, $body = [], $message = 'incorrect submit option');
        }
            $request->validate([
            'name' => 'required|string',
            'has_grade' => 'required|boolean',
            'submit_option' => 'required|integer',
            'lesson_id' => 'required|exists:lessons,id',
            'visible' => 'boolean|required',
            'userid' => 'required|exists:users,id',
            'start' => 'required|before:end|after:'. Carbon::now(),
            'end' => 'required'
        ]);
        $Assigment=new assigment;
        if(isset($request->file))
        {

            $request->validate([
                'file' => 'file|distinct|mimes:txt,pdf,docs,jpg',
            ]);
            $singlefile= $request->file;
            $extension = $singlefile->getClientOriginalExtension();

            $fileName = uniqid().$singlefile->getClientOriginalName();
            $size = $singlefile->getSize();
            
            $Assigment->file = $fileName;
            
            $filecheck++;
            Storage::disk('public')->putFileAs(
                'assigments/'.$request->userid,
                $singlefile,
                $fileName
            
            );

        }
        if(isset($request->description))
        {
            $filecheck++;
            $Assigment->description = $request->description;
        }
        if($filecheck==0)
        {
            return HelperController::api_response_format(400, $body = [], $message = 'add file or description');

        }
        $Assigment->name= $request->name;
        $Assigment->start_date = $request->start;
        $Assigment->end_date = $request->end;
        $Assigment->visible = $request->visible;
        $Assigment->has_grade = $request->has_grade;
        $Assigment->lesson_id = $request->lesson_id;
        $Assigment->submit_option = $request->submit_option;
        $Assigment->user_id=$request->userid;
        $Assigment->save();

        return HelperController::api_response_format(200, $body = [], $message = 'assigment added');

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
        return view('assigments::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Response
     */
    public function edit(Request $request)
    {

    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(Request $request)
    {
        if($request->submit_option!=1 &&$request->submit_option!=2&&$request->submit_option!=0)
        {
            return HelperController::api_response_format(400, $body = [], $message = 'incorrect submit option');
        }
        $request->validate([
            'id' => 'required|exists:assigments,id',
            'name' => 'required|string',
            'has_grade' => 'required|boolean',
            'submit_option' => 'required|integer',
            'lesson_id' => 'required|exists:lessons,id',
            'visible' => 'boolean|required',
            'userid' => 'required|exists:users,id',
            'start' => 'required|before:end|after:'. Carbon::now(),
            'end' => 'required'
        ]);
        $assig=assigment::find($request->id);
        if(isset($request->file))
        {

            $request->validate([
                'file' => 'file|distinct|mimes:txt,pdf,docs,jpg',
            ]);
            $singlefile= $request->file;
            $extension = $singlefile->getClientOriginalExtension();

            $fileName = uniqid().$singlefile->getClientOriginalName();
            $size = $singlefile->getSize();
            
            $assig->file = $fileName;
            
            Storage::disk('public')->putFileAs(
                'assigments/'.$request->userid,
                $singlefile,
                $fileName
            );

        }
        if(isset($request->description))
        {
            $assig->description = $request->description;
        }
        $assig->name= $request->name;
        $assig->start_date = $request->start;
        $assig->end_date = $request->end;
        $assig->visible = $request->visible;
        $assig->has_grade = $request->has_grade;
        $assig->lesson_id = $request->lesson_id;
        $assig->submit_option = $request->submit_option;
        $assig->user_id=$request->userid;
        $assig->save();

        return HelperController::api_response_format(200, $body = [], $message = 'assigment edited');
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function destroy(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:assigments,id'
        ]);

        $assig = assigment::find($request->id);
        $assig->delete();
        return HelperController::api_response_format(200, $body = [], $message = 'assigment deleted');
    }
}
