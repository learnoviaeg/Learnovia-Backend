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

    /*
      This function is to Add a new Page
      @param: name[requierd],page_content[optional],attached_file[optional][can be multiple],
      visability[true,false || 1,0],class_id[can be multiple][class should be exist],segment_id[requierd][should be exist],start_date[requierd],
      due_date[requierd].
      @output: 'all conditions pass' -> page added successfully
               'else' -> Error
    */


    public function Page(Request $request )
    {
        //Validation
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

         //To upload a file or files   
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
        //concatenate classes assigned
        $classlist=Implode(',',$request->class_id);

        //create the page
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
 
    /*
      This function is to get every page with the classes assigned 
      @param: pageid
      @output: 'if page exist' -> Page with classes assigned,
               'else' -> Page not found
    */
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

    /*
      This function is to get all classes
      @output: all classes registerd in system.
    */

    public function get_classes()
    {
        $class=Classes::get();
         return($class);
    }

    /*
      This function is to get all segments
      @output: all segments registerd in system.
    */

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

  /*
      This function is to show all pages
      @output: get all pages in system.
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
