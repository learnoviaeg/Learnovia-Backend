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
use App\Enroll;
use App\Lesson;

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
    public function index(Request $request)
    {
        $request->validate([
            'year' => 'exists:academic_years,id',
            'type' => 'exists:academic_types,id',
            'level' => 'exists:levels,id',
            'segment' => 'exists:segments,id',
            'courses'    => 'nullable|array',
            'courses.*'  => 'nullable|integer|exists:courses,id',
            'item_type' => 'string|in:page,media,file,quiz,assignment,h5p',
            'class' => 'nullable|integer|exists:classes,id',
            'lesson' => 'nullable|integer|exists:lessons,id',
            'times' => 'integer',
        ]);

        
        $user_course_segments = $this->chain->getCourseSegmentByChain($request);

        if(!$request->user()->can('site/show-all-courses'))//student
            $user_course_segments = $user_course_segments->where('user_id',Auth::id());

        $user_course_segments = $user_course_segments->select('course_segment')->distinct()->with('courseSegment.lessons')->get();

        $lessons = $user_course_segments->pluck('courseSegment.lessons')->collapse()->pluck('id');
      
        if($request->has('lesson')){
            if(!in_array($request->lesson,$lessons->toArray()))
                return response()->json(['message' => __('messages.error.no_active_for_lesson'), 'body' => []], 400);

            $lessons = [$request->lesson];
        }

        $lessons_enrolls = collect();

        $lessons_object = $user_course_segments->pluck('courseSegment.lessons')->collapse();
        if($request->filled('lesson_id'))
            $lessons_object = collect([Lesson::find($request->lesson_id)]);

        $lessons_object->map(function ($lesson) use ($lessons_enrolls) {

            $total = count(Enroll::where('course_segment',$lesson->course_segment_id)->select('user_id')->distinct()->get());
            $lessons_enrolls->push([
                'lesson_id' => $lesson->id,
                'total_enrolls' => $total
            ]);

            return $lessons_enrolls;
        });

        $report = collect();

        if(!$request->filled('item_type') || $request->item_type == 'assignment'){
            //get the assignments and map them
            $assignments = AssignmentLesson::whereIn('lesson_id',$lessons)->with('Assignment')->get();
            $assignments->map(function ($assignment) use ($report,$lessons_enrolls) {

                $total = $lessons_enrolls->where('lesson_id',$assignment->lesson_id);

                $report->push([
                    'item_id' => $assignment->assignment_id,
                    'item_name' => $assignment->Assignment[0]->name,
                    'item_type' => 'assignment',
                    'seen_number' => $assignment->seen_number,
                    'user_seen_number' => $assignment->user_seen_number,
                    'lesson_id' => $assignment->lesson_id,
                    'percentage' => count($total) > 0 ? round(($assignment->user_seen_number/$total[0]['total_enrolls'])*100,2) : 0,
                ]);

                return $report;
            });
        }


        if(!$request->filled('item_type') || $request->item_type == 'quiz'){
            //get the quizzes and map them
            $quizzes = QuizLesson::whereIn('lesson_id',$lessons)->with('quiz')->get();
            $quizzes->map(function ($quiz) use ($report,$lessons_enrolls) {

                $total = $lessons_enrolls->where('lesson_id',$quiz->lesson_id);

                $report->push([
                    'item_id' => $quiz->quiz_id,
                    'item_name' => $quiz->quiz->name,
                    'item_type' => 'quiz',
                    'seen_number' => $quiz->seen_number,
                    'user_seen_number' => $quiz->user_seen_number,
                    'lesson_id' => $quiz->lesson_id,
                    'percentage' => count($total) > 0 ? round(($quiz->user_seen_number/$total[0]['total_enrolls'])*100,2) : 0,
                ]);
                return $report;
            });
        }

        if(!$request->filled('item_type') || $request->item_type == 'h5p'){
            //get the h5p and map them
            $contents = h5pLesson::whereIn('lesson_id',$lessons)->get();
            $contents->map(function ($h5p) use ($report,$lessons_enrolls) {

                $total = $lessons_enrolls->where('lesson_id',$h5p->lesson_id);

                $report->push([
                    'item_id' => $h5p->content_id,
                    'item_name' => response()->json(DB::table('h5p_contents')->whereId($h5p->content_id)->pluck('title')->first())->original,
                    'item_type' => 'h5p',
                    'seen_number' => $h5p->seen_number,
                    'user_seen_number' => $h5p->user_seen_number,
                    'lesson_id' => $h5p->lesson_id,
                    'percentage' => count($total) > 0 ? round(($h5p->user_seen_number/$total[0]['total_enrolls'])*100,2) : 0,
                ]);
                return $report;
            });
        }

        if(!$request->filled('item_type') || in_array($request->item_type,['file','media','page'])){
            //get the materials and map them
            $materials = Material::whereIn('lesson_id',$lessons);

            if($request->filled('item_type'))
                $materials->where('type',$request->item_type);

            $materials = $materials->get();

            $materials->map(function ($material) use ($report,$lessons_enrolls) {

                $total = $lessons_enrolls->where('lesson_id',$material->lesson_id);

                $report->push([
                    'item_id' => $material->item_id,
                    'item_name' => $material->name,
                    'item_type' => $material->type,
                    'seen_number' => $material->seen_number,
                    'user_seen_number' => $material->user_seen_number,
                    'lesson_id' => $material->lesson_id,
                    'percentage' => count($total) > 0 ? round(($material->user_seen_number/$total[0]['total_enrolls'])*100,2) : 0,
                ]);
                return $report;
            });
        }

        if($request->filled('times'))
            $report = $report->where('seen_number',$request->times)->values();

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
