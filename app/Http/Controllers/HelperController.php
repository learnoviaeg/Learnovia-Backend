<?php

namespace App\Http\Controllers;

use App\AcademicYear;
use App\Segment;
use App\AcademicYearType;
use App\YearLevel;
use App\ClassLevel;
use App\SegmentClass;
use App\CourseSegment;
use Validator;

class HelperController extends Controller
{
    /**
     * response formate
     *
     * @param  [array/object] body
     * @param  [int] $code
     * @param  [string] message
     * @return [objects] all params
    */
    public static function api_response_format($code, $body = [], $message = [])
    {
        return response()->json([
            'message' => $message,
            'body' => $body
        ], $code);
    }

    /**
     * NOT Found
     *
     * @return [string] NOTFound, [] body, 404
    */
    public static function NOTFOUND()
    {
        return response()->json([
            'message' => 'NotFOund',
            'body' => []
        ], 404);
    }

    /**
     * get paginate
     *
     * @param  [int] paginate
     * @return [int] if there is no paginate by default return 10
    */
    public static function GetPaginate($request)
    {
        $request->validate([
            'paginate' => 'integer',
        ]);

        if($request->filled('paginate')){
            return $request->paginate;
        }
        return 10 ;
    }
}
