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
}
