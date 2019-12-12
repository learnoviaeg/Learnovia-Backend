<?php

namespace App\Http\Controllers;

use App\SystemSetting;
use Validator;
use Illuminate\Http\Request;

class SystemSettingsController extends Controller
{
    /**
     * Get Active languages
     *
     * if there is no languages in system @return [string] System does not installed to have languages
     * @return [objects] languages
    */
    public static  function GetActiveLanguages()
    {
        $result = collect();
        $languages=collect();
        $languages->push(SystemSetting::where('key', 'languages')->first());
        if ($languages == null)
            return HelperController::api_response_format(200, 'System does not installed to have languages');

        $languages = unserialize($languages[0]->data);
        foreach ($languages as $index => $language) {
            if ($language['active'])
                $result->push($language);
        }
        return HelperController::api_response_format(200, $result);
    }

    /**
     * Get default language
     *
     * if there is no languages in system @return [string] System does not installed to have languages
     * if there is no default languages in system @return [string] There is no default language set
     * @return [object] language
    */
    public function GetDefaultLanguage()
    {
        $languages = SystemSetting::where('key', 'languages')->first();
        if ($languages == null)
            return HelperController::api_response_format(200, 'System does not installed to have languages');

        $languages = unserialize($languages->data);
        $result = 'There is no default language set';
        foreach ($languages as $index => $language) {
            if ($language['default'])
                $result = $language;
        }
        return HelperController::api_response_format(200, $result);
    }

    /**
     * Activate language
     *
     * @param [int] id
     * if there is no languages in system @return [string] System does not installed to have languages
     * @return [string] message [activated|null]
    */
    public function ActivateLanguage(Request $request)
    {
        if (!$request->filled('id'))
            return HelperController::api_response_format(200, 'You must insert ID');

        $languages = SystemSetting::where('key', 'languages')->first();
        if ($languages == null)
            return HelperController::api_response_format(200, 'System does not installed to have languages');

        $message = null;
        $languages = unserialize($languages->data);
        foreach ($languages as $index => $language) {
            if ($index == $request->id) {
                $languages[$index]['active'] = 1;
                $message = 'Language ' . $language['name'] . ' activated';
            }
        }
        if ($message != null) {
            $temp = SystemSetting::where('key', 'languages')->first();
            $temp->data = serialize($languages);
            $temp->save();
        }
        return HelperController::api_response_format(200, $message);
    }

    /**
     * DeActivate language
     *
     * @param [int] id
     * if there is no languages in system @return [string] System does not installed to have languages
     * @return [string] message [deactivated|null]
    */
    public function DeActivateLanguage(Request $request)
    {
        if (!$request->filled('id'))
            return HelperController::api_response_format(200, 'You must insert ID');

        $languages = SystemSetting::where('key', 'languages')->first();
        if ($languages == null)
            return HelperController::api_response_format(200, 'System does not installed to have languages');
        $message = null;
        $languages = unserialize($languages->data);
        foreach ($languages as $index => $language) {
            if ($index == $request->id) {
                $languages[$index]['active'] = 0;
                $message = 'Language ' . $language['name'] . ' Deactivated';
            }
        }
        if ($message != null) {
            $temp = SystemSetting::where('key', 'languages')->first();
            $temp->data = serialize($languages);
            $temp->save();
        }
        return HelperController::api_response_format(200, $message);
    }

    /**
     * Add language
     *
     * @param [string] name
     * @param [boolean] active
     * @return [string] message {language added successfully}
     * if there is no languages in system @return [string] System does not installed to have languages
    */
    public function AddLanguage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:2',
            'active' => 'nullable|in:0,1',
        ]);
        if ($validator->fails())
            return HelperController::api_response_format(200, $validator->errors());

        $languages = SystemSetting::where('key', 'languages')->first();
        if ($languages == null)
            return HelperController::api_response_format(200, 'System does not installed to have languages');

        $languages = unserialize($languages->data);
        $temp = [
            'id' => count($languages),
            'name' => $request->name,
            'active' => ($request->filled('active')) ? $request->active : 0,
            'default' => 0,
        ];
        array_push($languages, $temp);
        $langObject = SystemSetting::where('key', 'languages')->first();
        $langObject->data = serialize($languages);
        $langObject->save();
        return HelperController::api_response_format(200, 'Language Added Successfully');
    }

    /**
     * set Default language
     *
     * @param [string] name
     * @param [boolean] active
     * @return [string] message {language ... set default}
     * if there is no languages in system @return [string] System does not installed to have languages
    */
    public function SetDefaultLanguage(Request $request)
    {
        $languages = SystemSetting::where('key', 'languages')->first();
        if ($languages == null)
            return HelperController::api_response_format(200, 'System does not installed to have languages');
        if (!$request->filled('id'))
            return HelperController::api_response_format(200, 'You must insert ID');

        $languages = SystemSetting::where('key', 'languages')->first();
        if ($languages == null)
            return HelperController::api_response_format(200, 'System does not installed to have languages');
        $message = null;
        $languages = unserialize($languages->data);
        foreach ($languages as $index => $language) {
            if ($index == $request->id) {
                $languages[$index]['default'] = 1;
                $message = 'Language ' . $language['name'] . ' Set Default';
                continue;
            }
            $languages[$index]['default'] = 0;
        }
        if ($message != null) {
            $temp = SystemSetting::where('key', 'languages')->first();
            $temp->data = serialize($languages);
            $temp->save();
        }
        return HelperController::api_response_format(200, $message);
    }
}
