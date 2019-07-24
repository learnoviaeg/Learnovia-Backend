<?php

namespace App\Http\Controllers;

use App\Component;
use Chumper\Zipper\Facades\Zipper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class ComponentController extends Controller
{
    public function GetInstalledComponents()
    {
        return HelperController::api_response_format(200, Component::whereActive(1)->get());
    }

    public function Install(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:zip'
        ]);
        $logFiles = Zipper::make($request->file)->listFiles();
        $pluginName = substr($logFiles[0], 0, strpos($logFiles[0], '/'));
        Zipper::make($request->file)->extractTo(base_path('Modules'));
        $componenet = Component::create([
            'name' => $pluginName
        ]);
        return HelperController::api_response_format(201, $componenet, 'Component Installed Successfully');
    }

    public function Uninstall(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:components,id'
        ]);
        $componenet = Component::find($request->id);
        File::deleteDirectory(base_path('Modules/' . $componenet->name));
        $componenet->delete();
        return HelperController::api_response_format(200, $componenet, 'Component Deleted Successfully');
    }

    public function ToggleActive(Request $request){
        $request->validate([
            'id' => 'required|exists:components,id'
        ]);
        $componenet = Component::find($request->id);
        $componenet->active = ($componenet->active == 1)? 0 : 1;
        $componenet->save();
        return HelperController::api_response_format(200 , $componenet , 'Component Updated Successfully');
    }
}