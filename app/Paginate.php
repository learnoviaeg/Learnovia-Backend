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
}
