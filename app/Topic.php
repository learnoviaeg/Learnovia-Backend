<?php

namespace App;
use App\AcademicYear;
use App\AcademicType;
use App\Segment;
use App\Level;
use App\Course;
use Spatie\Permission\Models\Role;


use Illuminate\Database\Eloquent\Model;
//use App\Events\TopicCreatedEvent;

class Topic extends Model
{
    protected $table = "topics";

    protected $fillable = [
        'title',
        'filter',
    ];

    public function getFilterAttribute($value)
    {
        $filter =  json_decode($value);
        $names = array();
        if(property_exists($filter, 'years')){
        foreach($filter->years as $year)
        {
            $names['years'][] =AcademicYear::find($year)->name; 

        }}
        if(property_exists($filter, 'types')){
        foreach($filter->types as $type)
        {
            $names['types'][] =AcademicType::find($type)->name; 

        }}
        if(property_exists($filter, 'segments')){
        foreach($filter->segments as $segment)
        {
            $names['segments'][] =Segment::find($segment)->name; 
        }}

        if(property_exists($filter, 'levels')){
            foreach($filter->levels as $level)
            {
                $names['levels'][] =Level::find($level)->name; 
            }
        }
        if(property_exists($filter, 'courses')){
          foreach($filter->courses as $course)
            {
                $names['courses'][] =Course::find($course)->name; 
            }}        
        if(property_exists($filter, 'roles')){
        foreach($filter->roles as $role)
        {
            $names['roles'][] =Role::find($role)->name; 

        }}
        if(property_exists($filter, 'users')){
        foreach($filter->users as $user)
        {
            $names['users'][] =User::find($user)->name;     
        }}
        $filter=$names;
        return $filter;

    }


    public function enrolls()
    {
        return $this->belongsToMany('App\Enroll' , 'enroll_id' , 'id');
    }
    
}
