<?php

namespace App\Services;

use App\GradingSchema;
use App\GradingSchemaCourse;
use App\Course;
use App\Level;
use App\GradeCategory;
use App\GradeItems;
use App\Events\GraderSetupEvent;

class GradingSchemaService {

    private $categoriesData = [];
    private $pointer = 0;

    public function importGradeSchema($data,$courses,$parent_id = null,$main_data = false){
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
                    'reference_category_id' => $this->categoriesData[$this->pointer] 
                ]);
                if($parent_id==null && isset($category['weight']) && isset($category['weight_adjust']) && $category['weight'] && $category['weight_adjust'])
                   event(new GraderSetupEvent($cat));
                $this->pointer++;
                if(isset($category['grade_items']) && count($category['grade_items']) > 0){
                    foreach($category['grade_items'] as $item){
                        $item = GradeCategory::create([
                            "parent"=>$cat->id,
                            "type" => "item",
                            'course_id' => $course->id,
                            "locked"=> $item['locked'],
                            "hidden"=> $item['hidden'],
                            "weight_adjust"=> $item['weight_adjust'],
                            "weight"=> $item['weight'],
                            "name"=> $item['name'],
                            "min"=>$item['min'],
                            "max"=> $item['max'],
                            "aggregation"=> $item['aggregation'],
                            'reference_category_id' => $this->categoriesData[$this->pointer]
                        ]);
                        $this->pointer++;
                    }
                }

                if(isset($category['categories']) && count($category['categories']) > 0){
                    Self::importGradeSchema($category['categories'],[$course],$cat->id);
                }
            }
            $this->pointer = 0;
        }

        return true;

    }


    public function importGradeSchemaDefault($data,$parent_id = null,$grade_schema_id = null,$main_data = false){
            foreach($data as $key => $category){
                $cat = GradeCategory::create([
                    'name' => $category['name'],
                    'parent' => $parent_id,
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
                    'grading_schema_id' => $grade_schema_id
                ]);

                $this->categoriesData[] = $cat->id; 
                
                if(isset($category['grade_items']) && count($category['grade_items']) > 0){
                    foreach($category['grade_items'] as $item_key=>$item){
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
                            "aggregation"=> $item['aggregation'],
                            "grading_schema_id" => $grade_schema_id
                        ]);
                        $this->categoriesData[] = $item->id; 
                    }
                }

                if(isset($category['categories']) && count($category['categories']) > 0){
                    Self::importGradeSchemaDefault($category['categories'],$cat->id,$grade_schema_id);
                }
            }
        return true;

    }
}