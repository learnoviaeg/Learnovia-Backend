<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\userAnnouncement;
use App\Announcement;
use Auth;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use App\Repositories\ChainRepositoryInterface;

class AnnouncementsController extends Controller
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
        $this->middleware('auth');
        $this->middleware(['permission:announcements/get'],   ['only' => ['index']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, $created = null)
    {

        $request->validate([
            'search' => 'nullable',
            'paginate' => 'integer'
        ]);

        $paginate = 12;
        if($request->has('paginate')){
            $paginate = $request->paginate;
        }

        if($created == 'created'){

            $announcements = Announcement::where('created_by',Auth::id())->orderBy('publish_date','desc');

            if(isset($request->search))
                $announcements->where('title', 'LIKE' , "%$request->search%");

            return response()->json(['message' => 'Announcements created by user.', 'body' => $announcements->get()->paginate($paginate)], 200);
        }

        $announcements =  userAnnouncement::with('announcements')
                                            ->where('user_id', Auth::id())
                                            ->get()
                                            ->pluck('announcements')
                                            ->sortByDesc('publish_date')
                                            ->unique()->values();

        if($request->user()->can('site/show-all-courses')){ //admin

            $announcements = Announcement::orderBy('publish_date','desc')->get();
        }

        if($request->has('search') && isset($request->search)){

            $announcements = collect($announcements)->filter(function ($item) use ($request) {
                return str_contains($item->title, $request->search);
            });
        }

        return response()->json(['message' => 'Announcements assigned to user.', 'body' => $announcements->values()->paginate($paginate)], 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        //check if user must filter with the whole chain
        $chain_filter = 0;
        if($request->user()->can('announcements/filter-chain')){
            $chain_filter = 1;
        }

        //Validtaion
        $request->validate([
            'title' => 'required',
            'description' => 'required',
            'attached_file' => 'nullable|file|mimetypes:mp3,application/pdf,
                                application/vnd.openxmlformats-officedocument.wordprocessingml.document,
                                application/msword,
                                application/vnd.ms-excel,
                                application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,
                                application/vnd.ms-powerpoint,
                                application/vnd.openxmlformats-officedocument.presentationml.presentation,
                                application/zip,application/x-rar,text/plain,video/mp4,audio/ogg,audio/mpeg,video/mpeg,
                                video/ogg,jpg,image/jpeg,image/png',
            'start_date' => 'before:due_date',
            'due_date' => 'after:' . Carbon::now(),
            'publish_date' => 'nullable|after:' . Carbon::now(),
            'role' => 'exists:roles,id',
            'year' => ['exists:academic_years,id',Rule::requiredIf($chain_filter === 1)],
            'type' => ['exists:academic_types,id',Rule::requiredIf($chain_filter === 1)],
            'level' => ['exists:levels,id',Rule::requiredIf($chain_filter === 1)],
            'class' => ['exists:classes,id',Rule::requiredIf($chain_filter === 1)],
            'segment' => ['exists:segments,id',Rule::requiredIf($chain_filter === 1)],
            'courses'    => 'array',
            'courses.*' => ['exists:courses,id',Rule::requiredIf($chain_filter === 1)]
        ]);

        $publish_date = Carbon::now()->format('Y-m-d H:i:s');
        if($request->has('publish_date')){
            $publish_date = $request->publish_date;
        }

        $file = null;
        if($request->has('attached_file')){
            $file = attachment::upload_attachment($request->attached_file, 'Announcement');
        }

        //get users that should receive the announcement
        $enrolls = $this->chain->getCourseSegmentByChain($request)->where('user_id','!=' ,Auth::id());

        if($request->has('role')){
            $enrolls->where('role_id',$request->role);
        }

        $users = $enrolls->with('user')->get()->pluck('user')->unique()->filter()->values()->pluck('id');

        //create announcement
        $announcement = Announcement::create([
            'title' => $request->title,
            'description' => $request->description,
            'attached_file' => isset($file) ? $file->id : null,
            'class_id' => isset($request->class) ? $request->class : null,
            'course_id' => $request->has('courses') && count($request->courses) > 0 ? $request->courses[0] : null,
            'level_id' => isset($request->level) ? $request->level : null,
            'year_id' => isset($request->year) ? $request->year : null,
            'type_id' => isset($request->type) ? $request->type : null,
            'segment_id' => isset($request->segment) ? $request->segment : null,
            'publish_date' => $publish_date,
            'created_by' => Auth::id(),
            'start_date' => isset($request->start_date) ? $request->start_date : null,
            'due_date' => isset($request->due_date) ? $request->due_date : null,
        ]);

        //add announcement id to users object
        $users->map(function ($user) use ($announcement) {
            userAnnouncement::create([
                'announcement_id' => $announcement->id,
                'user_id' => $user
            ]);
        });

        //notification object
        $notify_request = ([
            'id' => $announcement->id,
            'type' => 'announcement',
            'publish_date' => $publish_date,
            'title' => $request->title,
            'description' => $request->description,
            'attached_file' => $file,
            'start_date' => $announcement->start_date,
            'due_date' => $announcement->due_date,
            'message' => $request->title.' announcement is added'
        ]);


        return $users;


    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $announcement = Announcement::where('id',$id)->with('attachment')->first();

        if(isset($announcement))
            return response()->json(['message' => 'announcement objet', 'body' => $announcement], 200);

        return response()->json(['message' => 'Announcement not fount!', 'body' => [] ], 400);
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
