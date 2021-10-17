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
        'created_by',
    ];

    public function getFilterAttribute($value)
    {
        $filter =  json_decode($value);
        $names = array();
        if(property_exists($filter, 'years')){
        foreach($filter->years as $year)
        {
            $object['id'] = AcademicYear::find($year)->id;
            $object['name'] = AcademicYear::find($year)->name;
            $names['years'][] =$object;

        }}
        if(property_exists($filter, 'types')){
        foreach($filter->types as $type)
        {
            $object['id'] = AcademicType::find($type)->id;
            $object['name'] = AcademicType::find($type)->name;
            $names['types'][] =$object;
        }}
        if(property_exists($filter, 'segments')){
        foreach($filter->segments as $segment)
        {
            $object['id'] = Segment::find($segment)->id;
            $object['name'] = Segment::find($segment)->name;
            $names['segments'][] =$object;
        }}

        if(property_exists($filter, 'levels')){
            foreach($filter->levels as $level)
            {
                $object['id'] = Level::find($level)->id;
                $object['name'] = Level::find($level)->name;
                $names['levels'][] =$object;         
            }
        }
        if(property_exists($filter, 'classes')){
            foreach($filter->classes as $class)
            {
                $object['id'] = Classes::find($class)->id;
                $object['name'] = Classes::find($class)->name;
                $names['classes'][] =$object;
            }
        }
        if(property_exists($filter, 'courses')){
          foreach($filter->courses as $course)
            {
                $object['id'] = Course::find($course)->id;
                $object['name'] = Course::find($course)->name;
                $names['courses'][] =$object;
            }}        
        if(property_exists($filter, 'roles')){
        foreach($filter->roles as $role)
        {
            $object['id'] = Role::find($role)->id;
            $object['name'] = Role::find($role)->name;
            $names['roles'][] =$object;

        }}
        if(property_exists($filter, 'users')){
        foreach($filter->users as $user)
        {
            $object['id'] = User::find($user)->id;
            $object['name'] = User::find($user)->firstname;
            $names['users'][] =$object;
        }}
        $filter=$names;
        return $filter;

    }

    public function getCreatedByAttribute($value)
    {
        $user['id'] = $value;
        $user['name'] = User::find($value)->firstname;
        return $user;
    }


    public function enrolls()
    {
        return $this->belongsToMany('App\Enroll' , 'enroll_id' , 'id');
    }
    
}
