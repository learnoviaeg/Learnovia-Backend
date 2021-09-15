<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Material;
use App\h5pLesson;
use Modules\Assigments\Entities\AssignmentLesson;
use Modules\QuestionBank\Entities\QuizLesson;
use App\Repositories\ChainRepositoryInterface;
use DB;
use App\Paginate;
use Auth;
use App\Enroll;
use App\Course;
use App\Lesson;
use App\UserSeen;
use App\CourseSegment;
use App\SecondaryChain;

class SeenReportController extends Controller
{

    protected $chain;

    /**
     * ChainController constructor.
     *
     * @param ChainRepositoryInterface $post
     */
    public function __construct(ChainRepositoryInterface $chain)
    {
        $this->chain = $chain;
        $this->middleware(['permission:reports/overall_seen_report'],   ['only' => ['index']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request,$option = null)
    {
        $request->validate([
            'year' => 'exists:academic_years,id',
            'type' => 'exists:academic_types,id',
            'level' => 'exists:levels,id',
            'segment' => 'exists:segments,id',
            'courses'    => 'nullable|array',
            'role'    => 'array|exists:roles,id',
            'courses.*'  => 'nullable|integer|exists:courses,id',
            'item_type' => 'array',
            'item_type.*' => 'string|in:page,media,file,quiz,assignment,h5p',
            'class' => 'nullable|integer|exists:classes,id',
            'lesson' => 'nullable|integer|exists:lessons,id',
            'times' => 'integer',
            'from' => 'date|required_with:to',
            'to' => 'date|required_with:from',
            'search' => 'string',
        ]);

        
        $enrollss = $this->chain->getEnrollsByChain($request)->where('user_id',Auth::id());
      
        if($request->filled('role'))
            $enrollss->whereIn('role_id',$request->role);

        // if(!$request->user()->can('site/show-all-courses'))//student
            // $user_course_segments = $user_course_segments->where('user_id',Auth::id());

        // $user_course_segments = $user_course_segments->select('course_segment')->distinct()->with('courseSegment.lessons')->get();

        $lessons = SecondaryChain::whereIn('enroll_id', $enrollss->get()->pluck('id'))->pluck('lesson_id');
       
        if($request->has('lesson')){
            if(!in_array($request->lesson,$lessons->toArray()))
                return response()->json(['message' => __('messages.error.no_active_for_lesson'), 'body' => []], 400);

            $lessons = [$request->lesson];
        }

        //getting total number of enrolled users for each lesson
        $lessons_enrolls = collect();

        $lessons_object = Lesson::whereIn('id',$lessons)->get();
        if($request->filled('lesson_id'))
            $lessons_object = collect([Lesson::find($request->lesson_id)]);

        $lessons_object->map(function ($lesson) use ($lessons_enrolls) {
            
            $total = SecondaryChain::where('lesson_id', $lesson->id)->where('role_id',3)->count();
            // $total = count(Enroll::where('course_segment',$lesson->course_segment_id)->where('role_id',3)->select('user_id')->distinct()->get());

            $lessons_enrolls->push([
                'lesson_id' => $lesson->id,
                'total_enrolls' => $total,
            ]);

            return $lessons_enrolls;
        });

        //geting the items that has been viwed during this period
        $items_only = [];
        if($request->filled('from') && $request->filled('to')){
            $items_only = UserSeen::whereDate('updated_at', '>=',$request->from)->whereDate('updated_at', '<=', $request->to)->whereIn('lesson_id',$lessons)->get();
        }

        $report = collect();

        if(!$request->filled('item_type') || in_array('assignment',$request->item_type)){
            //get the assignments and map them
            $assignments = AssignmentLesson::whereIn('lesson_id',$lessons)->with('Assignment');

            if($request->filled('from') && $request->filled('to'))
                $assignments->whereIn('assignment_id',$items_only->where('type','assignment')->pluck('item_id'));
            
            $assignments = $assignments->get();

            $assignments->map(function ($assignment) use ($report,$lessons_enrolls) {

                $total = collect($lessons_enrolls->where('lesson_id',$assignment->lesson_id))->collapse();
                $lesson = Lesson::whereId($assignment->lesson_id)->first();
                $report->push([
                    'item_id' => $assignment->assignment_id,
                    'item_name' => $assignment->Assignment[0]->name,
                    'item_type' => 'assignment',
                    'seen_number' => $assignment->seen_number,
                    'user_seen_number' => $assignment->user_seen_number,
                    'lesson_id' => $assignment->lesson_id,
                    'percentage' => isset($total) && $assignment->user_seen_number != 0  ? round(($assignment->user_seen_number/$total['total_enrolls'])*100,2) : 0,
                    'course' => Course::find($lesson->course_id),
                    'class' => $lesson->shared_classes
                ]);

                return $report;
            });
        }


        if(!$request->filled('item_type') || in_array('quiz',$request->item_type)){
            //get the quizzes and map them
            $quizzes = QuizLesson::whereIn('lesson_id',$lessons)->with('quiz');
            
            if($request->filled('from') && $request->filled('to'))
                $quizzes->whereIn('quiz_id',$items_only->where('type','quiz')->pluck('item_id'));
            
            $quizzes = $quizzes->get();

            $quizzes->map(function ($quiz) use ($report,$lessons_enrolls) {

                $total = collect($lessons_enrolls->where('lesson_id',$quiz->lesson_id))->collapse();
                $lesson = Lesson::whereId($quiz->lesson_id)->first();
                $report->push([
                    'item_id' => $quiz->quiz_id,
                    'item_name' => $quiz->quiz->name,
                    'item_type' => 'quiz',
                    'seen_number' => $quiz->seen_number,
                    'user_seen_number' => $quiz->user_seen_number,
                    'lesson_id' => $quiz->lesson_id,
                    'percentage' => isset($total) && $quiz->user_seen_number != 0 ? round(($quiz->user_seen_number/$total['total_enrolls'])*100,2) : 0,
                    'course' => Course::find($lesson->course_id),
                    'class' => $lesson->shared_classes
                ]);
                return $report;
            });
        }

        if(!$request->filled('item_type') || in_array('h5p',$request->item_type)){
            //get the h5p and map them
            $contents = h5pLesson::whereIn('lesson_id',$lessons);
            
            if($request->filled('from') && $request->filled('to'))
                $contents->whereIn('content_id',$items_only->where('type','h5p')->pluck('item_id'));

            $contents = $contents->get();

            $contents->map(function ($h5p) use ($report,$lessons_enrolls) {

                $total = collect($lessons_enrolls->where('lesson_id',$h5p->lesson_id))->collapse();
                $lesson = Lesson::whereId($h5p->lesson_id)->first();
                $report->push([
                    'item_id' => $h5p->content_id,
                    'item_name' => response()->json(DB::table('h5p_contents')->whereId($h5p->content_id)->pluck('title')->first())->original,
                    'item_type' => 'h5p',
                    'seen_number' => $h5p->seen_number,
                    'user_seen_number' => $h5p->user_seen_number,
                    'lesson_id' => $h5p->lesson_id,
                    'percentage' => isset($total) && $h5p->user_seen_number != 0 ? round(($h5p->user_seen_number/$total['total_enrolls'])*100,2) : 0,
                    'course' => Course::find($lesson->course_id),
                    'class' => $lesson->shared_classes
                ]);
                return $report;
            });
        }

        if(!$request->filled('item_type') || in_array('file',$request->item_type) || in_array('media',$request->item_type) || in_array('page',$request->item_type)){
            //get the materials and map them
            $materials = Material::whereIn('lesson_id',$lessons);

            if($request->filled('item_type'))
                $materials->whereIn('type',$request->item_type);

            if($request->filled('from') && $request->filled('to'))
                $materials->whereIn('item_id',$items_only->whereIn('type',['file','media','page'])->pluck('item_id'));

            $materials = $materials->get();

            $materials->map(function ($material) use ($report,$lessons_enrolls) {

                $total = collect($lessons_enrolls->where('lesson_id',$material->lesson_id))->collapse();
                $lesson = Lesson::whereId($material->lesson_id)->first();
                $report->push([
                    'item_id' => $material->item_id,
                    'item_name' => $material->name,
                    'item_type' => $material->type,
                    'seen_number' => $material->seen_number,
                    'user_seen_number' => $material->user_seen_number,
                    'lesson_id' => $material->lesson_id,
                    'percentage' => isset($total) && $material->user_seen_number != 0 ? round(($material->user_seen_number/$total['total_enrolls'])*100,2) : 0,
                    'course' => Course::find($lesson->course_id),
                    'class' => $lesson->shared_classes
                ]);
                return $report;
            });
        }

        if($request->filled('times'))
            $report = $report->where('seen_number',$request->times)->values();

        if($request->filled('search')){

            $report = collect($report)->filter(function ($item) use ($request) {
                if(str_contains(strtolower($item['item_name']), strtolower($request->search))) 
                    return $item; 
            });
        }
        
        if($option == 'chart'){
            
            $total = count($report);
            $sum_percentage = array_sum($report->pluck('percentage')->toArray());
            $final_percentage = round($sum_percentage/$total,1);

            return response()->json(['message' => 'Total Percentage', 'body' => $final_percentage], 200);
        }

        return response()->json(['message' => 'Overall seen report', 'body' => $report->paginate(Paginate::GetPaginate($request))], 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
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
}