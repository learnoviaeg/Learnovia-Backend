<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ExcelController extends Controller
{
    /**
     * Import enroll/courses/users depended on type of import
     *
     * @param  [string] type
     * @param  [string .. path] file
     * @return [string] Data Imported Successfully
    */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xls,xlsx,csv',
            'type' => 'required',
        ]);
        $type = $request->type;
        $files = scandir(__DIR__ . '/../../Imports/');
        $name = $type . '.php';

        if (in_array($name, $files)) {
            eval('$importer = new App\Imports\\' . $type . '();');
            $check = Excel::import($importer, request()->file('file'));
            return HelperController::api_response_format(201 , [] , __('messages.success.data_imported'));
        } else {
            return HelperController::NOTFOUND();
        }
    }
}
