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
            'type_id' => 'required|exists:academic_year_types,academic_type_id'
        ],$messages)->validate();

        $year_type_id = AcademicType::find($row['type_id'])->yearType->pluck('id')->first();

        $level = Level::create([
            'name' => $row['name'],
        ]);

        YearLevel::firstOrCreate([
            'academic_year_type_id' => $year_type_id,
            'level_id' => $level->id,
        ]);
        
    }
}
