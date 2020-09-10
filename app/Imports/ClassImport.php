<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Validator;
use App\Classes;
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
        
        $year_level_id = Level::find($row['level_id'])->yearlevel->pluck('id')->first();

        ClassLevel::firstOrCreate([
            'year_level_id' => $year_level_id,
            'class_id' => $class->id
        ]);
    }
}
