<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Dictionary;
use App\Language;
use App\User;
use Illuminate\Support\Facades\Auth;

class LanguageController extends Controller
{

    public function add_language(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'default' => 'boolean',
        ]);
        if($request->default == 1 )
        Language::where('default' , '1')->update([
            'default'=> 0, 
        ]);
        Language::create([
            'name' => $request->name,
            'default' => (isset($request->default) && $request->default == 1) ? 1 : 0,
        ]);
        return HelperController::api_response_format(200, Language::all() , 'New language is added...');
    }
    public function update_language(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:languages,id',
            'name' => 'string',
            'default' => 'boolean',
        ]);
        $lang = Language::where('id' , $request->id)->first();
        if(isset($request->default) && $request->default == 0 && $lang->default == 1)
            return HelperController::api_response_format(200, [] , 'This is the default language and cannot be toggled unless you choose a default one instead');

        if($request->default == 1 && $lang->default != 1)
            Language::where('default' , '1')->update([
                'default'=> 0, 
            ]);
        
        if(isset($request->name ))
            $lang->name = $request->name;
        if(isset($request->default))
            $lang->default = $request->default;  
        $lang->save();
        return HelperController::api_response_format(200, Language::all(), 'Language is updated....');

    }
    public function Get_languages()
    {
        return HelperController::api_response_format(200, Language::all(), 'Languages are....');
    }
    public function Delete_languages(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:languages,id',
        ]);
        $lang = Language::where('id' , $request->id)->first();
        if($lang->default == 1)
            return HelperController::api_response_format(200, [] , 'This is the default language and cannot be deleted unless you choose a default one instead'); 
    $lang->delete();
    return HelperController::api_response_format(200, Language::all(), 'Language is deleted....');

    }
}
