<?php

namespace App\Imports;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Validator;
use App\AcademicYearType;
use App\Level;
use App\YearLevel;
use App\AcademicType;

class LevelsImport implements ToModel , WithHeadingRow
{

    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        $messages = [
            'exists' => 'Type with id '.$row['type_id'].' assigned to level '.$row['name'].' is not assigned to a year.',
        ];
        $validator = Validator::make($row,[
            'name' => 'required',
            'type_id' => 'required|exists:academic_year_types,academic_type_id',
            'segment_id' => 'exists:segments,id'
        ],$messages)->validate();

        $year_type_id = AcademicType::find($row['type_id'])->yearType->pluck('id')->first();
        $segment=Segment::where('academic_type_id',$type)->where('end_date','>=',Carbon::now())->pluck('id')->first();
        if(isset($row['segment_id']))
            $segment=$row['segment_id'];

        $level = Level::create([
            'name' => $row['name'],
        ]);

        $year_type=YearLevel::firstOrCreate([
            'academic_year_type_id' => $year_type_id,
            'level_id' => $level->id,
        ]);

        SegmentLevel::firstOrCreate([
            'segment_id' => $segment->id,
            'level_id' => $level_id->id,
            'year_level_id' => $year_type->id
        ]);
        
    }
}
