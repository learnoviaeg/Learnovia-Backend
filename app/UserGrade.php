<?php

namespace App;
use Modules\QuestionBank\Entities\userQuizAnswer;
use Modules\QuestionBank\Entities\userQuiz;

use Illuminate\Database\Eloquent\Model;

class UserGrade extends Model
{
    protected $fillable = [
        'grade_item_id', 'user_id', 'raw_grade', 'raw_grade_max', 'raw_grade_min','feedback'
    ];

    public function GradeItems()
    {
        return $this->belongsTo('App\GradeItems', 'grade_item_id', 'id');
    }
    public function user()
    {
        return $this->belongsTo('App\User', 'user_id', 'id');
    }

    public function calculateNaturalGrade()
    {
        $gradeitems = $this->GradeItems->GradeCategory->GradeItems;
        $result = 0;
        $total = 0;
        $temp = self::where('user_id', $this->user_id)->whereIn('grade_item_id', $gradeitems->pluck('id'))->pluck('grade_item_id');
        $items = GradeItems::wherein('id', $temp)->pluck('grademax');
        $total = array_sum($items->toArray());
        foreach ($gradeitems as $item) {
            $temp = self::where('user_id', $this->user_id)->where('grade_item_id', $item->id)->first();
            if ($temp == null)
                continue;
            $result += ($temp->final_grade / $item->grademax) * $item->weight();
        }
        $result = ($result * $total) / 100;
        return round($result, 3);
    }

    public function calculateSWMGrade()
    {
        $gradeitems = $this->GradeItems->GradeCategory->GradeItems;
        $temp = self::where('user_id', $this->user_id)->whereIn('grade_item_id', $gradeitems->pluck('id'))->pluck('grade_item_id');
        $finals = array_sum(self::where('user_id', $this->user_id)->whereIn('grade_item_id', $gradeitems->pluck('id'))->pluck('final_grade'));
        $categoryTotal = $this->GradeItems->GradeCategory->total();
        $max = array_sum(GradeItems::wherein('id', $temp)->pluck('grademax'));
        return ($finals * $categoryTotal) / $max;
    }

    public function calculateGrade()
    {
        switch ($this->GradeItems->GradeCategory->aggregation) {
            case 1:
                return $this->calculateNaturalGrade();
                break;
            case 2:
                return $this->calculateSWMGrade();
                break;
            default:
                return null;
                break;
        }
    }

    public static function quizUserGrade($user)
    {
        $user_quizzes=UserQuiz::where('user_id',$user->id)->pluck('id');
        foreach($user_quizzes as $user_quiz)
        {
            $quiz_lesson=UserQuiz::find($user_quiz)->quiz_lesson;
            if(isset($quiz_lesson) && $quiz_lesson->quiz->is_graded == 1)
            {
                //check if there is an essay not corrected
                $questions=$quiz_lesson->quiz->Question->where('question_type_id',4)->pluck('id');
                $userEssayCheckAnswer=UserQuizAnswer::where('user_quiz_id',$user_quiz)->whereIn('question_id',$questions)
                                            ->whereNull('correct')->count();
                if($userEssayCheckAnswer != 0)
                    continue;

                $courseSegmentQuiz=$quiz_lesson->lesson->courseSegment->id;
                // return $courseSegmentQuiz;
                $grade_items=GradeItems::where('name',$quiz_lesson->quiz->name)->get();
                foreach($grade_items as $grade_item)
                {
                    $CoSeGgrdItmSameName=$grade_item->GradeCategory->CourseSegment->id;
                    if($CoSeGgrdItmSameName == $courseSegmentQuiz)
                    {
                        $grade= UserQuiz::gradeMethod($quiz_lesson,$user);
                        $usergrade=UserGrade::where('user_id',$user->id)->where('grade_item_id',$grade_item->id)->first();
                        $usergrade->final_grade=$grade;
                        $usergrade->save();
                    }            
                }
            }
        }
    }
}
