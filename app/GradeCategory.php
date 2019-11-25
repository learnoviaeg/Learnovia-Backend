<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class GradeCategory extends Model
{
    protected $fillable = ['name', 'course_segment_id', 'parent', 'aggregation', 'aggregatedOnlyGraded', 'hidden', 'id_number'];
    public function Child()
    {
        return $this->hasMany('App\GradeCategory', 'parent', 'id');
    }
    public function Parents()
    {
        return $this->hasOne('App\GradeCategory', 'id', 'parent');
    }
    public function CourseSegment()
    {
        return $this->belongsTo('App\CourseSegment', 'course_segment_id', 'id');
    }
    public function GradeItems()
    {
        return $this->hasMany('App\GradeItems', 'grade_category', 'id');
    }
    public function total()
    {
        $result = 0;
        $gradeitems = $this->GradeItems;
        foreach ($gradeitems as $item)
            $result += $item->grademax;
        $child = $this->Child;
        foreach ($child as $item)
            $result += $item->total();
        return $result;
    }

    public function percentage()
    {
        $grade_items = $this->GradeItems;
        $result = 100;
        foreach ($grade_items as $Item) {
            $result -=  $Item->override;
        }
        return $result;
    }
    public function naturalTotal()
    {
        $grade_items = $this->GradeItems->where('override', '!=', 0);
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
        if ($this->override != 0)
            return $this->override;
        if(!$this->Parents)
            return 100;
        return ($this->total() / $this->Parents->total()) * 100 ;
    }

    public function depth(){
        if($this->Parents == null)
            return 1;
        return 1 + $this->Parents->depth();
    }

    public function path(){
        if($this->Parents == null)
            return $this;
        $result = collect();
        $category = $this;
        while(true){
            $result->push($category);
            if($category->Parents == null)
                break;
            $category = $category->Parents;
        }
        return $result;
    }
}
