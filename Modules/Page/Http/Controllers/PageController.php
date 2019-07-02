<?php

namespace Modules\Page\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Page\Entities\Page;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Input;
use App\Classes;
use App\Segment;
use Validator;
use Carbon\Carbon;


class PageController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index()
    {
        return view('page::index');
    }

    /**
     * Show the form for creating a new resource.
     * @return Response
     */
    public function create(Request $request)
    {
      
      
    }


    public function Page(Request $request )
    {
        $time=Carbon::now();
        $validater=Validator::make($request->all(),[
            'name'=>'required',
            'visability' => 'required|boolean',
            'segment_id'=>'required|integer|exists:segments,id',
            'start_date'=>'before:due_date|after:'.Carbon::now(),
            'due_date'=>'after:'.Carbon::now()
            
            
        ]);
        if ($validater->fails())
            {
                $errors=$validater->errors();
                return response()->json($errors,400);
            }

        if (Input::hasFile('attached_file'))
        {

            foreach($request->file('attached_file') as $file) 
            {
            if($file->getClientOriginalExtension() != 'pdf' && $file->getClientOriginalExtension() != 'docx' && $file->getClientOriginalExtension() != 'doc' && $file->getClientOriginalExtension() != 'xls' && $file->getClientOriginalExtension() != 'xlsx' && $file->getClientOriginalExtension() != 'ppt' && $file->getClientOriginalExtension() != 'pptx' && $file->getClientOriginalExtension() != 'zip' && $file->getClientOriginalExtension() != 'rar' && $file->getClientOriginalExtension() != 'txt') 
            {
                return ('Error,This type is not supported by system');
            }
            $destinationPath = public_path();
            $name = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $fileName = $name.'.'.uniqid($request->id).'.'.$extension;
            $data[]=$fileName;
            //store the file in the $destinationPath
            $file =$file->move($destinationPath, $fileName);
            }

            //save a corresponding record in the database
            $datalast=Implode(',',$data);

    }  
        //Restriction for class
        foreach($request->class_id as $c)
        {
            $class = Classes::where('id', '=',$c)->first();
            if ($class === null) {
                return('this class doesnt exist');
            }

        }

        $classlist=Implode(',',$request->class_id);

        Page::create([
            'name'=> $request->name,
            'page_content'=> $request->page_content,
            'attached_file'=> $datalast,
            'visability' => $request->visability,
            'class_id'=>$classlist,
            'segment_id'=>$request->segment_id,
            'start_date'=>$request->start_date,
            'due_date'=>$request->due_date
            ]);

  
 } 

    public function Pages_with_classes(Request $request)
    {

        $validater=Validator::make($request->all(),[
            'id'=>'required|integer|exists:pages,id', 
        ]);
        if ($validater->fails())
        {
            $errors=$validater->errors();
            return response()->json($errors,400);
        }

        $page = Page::where('id', '=', $request->id)
        ->pluck('class_id')
        ->first();
        
        $page=Explode(',',$page);
        return($page);
     
    }

    public function get_classes()
    {
        $class=Classes::get();
         return($class);
    }

    public function get_segments()
    {
        $segment=Segment::get();
         return($segment);
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
    public function show_Page()
    {
         $page=Page::get();
         return($page);
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
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function destroy($id)
    {
        //
    }
}
