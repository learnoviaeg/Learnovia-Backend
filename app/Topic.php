<?php

namespace App;
use App\AcademicYear;
use App\AcademicType;
use App\Segment;
use App\Level;
use App\Course;
use App\User;
use App\Classes;

use Spatie\Permission\Models\Role;


use Illuminate\Database\Eloquent\Model;

class Topic extends Model
{
    protected $table = "topics";

    protected $fillable = [
        'title',
        'filter',
        'created_by'
    ];

    public function getFilterAttribute($value)
    {
        $filter =  json_decode($value);
        $names = array();
        if(property_exists($filter, 'years')){
        foreach($filter->years as $year)
        {
            $year_details = AcademicYear::find($year);
            $object['id'] =$year_details->id;
            $object['name'] =$year_details->name;
            $names['years'][] =$object;

        }}
        if(property_exists($filter, 'types')){
        foreach($filter->types as $type)
        {
            $type_details = AcademicType::find($type);
            $object['id'] = $type_details->id;
            $object['name'] = $type_details->name;
            $names['types'][] =$object;
        }}
        if(property_exists($filter, 'segments')){
        foreach($filter->segments as $segment)
        {
            $segment_details = Segment::find($segment);
            $object['id'] = $segment_details->id;
            $object['name'] =$segment_details->name;
            $names['segments'][] =$object;
        }}

        if(property_exists($filter, 'levels')){
            foreach($filter->levels as $level)
            {
                $level_details = Level::find($level);
                $object['id'] =$level_details->id;
                $object['name'] =$level_details->name;
                $names['levels'][] =$object;         
            }
        }
        if(property_exists($filter, 'classes')){
            foreach($filter->classes as $class)
            {
                $class_details = Classes::find($class);
                $object['id'] =$class_details->id;
                $object['name'] =$class_details->name;
                $names['classes'][] =$object;
            }
        }
        if(property_exists($filter, 'courses')){
          foreach($filter->courses as $course)
            {
                $course_details = Course::find($course);
                $object['id'] = $course_details->id;
                $object['name'] = $course_details->name;
                $names['courses'][] =$object;
            }}        
        if(property_exists($filter, 'roles')){
        foreach($filter->roles as $role)
        {
            $role_details= Role::find($role);
            $object['id'] =$role_details->id;
            $object['name'] =$role_details->name;
            $names['roles'][] =$object;
        }}
        if(property_exists($filter, 'user_id')){
        foreach($filter->user_id as $user)
        {
            $user_details = User::find($user);
            $object['id'] = $user_details->id;
            $object['name'] = $user_details->firstname;
            $object['image'] = $user_details->image;
            $names['users'][] =$object;
        }}
        $filter=$names;
        return $filter;
    }


    public function enrolls()
    {
        return $this->belongsToMany('App\Enroll' , 'enroll_id' , 'id');
    }

    public function created_by()
    {
        return $this->belongsTo('App\User' , 'created_by' , 'id');

    }
    
}
