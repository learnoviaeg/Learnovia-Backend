<?php

namespace App\Imports;

use App\Dictionary;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Validator;

class LanguageImport implements ToModel , WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        Validator::make($row,[
            'language' => 'exists:languages,id'
        ])->validate();

        Dictionary::firstOrCreate([
            'language' => $row['language'],
            'key' => $row['key'],
            'value' => $row['value'],
        ]);
    }
}
