<?php

namespace App\Http\Controllers;

use App\Component;
use App\LessonComponent;
use Chumper\Zipper\Facades\Zipper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class ComponentController extends Controller
{
    public function GetInstalledComponents()
    {
        return HelperController::api_response_format(200, Component::whereActive(1)->get());
    }
    /**
     * @Description :install a component
     * @param : file in zip form as a required parameter.
     * @return : return component.
     */
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
    /**
     * @Description :uninstall a component.
     * @param : id of component.
     * @return : return component.
     */
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
    /**
     * @Description :toggles activity f components.
     * @param : id of component.
     * @return : return component.
     */
    public function ToggleActive(Request $request){
        $request->validate([
            'id' => 'required|exists:components,id'
        ]);
        $componenet = Component::find($request->id);
        $componenet->active = ($componenet->active == 1)? 0 : 1;
        $componenet->save();
        return HelperController::api_response_format(200 , $componenet , 'Component Updated Successfully');
    }

    public function sortDown($com_id, $index,$lesson_id)
    {

        $com_index = LessonComponent::where('comp_id', $com_id)->where('lesson_id',$lesson_id)->pluck('index')->first();

        $Comps = LessonComponent::where('lesson_id', $lesson_id)->get();
        foreach ($Comps as $com) {
            if ($com->index > $com_index || $com->index < $index) {
                continue;
            }
            if ($com->index != $com_index) {
                $com->update([
                    'index' => $com->index + 1
                ]);
            } else {
                $com->update([
                    'index' => $index
                ]);
            }
        }
        return $Comps;
    }

    public function SortUp($com_id, $index,$lesson_id)
    {
        $com_index = LessonComponent::where('comp_id', $com_id)->where('lesson_id',$lesson_id)->pluck('index')->first();

        $Comps = LessonComponent::where('lesson_id', $lesson_id)->get();
        foreach ($Comps as $com) {
            if ($com->index > $index || $com->index < $com_index) {
                continue;
            } elseif ($com->index != $com_index) {
                $com->update([
                    'index' => $com->index - 1
                ]);
            } else {
                $com->update([
                    'index' => $index
                ]);
            }
        }
        return $Comps;
    }
    /**
     * @Description :interface to sort components up and down.
     * @param : component_id, index and lesson id of component.
     * @return : return all components sorted.
     */
    public function sort(Request $request)
    {
        $request->validate([
            'component_id' => 'required|integer|exists:lesson_components,comp_id',
            'index' => 'required|integer',
            'lesson_id'=>'required|integer|exists:lesson_components,lesson_id'
        ]);
        $Com_index = LessonComponent::where('comp_id', $request->component_id)->where('lesson_id',$request->lesson_id)->pluck('index')->first();
        $max =LessonComponent ::where('lesson_id',$request->lesson_id)->max('index');
        if($request->index <=$max){
        if ($Com_index > $request->index  ) {
            $Components = $this->sortDown($request->component_id, $request->index,$request->lesson_id);
        } else {
            $Components = $this->SortUp($request->component_id, $request->index,$request->lesson_id);
        }
            return HelperController::api_response_format(200, $Components, ' Successfully');

        }

        return HelperController::api_response_format(400, null, 'invalid index');
    }
    public function ChangeColor(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:components,id',
            'color'=>'required'
        ]);
        $component = Component::find($request->id);
        $component->color=$request->color;
        $component->save();
        return HelperController::api_response_format(200, $component, 'Color is changed Successfully');
    }
}