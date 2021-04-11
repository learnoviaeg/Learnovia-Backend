<?php

namespace App\Repositories;
use Illuminate\Http\Request;

interface EnrollmentRepositoryInterface
{
    public function RemoveAllDataRelatedToRemovedChain($one);
}