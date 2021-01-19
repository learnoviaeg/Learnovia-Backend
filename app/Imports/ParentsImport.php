<?php

namespace App\Imports;

use Validator;
use App\User;
use App\Parents;
use App\Enroll;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Spatie\Permission\Models\Role;

class ParentsImport implements ToModel, WithHeadingRow
{
    /**
    * @param array $row
    */
    public function model(array $row)
    {
        Validator::make($row,[
            'parent'=>'required|exists:users,username',
        ])->validate();

        $childs = 'child';
        $i=1;

        while(isset($row[$childs.$i]))
        {
            $parent=User::where('username',$row['parent'])->pluck('id')->first();
            $child=User::where('username',$row[$childs.$i])->pluck('id')->first();
            $assign = Parents::firstOrCreate([
                'parent_id' => $parent,
                'child_id' => $child,
                'current' => isset($row['current']) ? $row['current'] : 0
            ]);

            $students = Enroll::where('user_id',$child)->get();

            foreach($students as $student){

                Enroll::firstOrCreate([
                    'course_segment' => $student->course_segment,
                    'user_id' => $parent,
                    'role_id'=> 7,
                    'year' => $student->year,
                    'type' => $student->type,
                    'level' => $student->level,
                    'class' => $student->class,
                    'segment' => $student->segment,
                    'course' => $student->course
                ]);
            }
            
            $i++;
        }
    }
}
