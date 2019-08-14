<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ExcelController extends Controller
{
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xls,xlsx',
            'type' => 'required',
        ]);
        $type = $request->type;
        $files = scandir(__DIR__ . '/../../Imports/');
        $name = $type . '.php';
        if (in_array($name, $files)) {
            eval('$impoerter = new App\Imports\\' . $type . '();');
            $x = Excel::import($impoerter, request()->file('file'));
            return HelperController::api_response_format(201 , [] , 'Data Imported Successfully');
        } else {
            return HelperController::NOTFOUND();
        }
    }
}
