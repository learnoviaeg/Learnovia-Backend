<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Letter;
use App\LetterDetails;
use App\Course;
use App\Repositories\ChainRepositoryInterface;

class LetterController extends Controller
{
    public function __construct(ChainRepositoryInterface $chain)
    {
        $this->chain = $chain;
        $this->middleware('auth');
        $this->middleware(['permission:grade/letter/show'],  ['only' => ['index','show']]);
        $this->middleware(['permission:grade/letter/add'],   ['only' => ['store']]);
        $this->middleware(['permission:grade/letter/edit'],   ['only' => ['update']]);
        $this->middleware(['permission:grade/letter/delete'],   ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        $letter = Letter::with(['details', 'course'])->get();
        return response()->json(['message' => __('messages.letter.list'), 'body' => $letter], 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'courses'    => 'nullable|array',
            'courses.*'  => 'nullable|integer|exists:courses,id',
            'levels'    => 'nullable|array',
            'levels.*'  => 'nullable|integer|exists:levels,id',
            'years' => 'array',
            'years.*' => 'exists:academic_years,id',
            'types' => 'array',
            'types.*' => 'exists:academic_types,id',
            'name' => 'required',
            'letter' => 'required|array',
            'letter.*evaluation'=> 'required|string',
            'letter.*lower_boundary' => 'required|string',
        ]);

       $courses = $this->chain->getEnrollsByManyChain($request)->where('role_id',1)->distinct('course')->select('course')->pluck('course');
       $letter = Letter::firstOrCreate([
        'name' => $request->name,
        'chain' => json_encode($request->except(['name', 'letter'])),
        ]); 
        $letters = array_values(collect($request->letter)->sortBy('lower_boundary')->reverse()->toArray());

        foreach($letters as $key => $letter_detail)
        {
            if($key == 0)
                $letter_detail['higher_boundary'] = 100;

            if($key > 0)
                $letter_detail['higher_boundary'] = $letters[$key-1]['lower_boundary'];
            
            if($key+1 == count($letters)){
                $letter_detail['lower_boundary'] = 0;

            }

            $letter->details()->create([
                'evaluation' => $letter_detail['evaluation'],
                'lower_boundary' => $letter_detail['lower_boundary'],
                'higher_boundary' => $letter_detail['higher_boundary'],

            ]);
        }

        foreach($courses as $course)
        {
            Course::find($course)->update(['letter_id' => $letter->id]);
        }
        return response()->json(['message' => __('messages.letter.list'), 'body' => $letter], 200);
    }


    public function update(Request $request, $id)
    { 
        $request->validate([
            'letter' => 'array',
            'letter.*evaluation'=> 'required|string',
            'letter.*lower_boundary' => 'required|string',
        ]);

        $letter = Letter::find($id);
        if($request->filled('name'))
            $letter->update(['name' => $request->name]);
        
        if($request->filled('letter'))
        {
            $letter->details()->delete();
            $letters = array_values(collect($request->letter)->sortBy('lower_boundary')->reverse()->toArray());
            foreach($letters as $key => $letter_detail)
            {
                if($key == 0)
                    $letter_detail['higher_boundary'] = 100;
    
                if($key > 0)
                    $letter_detail['higher_boundary'] = $letters[$key-1]['lower_boundary'];
                
                if($key+1 == count($letters)){
                    $letter_detail['lower_boundary'] = 0;
    
                }
    
                $letter->details()->create([
                    'evaluation' => $letter_detail['evaluation'],
                    'lower_boundary' => $letter_detail['lower_boundary'],
                    'higher_boundary' => $letter_detail['higher_boundary'],
    
                ]);
            }
        }
            
        return response()->json(['message' => __('messages.letter.update'), 'body' => $letter], 200);
    }

    public function show($id)
    {
        $letter = Letter::where('id',$id)->with(['details', 'course'])->first();
        return response()->json(['message' => __('messages.letter.list'), 'body' => $letter], 200);
    }
    
}
