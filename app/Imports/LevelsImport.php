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
            'type_id' => 'required|exists:academic_types,id'
        ],$messages)->validate();

        $level = Level::create([
            'name' => $row['name'],
            'academic_type_id' => $row['type_id']
        ]);        
    }
}
