<?php

namespace App\Services;

use App\GradingSchema;
use App\GradingSchemaCourse;
use App\Course;
use App\Level;
use App\GradeCategory;
use App\GradeItems;

class GradingSchemaService {


    public function importGradeSchema($data,$courses,$parent_id = null){
        foreach($courses as $course){
            $course_total_category = GradeCategory::select('id')->whereNull('parent')->where('type','category')->where('course_id',$course->id)->first();
            foreach($data as $category){
                $cat = GradeCategory::create([
                    'name' => $category['name'],
                    'course_id' => $course->id,
                    'parent' => $parent_id?$parent_id:$course_total_category->id,
                    'hidden' =>isset($category['hidden']) ? $category['hidden'] : 0,
                    'calculation_type' =>isset($category['calculation_type']) ? json_encode([$category['calculation_type']]) : json_encode(['Natural']),
                    'locked' =>isset($category['locked']) ? $category['locked'] : 0,
                    'min' =>isset($category['min']) ? $category['min'] : 0,
                    'max' =>isset($category['max']) ? $category['max'] : null,
                    'type' => 'category',
                    'aggregation' =>isset($category['aggregation']) ? $category['aggregation'] : 'Value',
                    'weight_adjust' =>isset($category['weight_adjust']) ? $category['weight_adjust'] : 0,
                    'weights' =>isset($category['weight']) ? $category['weight'] : null,
                    'exclude_empty_grades' =>isset($category['exclude_empty_grades']) ? $category['exclude_empty_grades'] : 0,
                ]);
                
                if(isset($category['grade_items']) && count($category['grade_items']) > 0){
                    foreach($category['grade_items'] as $item){
                        $item = GradeCategory::create([
                            "parent"=>$cat->id,
                            "type" => "item",
                            "locked"=> $item['locked'],
                            "hidden"=> $item['hidden'],
                            "weight_adjust"=> $item['weight_adjust'],
                            "weight"=> $item['weight'],
                            "name"=> $item['name'],
                            "min"=>$item['min'],
                            "max"=> $item['max'],
                            "aggregation"=> $item['aggregation']
                        ]);
                    }
                }

                if(isset($category['categories']) && count($category['categories']) > 0){
                    Self::importGradeSchema($category['categories'],[$course],$cat->id);
                }
            }
        }

        return true;

    }
}