<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\ChainRepositoryInterface;
use App\Enroll;
use App\Material;
use Illuminate\Support\Facades\Auth;
use App\Lesson;
use App\Level;
use App\Classes;
use App\Paginate;
use App\attachment;
use App\CourseItem;
use App\Helpers\CoursesHelper;
use Modules\Assigments\Entities\assignment;
use DB;
use App\SecondaryChain;
use Carbon\Carbon;
use Modules\UploadFiles\Entities\file;
use Modules\UploadFiles\Entities\media;
use Modules\UploadFiles\Entities\page;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Builder;

class MaterialsController extends Controller
{
    protected $chain;

    public function __construct(ChainRepositoryInterface $chain)
    {
        $this->chain = $chain;
        $this->middleware(['permission:material/get'],   ['only' => ['index' , 'getMaterials']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request,$count = null)
    {
        $request->validate([
            'year' => 'exists:academic_years,id',
            'type' => 'exists:academic_types,id',
            'level' => 'exists:levels,id',
            'segment' => 'exists:segments,id',
            'courses'    => 'nullable|array',
            'courses.*'  => 'nullable|integer|exists:courses,id',
            'sort_in' => 'in:asc,desc',
            'item_type' => 'string|in:page,media,file',
            'class' => 'nullable|integer|exists:classes,id',
            'lesson' => 'nullable|integer|exists:lessons,id'
        ]);
        if(isset($request->item_id)){
            $check = Material::where('type',$request->item_type)->where('item_id',$request->item_id)->first();
            if(!isset($check))
                return response()->json(['message' => __('messages.error.not_found'), 'body' => null], 400);
        }

        $lessons = $this->chain->getEnrollsByChain($request)->where('user_id',Auth::id());
        $lessons = $lessons->with('SecondaryChain')->get()->pluck('SecondaryChain.*.lesson_id')->collapse();

        if($request->has('lesson')){
            if(!in_array($request->lesson,$lessons->toArray()))
                return response()->json(['message' => __('messages.error.no_active_for_lesson'), 'body' => []], 400);

            $lessons = [$request->lesson];
        }

        $page = Paginate::GetPage($request);
        $paginate = Paginate::GetPaginate($request);

        $materials_query =  Material::orderBy('created_at','desc');

        $material = $materials_query->with(['lesson','course.attachment'])->whereIn('lesson_id',$lessons);
        if($request->user()->can('site/course/student')){
            $material
            ->where('visible',1)
            ->where('publish_date' ,'<=', Carbon::now())
            ->where(function($query) {                //Where accessible
                $query->whereHasMorph(
                    'item',
                    [
                        'Modules\Page\Entities\page',
                        'Modules\UploadFiles\Entities\media',
                        'Modules\UploadFiles\Entities\file',
                    ],
                    function($query){
                        $query->doesntHave('courseItem')
                        ->orWhereHas('courseItem.courseItemUsers', function (Builder $query){
                            $query->where('user_id', Auth::id());
                        });
                    }
                );
            });
        }


        if($request->has('item_type'))
            $material->where('type',$request->item_type);

        if($count == 'count'){
             //copy this counts to count it before filteration
            $query=clone $materials_query;

            $all=$query->select(DB::raw
                            (  "COUNT(case `type` when 'file' then 1 else null end) as file ,
                                COUNT(case `type` when 'media' then 1 else null end) as media ,
                                COUNT(case `type` when 'page' then 1 else null end) as page"
                            ))->first()->only(['file','media','page']);
            $cc['all']=$all['file']+$all['media']+$all['page'];

            $counts = $materials_query->select(DB::raw
                (  "COUNT(case `type` when 'file' then 1 else null end) as file ,
                    COUNT(case `type` when 'media' then 1 else null end) as media ,
                    COUNT(case `type` when 'page' then 1 else null end) as page"
                ))->first()->only(['file','media','page']);
            $counts['all']=$cc['all'];

            return response()->json(['message' => __('messages.materials.count'), 'body' => $counts], 200);
        }
        $result['last_page'] = Paginate::allPages($material->count(),$paginate);
        $result['total']= $material->count();

        $AllMat=$material->skip(($page)*$paginate)->take($paginate)->with(['lesson.SecondaryChain.Class'])->get();

        foreach($AllMat as $one){
            $one->class = $one->lesson->SecondaryChain->pluck('class')->unique();
            $one->level = Level::whereIn('id',$one->class->pluck('level_id'))->first();
            unset($one->lesson->SecondaryChain);
        }
        $result['data'] =  $AllMat;
        $result['current_page']= $page + 1;
        $result['per_page']= count($result['data']);

        return response()->json(['message' => __('messages.materials.list'), 'body' =>$result], 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $material = Material::find($id);

        if(!isset($material))
            return response()->json(['message' => __('messages.error.not_found'), 'body' => null], 400);

        if(!isset($material->getOriginal()['link']))
            return response()->json(['message' => 'No redirection link', 'body' => null], 400);

        if(isset($material->getOriginal()['link'])){

            $url = $material->getOriginal()['link'];
            if(str_contains($material->getOriginal()['link'],'youtube') && $material->media_type != 'Link'){
                if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i',$material->getOriginal()['link'], $match)){
                    $url = 'https://www.youtube.com/embed/'.$match[1];
                }
            }
            return redirect($url);
        }

    }

    public function Material_Details(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:materials,id',
        ]);

        $material = Material::find($request->id);

        if(!isset($material))
            return response()->json(['message' => __('messages.error.not_found'), 'body' => null], 400);

        if ($material->type == "media") {

            $path=public_path('/storage')."/media".substr($material->getOriginal()['link'],
            strrpos($material->getOriginal()['link'],"/"));
            $result = media::find($material->item_id);
            $extension=substr(strstr($result->type, '/'), 1);
        }
        if ($material->type == "file") {

            $path=public_path('/storage')."/files".substr($material->getOriginal()['link'],
            strrpos($material->getOriginal()['link'],"/"));
            $result = file::find($material->item_id);
            $extension = $result->type;
        }
        if($material->type == 'page'){
            $result = page::find($material->item_id);
        }

        if(!file_exists($path))
            return response()->json(['message' => __('messages.error.not_found'), 'body' => null], 400);

        $fileName = $result->name.'.'.$extension;
        $fileName=str_replace('/','-',$fileName);
        $fileName=str_replace('\\','-',$fileName);
        $headers = ['Content-Type' => 'application/'.$extension];

        return response()->download($path , $fileName , $headers);
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
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }


    public function downloadAssignment(Request $request)
    {

        $request->validate([
            'id' => 'required|exists:assignments,id',
        ]);

        $assigment = Assignment::find($request->id);
        if(!isset($assigment))
        {
            return response()->json(['message' => __('messages.error.not_found'), 'body' => null], 400);
        }
        $attachment = attachment::find($assigment->attachment_id);
        $path = public_path('/storage/assignment').substr($attachment->getOriginal()['path'],
        strrpos($attachment->getOriginal()['path'],"/"));

        if(!file_exists($path))
        return response()->json(['message' => __('messages.error.not_found'), 'body' => null], 400);

        $fileName = $attachment->name;
        $headers = ['Content-Type' => 'application/'.$attachment->extension];

        return response()->download($path , $fileName , $headers);

    }

    public function getMaterials(Request $request,$count = null)
    {
        $request->validate([
            'year' => 'exists:academic_years,id',
            'type' => 'exists:academic_types,id',
            'level' => 'exists:levels,id',
            'segment' => 'exists:segments,id',
            'courses'    => 'nullable|array',
            'courses.*'  => 'nullable|integer|exists:courses,id',
            'sort_in' => 'in:asc,desc',
            'item_type' => 'string|in:page,media,file',
            'class' => 'nullable|integer|exists:classes,id',
            'lesson' => 'nullable|integer|exists:lessons,id'
        ]);
        if(isset($request->item_id)){
            $check = Material::where('type',$request->item_type)->where('item_id',$request->item_id)->first();
            if(!isset($check))
                return response()->json(['message' => __('messages.error.not_found'), 'body' => null], 400);
        }

        $lessons = $this->chain->getEnrollsByChain($request)->where('user_id',Auth::id());
        $lessons = $lessons->with('SecondaryChain')->get()->pluck('SecondaryChain.*.lesson_id')->collapse();

        if($request->has('lesson')){
            if(!in_array($request->lesson,$lessons->toArray()))
                return response()->json(['message' => __('messages.error.no_active_for_lesson'), 'body' => []], 400);

            $lessons = [$request->lesson];
        }

        $page = Paginate::GetPage($request);
        $paginate = Paginate::GetPaginate($request);

        $materials_query =  Material::select('id', 'item_id', 'name' , 'publish_date' , 'course_id' , 'lesson_id' ,'type' , 'link' , 'visible' )
                            ->orderBy('created_at','desc');


        $material = $materials_query->with(['course.attachment'])->whereIn('lesson_id',$lessons);
        if($request->user()->can('site/course/student')){
            $material
            ->where('visible',1)
            ->where('publish_date' ,'<=', Carbon::now())
            ->where(function($query) {                //Where accessible
                $query->whereHasMorph(
                    'item',
                    [
                        'Modules\Page\Entities\page',
                        'Modules\UploadFiles\Entities\media',
                        'Modules\UploadFiles\Entities\file',
                    ],
                    function($query){
                        $query->doesntHave('courseItem')
                        ->orWhereHas('courseItem.courseItemUsers', function (Builder $query){
                            $query->where('user_id', Auth::id());
                        });
                    }
                );
            });
        }


        if($request->has('item_type'))
            $material->where('type',$request->item_type);

        if($count == 'count'){
             //copy this counts to count it before filteration
            $query=clone $materials_query;
            $all=$query->select(DB::raw
                            (  "COUNT(case `type` when 'file' then 1 else null end) as file ,
                                COUNT(case `type` when 'media' then 1 else null end) as media ,
                                COUNT(case `type` when 'page' then 1 else null end) as page"
                            ))->first()->only(['file','media','page']);
            $cc['all']=$all['file']+$all['media']+$all['page'];

            $counts = $materials_query->select(DB::raw
                (  "COUNT(case `type` when 'file' then 1 else null end) as file ,
                    COUNT(case `type` when 'media' then 1 else null end) as media ,
                    COUNT(case `type` when 'page' then 1 else null end) as page"
                ))->first()->only(['file','media','page']);
            $counts['all']=$cc['all'];

            return response()->json(['message' => __('messages.materials.count'), 'body' => $counts], 200);
        }
        $result['last_page'] = Paginate::allPages($material->count(),$paginate);
        $result['total']= $material->count();

        $AllMat=$material->groupBy('item_id', 'type')->skip(($page)*$paginate)->take($paginate)
                    ->with('item.lessons')->get();
        $result['data'] =  $AllMat;
        $result['current_page']= $page + 1;
        $result['per_page']= count($result['data']);

        return response()->json(['message' => __('messages.materials.list'), 'body' =>$result], 200);
    }

    public function getMaterialAssignedUsers(Request $request){

        $request->validate([
            'id' => 'required|exists:materials,id',
        ]);

        $material = Material::find($request->id);

        $items=Material::where('item_id',$material->item_id)->where('type',$material->type)->
            with(['lesson', 'item.courseItem.courseItemUsers']);
        foreach($items as $item)
            $result['material_classes'][]= $item->lesson->shared_classes->pluck('id')->first();

        $result['restricted'] = $material->restricted;
        if(isset($material['item']->courseItem)){

            $courseItemUsers = $material['item']->courseItem->courseItemUsers;
            foreach($courseItemUsers as $user)
                $result['assigned_users'][] = $user->user_id;
        }

        return response()->json($result, 200);
    }

    public function editMaterialAssignedUsers(Request $request){
        $request->validate([
            'id' => 'required|exists:materials,id',
            'users_ids' => 'array',
            'users_ids.*' => 'exists:users,id'
        ]);

        $material = Material::find($request->id);
        if(isset($request->users_ids))
            Material::where('id',$request->id)->update(['restricted',1]);
        CoursesHelper::updateCourseItem($material->item_id, $material->type, $request->users_ids);
        return response()->json(['message' => 'Updated successfully'], 200);
    }
}

