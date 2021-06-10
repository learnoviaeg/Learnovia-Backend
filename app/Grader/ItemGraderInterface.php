<?php

namespace App\Grader;
use Illuminate\Http\Request;

interface ItemGraderInterface
{
    public function grade($user);
}