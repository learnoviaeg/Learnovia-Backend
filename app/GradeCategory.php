<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use stdClass;

class GradeCategory extends Model
{
    protected $fillable = ['name', 'course_id', 'parent', 'hidden' ,'instance_type' ,'instance_id','lesson_id',
                           'min','max' ,'calculation_type' , 'locked','exclude_empty_grades','weight_adjust'];
    public function Child()
    {
        return $this->hasMany('App\GradeCategory', 'parent', 'id');
    }
    public function Parents()
    {
        return $this->hasOne('App\GradeCategory', 'id', 'parent');
    }
    public function course()
    {
        return $this->belongsTo('App\Course', 'course_id', 'id');
    }
    public function GradeItems()
    {
        return $this->hasMany('App\GradeItems', 'grade_category_id', 'id');
    }
    public function Children()
    {
        return $this->Child()->with(['Children', 'GradeItems']);
    }
    public function total()
    {
        $result = 0;
        $gradeitems = $this->GradeItems;
        foreach ($gradeitems as $item)
            $result += $item->grademax;
        $child = $this->Children;
        foreach ($child as $item)
            $result += $item->total();
        if($result == 0)$result = 1;
        return $result;
    }

    public function percentage()
    {
        $grade_items = $this->GradeItems;
        $result = 100;
        foreach ($grade_items as $Item) {
            $result -=  $Item->weight;
        }
        return $result;
    }
    public function naturalTotal()
    {
        $grade_items = $this->GradeItems->where('weight', '!=', 0);
        $total = $this->total();
        foreach ($grade_items as $grades) {
            $total -= $grades->grademax;
        }
        return $total;
    }
    public function grade_category_total()
    {
        $total = 0;
        $grade_items = $this->GradeItems;
        foreach ($grade_items as $grade_item) {
            $total += $grade_item->weight();
        }
        $childs = $this->Child;
        foreach ($childs as $child) {
            $total += $child->grade_category_total();
        }
        return $total;
    }

    public function weight()
    {
        if ($this->weight != 0)
            return $this->weight;
        if (!$this->Parents)
            return 100;
        return round(($this->total() / $this->Parents->total()) * 100, 3);
    }

    public function depth()
    {
        if ($this->Parents == null)
            return 1;
        return 1 + $this->Parents->depth();
    }

    public function path()
    {
        if ($this->Parents == null)
            return $this;
        $result = collect();
        $category = $this;
        while (true) {
            $result->push($category);
            if ($category->Parents == null)
                break;
            $category = $category->Parents;
        }
        return $result;
    }

    public function getUsergrades($userid){
        $children = $this->Children;
        $items = $this->GradeItems;
        foreach($items as $item){
            $item->grade = '-';
            $item->feedback = '-';
            $item->precentage = '-';
            $item->letter = '-';
            $usergrade = UserGrade::where('grade_item_id' , $item->id)->where('user_id' , $userid)->first();
            if(isset($usergrade))
            {
                if($usergrade != null)
                {
                    $item->grade = $usergrade->final_grade;
                    $item->feedback=$usergrade->feedback;
                    if($usergrade->raw_grade_max != 0)
                        $item->precentage=((($usergrade->final_grade)*100)/$usergrade->raw_grade_max);
                    $gpa_letter=Letter::find($usergrade->GradeItems->GradeCategory->CourseSegment->letter_id);
                    $gpa_letter = unserialize($gpa_letter['formate']);
                    if($gpa_letter)
                    foreach($gpa_letter as $gpa)
                            if($item->percentage < $gpa['boundary'])
                                $item->letter=$gpa['name'];
                }
            }

        }
        foreach($children as $child){
            $child->getUsergrades($userid);
        }
    }

    public function userGrades()
    {
        return $this->hasMany('App\UserGrader', 'item_id', 'id')->where('item_type','category');
    }
    public function getCalculationTypeAttribute($value)
    {
        $content= json_decode($value);
        return $content;
    }

}
