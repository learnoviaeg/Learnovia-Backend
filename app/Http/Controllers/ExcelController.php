<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\UserExport;
use App\Imports\UserImport;
use App\Imports\CourseImport;
use App\Exports\CourseExport;

class ExcelController extends Controller
{

        // Future Work (AddiWithng Image  the Excel sheet Validation)
 
    public function import(Request $request)
{   

    $request->validate(['file' => 'required|mimes:xls,xlsx']);

    $type = $request->type;
    $files = scandir( __DIR__.'/../../Imports/');
      //  dd($files);
    $name = $type.'.php';

    if(in_array($name , $files)){
       //  echo $type;
       eval('$impoerter = new App\Imports\\'.$type.'();');
       Excel::import($impoerter , request()->file('file'));  
    }
    else{
        return 'Wrong Type!';
    }

}
    // Future Work

// public function export() 
// { 
//     $type = get()->type;
//     eval('$excel = new App\Exports\\'.$type.'();');
//     return Excel::download(new $excel, 'Excel.xlsx');
// }

}
