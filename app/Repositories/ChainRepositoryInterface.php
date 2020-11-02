<?php

namespace App\Repositories;
use Illuminate\Http\Request;

interface ChainRepositoryInterface
{

    public function getCourseSegmentByChain(Request $request);

}