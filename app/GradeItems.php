<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class GradeItems extends Model
{
    protected $fillable = [
        'grade_category_id','item_id', 'name', 'type', 'index'
    ];

    // protected $appends = ['parent_aggregation'];

    public function GradeCategory()
    {
        return $this->belongsTo('App\GradeCategory', 'grade_category_id', 'id');
    }

    public function ItemType()
    {
        return $this->belongsTo('App\ItemType', 'item_type', 'id');
    }
    
    public function scale()
    {
        return $this->belongsTo('App\scale', 'scale_id', 'id');
    }

    public function UserGrade()
    {
        return $this->hasMany('App\UserGrade', 'grade_item_id', 'id');
    }

    public function weight()
    {
        if ($this->weight != 0)
            return $this->weight;
        return round(($this->grademax * $this->GradeCategory->percentage()) / $this->GradeCategory->total(), 3);
    }

    // public function getParentAggregationAttribute()
    // {
    //     return $this->GradeCategory->aggregation;
    // }
    // public static function clacWitheval($calculation, $grade)
    // {
    //     $str = $calculation . "(" . $grade . ")";
    //     $p = eval('return ' . $str . ';');
    //     return $p;
    // }

    public static function rads()
    {
        return [
            'abs',
            'log10',
            'exp',
            'sqrt',
        ];
    }
    public static function Allowed_functions()
    {
        return [
            'abs',
            'log10',
            'exp',
            'sqrt',
            'sin',
            'sinh',
            'asin',
            'asinh',
            'cos',
            'cosh',
            'acos',
            'acosh',
            'tan',
            'tanh',
            'atan',
            'atanh',
        ];
    }
    public function keepWeight()
    {
        $weight = [];
        $grade_items = $this->GradeCategory->GradeItems->where('weight',"!=" ,0);
        $allWeight = 0;
        foreach ($grade_items as $grade_item) {
            $allWeight += $grade_item->weight();
            $weight[] = $grade_item->weight();
        }
        if ($allWeight != 100 && $allWeight !=0) {
            $gcd = self::findGCD($weight, sizeof($weight));
            foreach ($weight as $w) {
                $devitions[] = $w / $gcd;
            }
            $calculations = (100 / array_sum($devitions));
            $count = 0;
            foreach ($grade_items as $grade_item) {
                $grade_item->update(['weight' => round($devitions[$count] * $calculations, 3)]);
                $count++;
            }
        }
    }

    public static function gcd($a, $b)
    {
        if ($a == 0)
            return $b;

            return self::gcd(fmod($b,$a), $a);
       
        }

    public static function findGCD($arr, $n)
    {
        $result = $arr[0];
        for ($i = 1; $i < $n; $i++)
            $result = self::gcd($arr[$i], $result);

        return $result;
    }

    public function userGrades()
    {
        return $this->hasMany('App\UserGrader', 'item_id', 'id')->where('item_type','item');
    }
}
