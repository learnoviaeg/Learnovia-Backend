<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class GradeCategory extends Model
{
    protected $fillable = ['name','course_segment_id','parent','aggregation','aggregatedOnlyGraded','hidden' , 'id_number'];
    Public Function Child(){
        return $this->hasMany('App\GradeCategory','parent','id');

    }
    Public Function Parents(){
        return $this->hasOne('App\GradeCategory','id','parent');

    }
    public function CourseSegment()
    {
        return $this->belongsTo('App\CourseSegment', 'course_segment_id', 'id');
    }
    public function GradeItems()
    {
        return $this->hasMany('App\GradeItems','grade_category','id');
    }

    public static function Depth($id){
        $ParentOfGivenGradeCat=self::where('id',$id)->first('parent');
            $i=1;
            $Flag=true;
            if(is_null($ParentOfGivenGradeCat->parent)){
                return $i;
            }else{
            $i+=1;
                $CurrentParent=self::where('id',$ParentOfGivenGradeCat->parent)->first('parent');
                if($CurrentParent->parent!=null){
                    $i++;
                    while($Flag==true){
                        $CurrentParent=self::where('id',$CurrentParent->parent)->first('parent');
                        if($CurrentParent->parent==null){
                            $Flag=False;
                            return $i;
                        }else
                        $i++;                    
                    }
                }            
            return $i;
            }
    }

    public static function Path($id){
        $ParentOfGivenGradeCat=self::where('id',$id)->first();
            $Flag=true;
            $path=array();
            array_push($path,$ParentOfGivenGradeCat);
            if(is_null($ParentOfGivenGradeCat->parent)){
                return $path;
            }else{
                $CurrentParent=self::where('id',$ParentOfGivenGradeCat->parent)->first();
                array_push($path,$CurrentParent);
                if($CurrentParent->parent!=null){
                    array_push($path,$CurrentParent);
                    while($Flag==true){
                        $CurrentParent=self::where('id',$CurrentParent->parent)->first();
                        array_push($path,$CurrentParent);
                        if($CurrentParent->parent==null){
                            $Flag=False;
                        }                    
                    }
                }            
            return $path;
            }
    }
}
   