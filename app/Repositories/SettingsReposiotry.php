<?php

namespace App\Repositories;
use Illuminate\Http\Request;
use App\Settings;

class SettingsReposiotry implements SettingsReposiotryInterface
{
    public function get_value($key){

        $setting = Settings::where('key',$key)->pluck('value')->first();
        return $setting;
    }
}