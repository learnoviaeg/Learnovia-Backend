<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Paginate extends Model
{
    //
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

    public static function GetPage($request)
    {
        $request->validate([
            'page' => 'integer',
        ]);

        if($request->filled('page') && $request->page >0){
            return ($request->page)-1;
        }
        return 0 ;
    }

    public static function allPages($countQuery, $paginate)
    {
        return ceil($countQuery/$paginate);
    }
}
