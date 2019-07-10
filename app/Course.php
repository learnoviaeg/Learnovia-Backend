<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Http\Controllers\HelperController;

class Course extends Model
{
    protected $fillable = ['name', 'category_id'];

    public static function Get_Segments_with_specific_Class($id)
    {
        $cat = ClassLevel::where('class_id', $id)->get(['id'])->toArray();
        $Segment_class = SegmentClass::whereIN('class_level_id', $cat)->get(['segment_id'])->toArray();
        $Segments = Segment::whereIN('id', $Segment_class)->get();
        return HelperController::api_response_format(200, $Segments);
    }
}