<?php

namespace App\Repositories;
use Illuminate\Http\Request;

interface SettingsReposiotryInterface
{
    public function get_value($key);
}