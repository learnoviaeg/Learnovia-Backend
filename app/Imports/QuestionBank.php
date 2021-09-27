<?php

namespace App\Imports;

use App\Course;
use App\AcademicYearType;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use App\ClassLevel;
use App\CourseSegment;
use App\Segment;
use App\Http\Controllers\CourseController;
use App\SegmentClass;
use Illuminate\Http\Request;
use App\YearLevel;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use App\GradeCategory;
use Validator;
use Modules\QuestionBank\Entities\QuestionsCategory;
use Modules\QuestionBank\Entities\Questions;
use Modules\QuestionBank\Entities\QuestionsType;
use Modules\QuestionBank\Entities\QuestionsAnswer;

class QuestionBank implements ToModel , WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */

    public $flage = 0;
    public $mcq = array();
    public $key = 0;

    public function model(array $row )
    {

        
            // $course = Course::firstOrCreate([
            //     'name' => isset($row['course_name']) ? $row['course_name'] : null ,
            // ]);
        $course_id = Course::where('short_name' , $row['short_name'])->pluck('id')->first();
        $question_category = QuestionsCategory::firstOrCreate([
            'name' => isset($row['category_name']) ? $row['category_name'] : null ,
            'course_id' => isset($course_id) ? $course_id : null,
            ]);
        $question_category_id = QuestionsCategory::where('course_id' , $course_id)->pluck('id')->first();
        if($row['qtype'] == 'multichoice')
        {
            $question_type_id = QuestionsType::where('name' , 'MCQ')->pluck('id')->first();
            if($row['question_id'] == $this->flage)
            {
                $data = [
                    'text' => $row['question_text'],
                    'course_id' => $course_id,
                    'question_type_id' => $question_type_id,
                    'question_category_id' => $question_category_id, 
                    'mcq_type' => 1,
                ]; 
                $choices['is_true'] = ( $row['fraction'] == 1 ) ? TRUE : FALSE;
                $choices['content'] = $row['answer'];
                $choices['key'] = ++$this->key;
                $this->mcq[] = $choices;
                $data['content'] = $this->mcq;
                $data['content'] = json_encode($data['content']);
                $question_id = Questions::where('text' , $row['question_text'] )->pluck('id')->first();
                $question = Questions::find($question_id);
                $question->update($data);
            }
            else
            {
                $this->mcq = array();
                $this->key=0;
                $data = [
                    'text' => $row['question_text'],
                    'course_id' => $course_id,
                    'question_type_id' => $question_type_id,
                    'question_category_id' => $question_category_id, 
                ]; 
                $choices['is_true'] = ($row['fraction'] == 1 ) ? TRUE : FALSE;
                $choices['content'] = $row['answer'];
                $choices['key'] = ++$this->key;
                $this->mcq[] = $choices;
                $data['content'] = $this->mcq;
                $data['content'] = json_encode($data['content']);
                if($question_id = Questions::where('text' , $row['question_text'] )->pluck('id')->first())
                {
                  $question = Questions::find($question_id);
                  $question->update($data);
                }
                else
                $question = Questions::Create($data);
            }
        }
        elseif($row['qtype'] == 'truefalse')
        {
            $question_type_id = QuestionsType::where('name' , 'True/False')->pluck('id')->first();
            $tru_false = array();
            $data = [
                'text' => $row['question_text'],
                'course_id' => $course_id,
                'question_type_id' => $question_type_id,
                'question_category_id' => $question_category_id,
                
            ]; 
            if($row['fraction'] == 1)
            {
                $tru_false['is_true'] = $row['answer'];
                $tru_false['and_why'] = null;
                $data['content'] = json_encode($tru_false); 
                $question = Questions::firstOrCreate($data);
            }
        }
        elseif($row['qtype'] == 'essay')
        {
            $question_type_id = QuestionsType::where('name' , 'Essay')->pluck('id')->first();
            $data = [
                'text' => $row['question_text'],
                'course_id' => $course_id,
                'question_type_id' => $question_type_id,
                'question_category_id' => $question_category_id, 
                'content' => null
            ]; 
            $question = Questions::firstOrCreate($data);
        }
        else{
           // die('Question Type not found');
        }

          $this->flage = $row['question_id'] ;

    }

}
