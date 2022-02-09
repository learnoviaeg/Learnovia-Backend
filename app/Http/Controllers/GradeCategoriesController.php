<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\ChainRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use App\GradeCategory;
use App\GradeItems;
use App\UserGrader;
use App\Enroll;
use App\Course;
use App\Events\GraderSetupEvent;
use App\Jobs\RefreshUserGrades;
use Modules\Assigments\Entities\AssignmentLesson;
use Modules\QuestionBank\Entities\quiz;

class GradeCategoriesController extends Controller
{

    public function __construct(ChainRepositoryInterface $chain)
    {
        $this->chain = $chain;
        $this->middleware('auth');
        $this->middleware(['permission:grade/category/add' ],   ['only' => ['store']]);
        $this->middleware(['permission:grade/category/update'],   ['only' => ['update']]);
        $this->middleware(['permission:grade/category/get'],   ['only' => ['index']]);
        $this->middleware(['permission:grade/category/delete'],   ['only' => ['destroy']]);
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $request->validate([
            'year' => 'exists:academic_years,id',
            'type' => 'exists:academic_types,id',
            'level' => 'exists:levels,id',
            'segment' => 'exists:segments,id',
            'courses'    => 'nullable|array',
            'courses.*'  => 'nullable|integer|exists:courses,id',
            'name' => 'string',
            'parent' => 'exists:grade_categories,id',
        ]);

        $grade_categories = GradeCategory::whereNull('instance_type')->where('type', 'category');
            if($request->filled('name'))
                $grade_categories->where('name','LIKE' , "%$request->name%");
            if($request->filled('parent'))
                $grade_categories->where('parent' ,$request->parent);
            if( $request->filled('courses')){
                $grade_categories->whereIn('course_id' ,$request->courses);
            }
            
        return response()->json(['message' => __('messages.grade_category.list'), 'body' => $grade_categories->with('Child.GradeItems','GradeItems')->get() ], 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'courses'    => 'nullable|array|required_without:levels',
            'courses.*'  => 'nullable|integer|exists:courses,id',
            'levels'    => 'nullable|array|required_without:courses',
            'levels.*'  => 'nullable|integer|exists:levels,id',
            'category.*.name' => 'required|string',
            'category.*.parent' => 'exists:grade_categories,id',
            'category.*.aggregation' => 'in:Value,Scale',
            'category.*.hidden' => 'boolean',
            'category.*.calculation_type' => 'nullable|in:Natural,Simple_weighted_mean',
            'category.*.locked' => 'boolean',
            'category.*.min'=>'between:0,100',
            'category.*.max'=>'between:0,100',
            'category.*.weight_adjust' => 'boolean',
            'category.*.exclude_empty_grades' => 'boolean'
        ]);
        if($request->filled('courses'))
            $courses = $request->courses;
        else{
            $enrolls = $this->chain->getEnrollsByManyChain($request)->where('user_id', 1);
            $courses = $enrolls->get()->pluck('course')->unique(); 
        }
        foreach($courses as $course){
            $course_total_category = GradeCategory::select('id')->whereNull('parent')->where('type','category')->where('course_id',$course)->first();
            foreach($request->category as $key=>$category){
                $cat = GradeCategory::create([
                    'course_id'=> $course,
                    'name' => $category['name'],
                    'parent' => isset($category['parent']) ? $category['parent'] : $course_total_category->id,
                    'hidden' =>isset($category['hidden']) ? $category['hidden'] : 0,
                    'calculation_type' =>isset($category['calculation_type']) ? json_encode([$category['calculation_type']]) : json_encode(['Natural']),
                    'locked' =>isset($category['locked']) ? $category['locked'] : 0,
                    'min' =>isset($category['min']) ? $category['min'] : 0,
                    'max' =>isset($category['max']) ? $category['max'] : null,
                    'aggregation' =>isset($category['aggregation']) ? $category['aggregation'] : 'Value',
                    'weight_adjust' =>isset($category['weight_adjust']) ? $category['weight_adjust'] : 0,
                    'weights' =>isset($category['weight']) ? $category['weight'] : null,
                    'exclude_empty_grades' =>isset($category['exclude_empty_grades']) ? $category['exclude_empty_grades'] : 0,
                ]);

                $enrolled_students = Enroll::where('course',$course)->where('role_id',3)->get()->pluck('user_id')->unique();
                foreach($enrolled_students as $student){
                    UserGrader::create([
                        'user_id'   => $student,
                        'item_type' => 'category',
                        'item_id'   => $cat->id,
                        'grade'     => null
                    ]);
                }
                event(new GraderSetupEvent($cat));
                $userGradesJob = (new \App\Jobs\RefreshUserGrades($this->chain , $cat));
                dispatch($userGradesJob);
                }
        }
        return response()->json(['message' => __('messages.grade_category.add'), 'body' => null ], 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $grade_categories = GradeCategory::where('id', $id)->with('Child.GradeItems','GradeItems')->first();
        return response()->json(['message' => __('messages.grade_category.list'), 'body' => $grade_categories], 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'string',
            'parent' => 'exists:grade_categories,id',
            'hidden' => 'boolean',
            'weight_adjust' => 'boolean'

        ]);
        $grade_category = GradeCategory::findOrFail($id);
        $grade_category->update([
            'name'   => isset($request->name) ? $request->name : $grade_category->name,
            'parent' => isset($request->parent) ? $request->parent : $grade_category->parent,
            'hidden' => isset($request->hidden) ? $request->hidden : $grade_category->hidden,
            'calculation_type' =>isset($request->calculation_type) ? json_encode([$request->calculation_type]) : json_encode($grade_category['calculation_type']),
            'locked' =>isset($request->locked) ? $request->locked  : $grade_category['locked'],
            'min' =>isset($request->min) ? $request->min : $grade_category['min'],
            'max' =>isset($request->max) ? $request->max : $grade_category['max'],
            'weight_adjust' =>isset($request->weight_adjust) ? $request->weight_adjust : $grade_category['weight_adjust'],
            'weights' =>isset($request->weight) ? $request->weight : $grade_category['weights'],
            'exclude_empty_grades' =>isset($request->exclude_empty_grades) ? $request->exclude_empty_grades : $grade_category['exclude_empty_grades'],
            'aggregation' =>isset($request->aggregation) ? $request->aggregation : $grade_category['aggregation'],
        ]);
        event(new GraderSetupEvent($grade_category));
        $userGradesJob = (new \App\Jobs\RefreshUserGrades($this->chain , $grade_category));
        dispatch($userGradesJob);

        return response()->json(['message' => __('messages.grade_category.update'), 'body' => null ], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $grade_category = GradeCategory::find($id);
        $top_parent_category = GradeCategory::where('course_id',$grade_category->course_id)->whereNull('parent')->where('type','category')->first();
        $grade_category->GradeItems()->update(['parent' => $top_parent_category->id]);
        $grade_category->child()->update(['parent' => $top_parent_category->id]);
        $parent_Category = GradeCategory::find($grade_category->parent);
        $grade_category->delete();
        event(new GraderSetupEvent($parent_Category));
        $userGradesJob = (new \App\Jobs\RefreshUserGrades($this->chain , $parent_Category));
        dispatch($userGradesJob);

        return response()->json(['message' => __('messages.grade_category.delete'), 'body' => null], 200);
    }


    public function weight_adjust(Request $request)
    {
        $request->validate([
            'instance' => 'required|array',
            'instance.*.id' => 'required|exists:grade_categories,id',
            'instance.*.weight' => 'numeric|min:0|max:100',
            'instance.*.weight_adjust' => 'required|boolean',
        ]);

        foreach($request->instance as $instance)
        {
            $category = GradeCategory::find($instance['id']);
            $category->update([
                'name'   =>  isset($instance['name']) ? $instance['name'] : $category->name,
                'hidden' => isset($instance['hidden']) ? $instance['hidden'] : $category->hidden,
                'calculation_type' =>isset($instance['calculation_type']) ? $instance['calculation_type'] : json_encode($category->calculation_type),
                'locked' =>isset($instance['locked']) ? $instance['locked']  : $category->locked,
                'min' =>isset($instance['min']) ? $instance['min'] : $category->min,
                'max' =>isset($instance['max']) ? $instance['max'] : $category->max,
                'weight_adjust' =>isset($instance['weight_adjust']) ? $instance['weight_adjust'] : $category->weight_adjust,
                'weights' =>isset($instance['weight']) ? $instance['weight'] : $category->weights,
                'exclude_empty_grades' =>isset($instance['exclude_empty_grades']) ? $instance['exclude_empty_grades'] : $category->exclude_empty_grades,
            ]);
            if($category->instance_type != null){
                if($category->instance_type == 'Quiz'){
                    if($category->weights > 0)
                        quiz::where('id', $category->instance_id )->update(['is_graded' => 1]);
                    else
                        quiz::where('id', $category->instance_id )->update(['is_graded' => 0]);
                }
                   
                if($category->instance_type == 'Assignment'){
                    if($category->weights > 0)
                        AssignmentLesson::where('assignment_id', $category->instance_id )->update(['is_graded' => 1]);
                    else
                        AssignmentLesson::where('assignment_id', $category->instance_id )->update(['is_graded' => 0]);
                }          
            }
            event(new GraderSetupEvent($category->Parents));
            $userGradesJob = (new \App\Jobs\RefreshUserGrades($this->chain , $category->Parents));
            dispatch($userGradesJob);
        }
        return response()->json(['message' => __('messages.grade_category.update'), 'body' => null ], 200);
    }

    public function reArrange(Request $request)
    {
        $request->validate([
            'grade_cat_id' => 'required|exists:grade_categories,id',
            'index' => 'integer',
            'parent' => 'exists:grade_categories,id'
        ]);

        $category = GradeCategory::find($request->grade_cat_id);
        $oldIndex=$category->index;
        if(isset($request->index))
        {
            $cat=GradeCategory::where('parent',$category->parent)->where('course_id',$category->course_id);
            if($request->index < $oldIndex)
            {
                foreach($cat->where('index','>=',$request->index)->where('index','<',$oldIndex)->get() as $updateIndex)
                {
                    $updateIndex->index+=1;
                    $updateIndex->save();
                }
            }
            elseif($request->index > $oldIndex)
            {
                foreach($cat->where('index','<=',$request->index)->where('index','>',$oldIndex)->get() as $updateIndex)
                {
                    $updateIndex->index-=1;
                    $updateIndex->save();
                }
            }
            $category->index=$request->index;
        }

        if(isset($request->parent))
        {
            $all=GradeCategory::where('parent',$category->parent)->where('course_id',$category->course_id);
            foreach($all->where('index','>',$oldIndex)->get() as $gradeinx)
            {
                $gradeinx->index-=1;
                $gradeinx->save();
            }

            $maxIndex=GradeCategory::where('parent',$request->parent)->max('index');
            $category->index=$maxIndex+1;
            $category->parent=$request->parent;
        }

        $category->save();

        return 'Done';
    }
}
