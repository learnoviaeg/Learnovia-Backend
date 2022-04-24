<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Modules\Attendance\Entities\AttendanceSession;
use App\Traits\Auditable;
use App\Segment;

class Course extends Model
{
    use Auditable;
    
    protected $fillable = ['name' , 'category_id','mandatory' , 'image' , 'description','short_name','progress','level_id','segment_id',
    'is_template','classes', 'letter_id','shared_lesson', 'index'];

    protected $dispatchesEvents = [
        'created' => \App\Events\CourseCreatedEvent::class,
    ];

    public function level()
    {
        return $this->belongsTo('App\Level','level_id','id');
    }

    public static function findByName($course_name)
    {
        return self::where('name',$course_name)->pluck('id')->first();
    }

    public static function findById($course_id)
    {
        return self::where('id',$course_id)->pluck('id')->first();
    }

    public function optionalCourses()
    {
        return self::whereMandatory(0);
    }

    protected $hidden = [
        'created_at', 'updated_at',
    ];
    
    public function category(){
        return $this->belongsTo('App\Category');
    }

    public function attachment()
    {
        return $this->hasOne('App\attachment', 'id', 'image');
    }
    
    public function sessions()
    {
        return $this->hasMany('Modules\Attendance\Entities\AttendanceSession','course_id','id');
    }

    public function timeline()
    {
        return $this->hasMany('App\Timeline','course_id','id');
    }

    public function getMandatoryAttribute()
    {
        $content=false;
        if($this->attributes['mandatory']==1)
            $content = true;
        return $content;
    }

    public function getImageAttribute()
    {
        if($this->attributes['image'] !=null){
            $attachment=attachment::find($this->attributes['image']);
            return $attachment->path;
        }
    }

    public function getClassesAttribute($value)
    {   if($value != null){
            $content= json_decode($value);
            return $content;
        }
        return $value;
    }

    public function gradeCategory()
    {
        return $this->hasMany('App\GradeCategory','course_id','id');
    }

    public function materials()
    {
        return $this->hasMany('App\Material','course_id','id');
    }
 
    public function letter()
    {
        return $this->belongsTo('App\Letter', 'letter_id', 'id');
    }

    public function Scale()
    {
        return $this->hasMany('App\course_scales','course_id','id');
    }

    // start function get name and value f attribute
    public static function get_year_name($old, $new)
    {
            $new_segment = Segment::find(intval($new['segment_id']));
            $target_year_id = [$new_segment->academicYear->id];
            $target_type_id = [$new_segment->academicType->id];

        $old_count = count($old);
        if ($old_count == 0) 
        {
            $year_id = $target_year_id;
        }else{
            if ($old['segment_id'] == $new['segment_id']) {
                $segment_id = [intval($new['segment_id'])];
                $year_id = $target_year_id;
            }else{
                $old_segment = Segment::find(intval($old['segment_id']));
                if ($new_segment->academicYear->id == $old_segment->academicYear->id) {
                    $year_id = $target_year_id;
                }else{
                    $year_id = [$new_segment->academicYear->id, $old_segment->academicYear->id];
                }
            }
        }
        return $year_id;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_type_name($old, $new)
    {
        $new_segment = Segment::find(intval($new['segment_id']));
            $target_type_id = [$new_segment->academicType->id];

        $old_count = count($old);
        if ($old_count == 0) 
        {
            $type_id = $target_type_id;
        }else{
            if ($old['segment_id'] == $new['segment_id']) {
                $segment_id = [intval($new['segment_id'])];
                $type_id    = $target_type_id;
            }else{
                $old_segment = Segment::find(intval($old['segment_id']));
                if ($new_segment->academicType->id == $old_segment->academicType->id) {
                    $type_id = $target_type_id;
                }else{
                    $type_id = [$new_segment->academicType->id, $old_segment->academicType->id];
                }
            }
        }
        return $type_id;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_level_name($old, $new)
    {
        $old_count = count($old);
        if ($old_count == 0) {
            $level_id = [intval($new['level_id'])];
        }else{
            if ($old['level_id'] == $new['level_id']) {
                $level_id = [intval($new['level_id'])];
            }else{
                $level_id = [intval($old['level_id']), intval($new['level_id'])];
            }
        }
        return $level_id;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_class_name($old, $new)
    {
        $create_intvals = array();
        $v1 = $new['classes'];
        $first   = str_replace("\"", "", $v1);
        $r       = $first;
        $move1   = trim($r[0], "[");
        $move2   = trim($move1, "]");
        $v1_edit = explode(",", $move2); 
        $intvals = array();
        foreach ($v1_edit as $key => $value) {
            array_push($create_intvals, intval($value));
        }

        $old_count = count($old);
        if ($old_count == 0) {
                /*$create_intvals = array();
                $v1 = $new['classes'];
                $first   = str_replace("\"", "", $v1);
                $r       = $first;
                $move1   = trim($r[0], "[");
                $move2   = trim($move1, "]");
                $v1_edit = explode(",", $move2); 
                $intvals = array();
                foreach ($v1_edit as $key => $value) {
                    array_push($create_intvals, intval($value));
                }*/
                $classes = $create_intvals;
        }else{
                $new_classes = $create_intvals;

                $v1      = $old['classes'];
                $first   = str_replace("\"", "", $v1);
                $r       = array($first);
                $move1   = trim($r[0], "[");
                $move2   = trim($move1, "]");
                $v1_edit = explode(",", $move2); 
                $intvals = array();
                foreach ($v1_edit as $key => $value) {
                    array_push($intvals, intval($value));
                }
            if ($intvals == $new_classes) {
                $classes = $new_classes;
            }else{
                $classes = array_merge($intvals, $new_classes);
            }
        }
        return $classes;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_segment_name($old, $new)
    {
        $old_count = count($old);
        if ($old_count == 0) {
            $segment_id = [intval($new['segment_id'])];
        }else{
            if ($old['segment_id'] == $new['segment_id']) {
                $segment_id = [intval($new['segment_id'])];
            }else{
                $segment_id = [intval($old['segment_id']), intval($new['segment_id'])];
            }
        }
        return $segment_id;
    }
    // end function get name and value attribute

    // start function get name and value f attribute
    public static function get_course_name($old, $new)
    {
        return null;
    }
    // end function get name and value attribute
}
