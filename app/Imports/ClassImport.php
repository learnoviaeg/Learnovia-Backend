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
            'level_id' => 'required|exists:levels,id'
        ],$messages)->validate();

        $class = Classes::firstOrCreate([
            'name' => $row['name'],
            'level_id' => $row['level_id']
        ]);
    }
}
