<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
class URLController extends Controller
{
    public function index(Request $request)
    {
       $path = url('/storage/files/'.$request->filename);
        return $path;
    }
}
