<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\CourseSegment;
use App\Letter;

class LetterController extends Controller
{
    /**
     * Add letter
     *
     * @param  [string] name
     * @param  [array] formate
     * @param  [string] formate[name], formate[boundary]
     * @return [object] Letter Created Successfully
    */
    public function add(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'formate' => 'required|array',
            'formate.*'=> 'required',
            'formate.*.name'=> 'required|string',
            'formate.*.boundary'=> 'required|integer',
        ]);

        $scaleLetter=serialize($request->formate);
        $letter = Letter::firstOrCreate([
            'name' => $request->name,
            'formate' => $scaleLetter,
        ]);
        $letter->formate = unserialize($letter->formate);
        return HelperController::api_response_format(200,$letter, 'Letter Created Successfully' );

    }

    /**
     * update letter
     *
     * @param  [string] name, formate[name], formate[boundary]
     * @param  [int] id
     * @return [object] Letter updated Successfully
    */
    public function update(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'id'  => 'required|exists:letters,id',
            'formate' => 'required|array',
            'formate.*'=> 'required',
            'formate.*.name'=> 'required|string',
            'formate.*.boundary'=> 'required',
        ]);

        $check = CourseSegment::where('letter_id',$request->id)->first();
        if(!isset($check))
        {
            $letter_id = Letter::find($request->id);
            $letter_id->name = $request->name;
            $letter_id->formate = serialize($request->formate);
            $letter_id->save();
            $letter_id->formate = unserialize($letter_id->formate);
            return HelperController::api_response_format(200,$letter_id, 'Letter Updated Succefully' );
        }
        return HelperController::api_response_format(200,null, 'This Letter Used Before ' );
    }

    /**
     * delete letter
     *
     * @param  [int] id
     * @return if letter used in course segment [string] This Letter Used Before
     * @return [object] Letter deleted Successfully
    */
    public function delete(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:letters,id'
        ]);
        $check = CourseSegment::where('letter_id',$request->id)->first();
        $letter_id = Letter::find($request->id);
        if (isset($check))
        {
            $letter_id->formate = unserialize($letter_id->formate);
            return HelperController::api_response_format(200,$letter_id, 'This Letter Used Before ');
        }
        $letter_id->delete();
        return HelperController::api_response_format(200, Letter::get()->paginate(HelperController::GetPaginate($request)), 'Letter Deleted Successfully');
    }

    /**
     * get letter
     *
     * @param  [int] id
     * @return [object] Letter
    */
    public function get(Request $request)
    {
        $request->validate([
            'id' => 'exists:letters,id'
        ]);

        if($request->filled('id'))
        {
            $letter_id = Letter::find($request->id);
            $letter_id->formate = unserialize($letter_id->formate);
            return HelperController::api_response_format(200, $letter_id);
        }
        $letters=Letter::get();
        foreach($letters as $letter)
            $letter->formate = unserialize($letter->formate);
        return HelperController::api_response_format(200, $letters);
    }

    public function assignLetterToCourse(Request $request){
        $request->validate([
            'course' => 'required|exists:courses,id',
            'class' => 'required|exists:classes,id',
            'letter' => 'required|exists:letters,id',
        ]);
        $courseSegment = CourseSegment::GetWithClassAndCourse($request->class,$request->course);
        if($courseSegment == null)
            return HelperController::api_response_format(200, 'This Course is not assigned to this class');
        $courseSegment->update([
            'letter_id' => $request->letter,
            'letter' => 1
        ]);
        return HelperController::api_response_format(200, 'Letter Assigned Successfully');
    }

    public function GetLettersWithCourse(Request $request)
    {
        $request->validate([
            'course' => 'required|integer|exists:courses,id',
            'class' => 'required|integer|exists:classes,id'
        ]);

        $course_segment=CourseSegment::GetWithClassAndCourse($request->class,$request->course);
        if(!isset($course_segment)){
            return HelperController::api_response_format(200, 'This Course and class is not assigned to course segmant');
            }

            $letters=CourseSegment::where('id',$course_segment->id)->where('letter',1)->pluck('letter_id')->first();
            if(!isset($letters)){
                return HelperController::api_response_format(200, 'This Course doesn\'t have letter');
        }
        $letter = Letter::find($letters);
        $letter->formate = unserialize($letter->formate);
        return HelperController::api_response_format(200,$letter);

       
    }

}
