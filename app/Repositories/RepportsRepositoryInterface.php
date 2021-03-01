<?php

namespace App\Repositories;
use Illuminate\Http\Request;

interface RepportsRepositoryInterface
{
    public function calculate_course_progress($course_id);
}