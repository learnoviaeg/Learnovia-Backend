<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class LessonEcport implements FromCollection, WithHeadings
{
    protected $fields = ['id','name','course'];

    function __construct($lessons) {
        $this->lessons = $lessons;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $lessons=$this->lessons;
        $export = collect();
        foreach ($lessons as $lesson) {
            $lesson['id'] = $lesson->id;
            $lesson['name'] = $lesson->name;
            $lesson['course'] = $lesson->course;
            $lesson->setHidden([])->setVisible($this->fields);
            $export->push($lesson);
        }
        
        return $export;
    }

    public function headings(): array
    {
        return $this->fields;
    }
}
