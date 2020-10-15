<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Validator;
use App\Classes;
use App\Segment;
use App\SegmentClass;
use App\Level;
use App\ClassLevel;


class ClassImport implements ToModel , WithHeadingRow
{
   /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        $messages = [
            'exists' => 'Level with id '.$row['level_id'].' assigned to class '.$row['name'].' is not assigned to a type.',
        ];
        $validator = Validator::make($row,[
            'name' => 'required',
            'level_id' => 'required|exists:year_levels,level_id'
        ],$messages)->validate();

        $class = Classes::create([
            'name' => $row['name'],
        ]);
        
        $year_level_id = Level::find($row['level_id'])->yearlevel->first();

        $class_level=ClassLevel::firstOrCreate([
            'year_level_id' => $year_level_id->id,
            'class_id' => $class->id
        ]);
            // dd($year_level_id->yearType->academictype);
        $segments=Segment::where('academic_type_id',$acadymic_type=$year_level_id->yearType->pluck('academic_type_id')->first());
        if(isset($row['segment_id']))
            $segments->where('id',$row['segment_id']);
        foreach($segments->get() as $segment)
        {
            SegmentClass::firstOrCreate([
                'segment_id' => $segment->id,
                'class_level_id' =>$class_level->id
            ]);
        }

    }
}
