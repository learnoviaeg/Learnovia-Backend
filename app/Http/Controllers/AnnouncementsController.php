<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\userAnnouncement;
use App\AnnouncementsChain;
use App\Announcement;
use App\attachment;
use Auth;
use App\user;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use App\Repositories\ChainRepositoryInterface;
use Illuminate\Support\Facades\Input;

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
        $this->middleware(['permission:announcements/get'],   ['only' => ['index','show']]);
        $this->middleware(['permission:announcements/update'],   ['only' => ['update']]);
        $this->middleware(['permission:announcements/delete'],   ['only' => ['destroy']]);
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

        $roles = Auth::user()->roles->pluck('name');
        if(in_array("Parent" , $roles->toArray())){
            if(Auth::user()->currentChild != null)
            {
                $currentChild =User::find(Auth::user()->currentChild->child_id);
                Auth::setUser($currentChild);
        }
        }
        $paginate = 12;
        if($request->has('paginate')){
            $paginate = $request->paginate;
        }

        if($created == 'created'){

            $announcements = Announcement::where('created_by',Auth::id())->orderBy('publish_date','desc');

            if(isset($request->search))
                $announcements->where('title', 'LIKE' , "%$request->search%");

            return response()->json(['message' => __('messages.announcement.created_list'), 'body' => $announcements->get()->paginate($paginate)], 200);
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

        if($request->filled('search')){

            $announcements = collect($announcements)->filter(function ($item) use ($request) {
                return str_contains(strtolower($item->title), strtolower($request->search));
            });
        }

        return response()->json(['message' => __('messages.announcement.list'), 'body' => $announcements->filter()->values()->paginate($paginate)], 200);
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

        //Validtaion //note:please dont take spaces between attached_file types as validation will not work
        $request->validate([
            'title' => 'required',
            'description' => 'required', 
            'attached_file' => 'nullable|file|mimetypes:application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/msword,application/pdf,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation,application/zip,application/x-rar,text/plain,video/mp4,audio/ogg,audio/mpeg,video/mpeg,video/ogg,jpg,image/jpeg,image/png,mp3',
            'start_date' => 'before:due_date',
            'due_date' => 'after:' . Carbon::now(),
            'publish_date' => 'nullable|date',
            'chains' => 'required|array',
            'chains.*.roles' => 'array',
            'chains.*.roles.*' => 'exists:roles,id',
            'chains.*.year' => 'required|exists:academic_years,id',
            'chains.*.type' => ['exists:academic_types,id',Rule::requiredIf($chain_filter === 1)],
            'chains.*.level' => ['exists:levels,id',Rule::requiredIf($chain_filter === 1)],
            'chains.*.class' => ['exists:classes,id',Rule::requiredIf($chain_filter === 1)],
            'chains.*.segment' => ['exists:segments,id',Rule::requiredIf($chain_filter === 1)],
            'chains.*.course' => ['exists:courses,id',Rule::requiredIf($chain_filter === 1)]
        ]);

        $publish_date = Carbon::now()->format('Y-m-d H:i:s');
        if($request->has('publish_date') && $request->publish_date >= Carbon::now()){
            $publish_date = $request->publish_date;
        }

        $file = null;
        if($request->has('attached_file')){
            $file = attachment::upload_attachment($request->attached_file, 'Announcement');
        }

        //create announcement
        $announcement = Announcement::create([
            'title' => $request->title,
            'description' => $request->description,
            'attached_file' => isset($file) ? $file->id : null,
            'publish_date' => $publish_date,
            'created_by' => Auth::id(),
            'start_date' => isset($request->start_date) ? $request->start_date : null,
            'due_date' => isset($request->due_date) ? $request->due_date : null,
        ]);


        $users = collect();
        foreach($request->chains as $chain){

            //chain object
            $chain_request = new Request ([
                'year' => $chain['year'],
                'type' => isset($chain['type']) ? $chain['type'] : null,
                'level' => isset($chain['level']) ? $chain['level'] : null,
                'class' => isset($chain['class']) ? $chain['class'] : null,
                'segment' => isset($chain['segment']) ? $chain['segment'] : null,
                'courses' => isset($chain['course']) ? [$chain['course']] : null,
            ]);

            //get users that should receive the announcement
            $enrolls = $this->chain->getCourseSegmentByChain($chain_request)->where('user_id','!=' ,Auth::id());

            if(isset($chain['roles']) && count($chain['roles']) > 0){
                $enrolls->whereIn('role_id',$chain['roles']);
            }

            $users->push($enrolls->with('user')->get()->pluck('user')->unique()->filter()->values()->pluck('id'));

            $announcement_chain = AnnouncementsChain::create([
                'announcement_id' => $announcement->id,
                'year' => $chain['year'],
                'type'=> isset($chain['type']) ? $chain['type'] : null,
                'level' => isset($chain['level']) ? $chain['level'] : null,
                'class' => isset($chain['class']) ? $chain['class'] : null,
                'segment' => isset($chain['segment']) ? $chain['segment'] : null,
                'course' => isset($chain['course']) ? $chain['course'] : null,
            ]);
        }

        //filter users
        $users = $users->collapse()->unique()->values();

        //check if there's a students to send for them or skip that part
        if(count($users) > 0){

            //add user announcements
            $users->map(function ($user) use ($announcement) {
                userAnnouncement::create([
                    'announcement_id' => $announcement->id,
                    'user_id' => $user
                ]);
            });

            //notification object
            $notify_request = new Request ([
                'id' => $announcement->id,
                'type' => 'announcement',
                'publish_date' => $publish_date,
                'title' => $request->title,
                'description' => $request->description,
                'attached_file' => $file,
                'start_date' => $announcement->start_date,
                'due_date' => $announcement->due_date,
                'message' => $request->title.' announcement is added',
                'from' => $announcement->created_by,
                'users' => $users->toArray()
            ]);

            // use notify store function to notify users with the announcement
            $notify = (new NotificationsController)->store($notify_request);
        }

        return response()->json(['message' => __('messages.announcement.add'), 'body' => $announcement], 200);
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
            return response()->json(['message' => __('messages.announcement.object'), 'body' => $announcement], 200);

        return response()->json(['message' => __('messages.error.not_found'), 'body' => [] ], 400);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:announcements,id',
            'attached_file' => 'nullable|file|mimetypes:application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/msword,application/pdf,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation,application/zip,application/x-rar,text/plain,video/mp4,audio/ogg,audio/mpeg,video/mpeg,video/ogg,jpg,image/jpeg,image/png,mp3',
            'start_date' => 'before:due_date',
            'due_date' => 'after:' . Carbon::now(),
        ]);

        $announcement = Announcement::where('id',$request->id)->with('attachment')->first();

        if($request->filled('title'))
            $announcement->title = $request->title;

        if($request->filled('description'))
            $announcement->description = $request->description;

        if($request->filled('start_date'))
            $announcement->start_date = $request->start_date;

        if($request->filled('due_date'))
            $announcement->due_date = $request->due_date;

        $file = $announcement->attachment;
        if(Input::hasFile('attached_file')){
            $file = attachment::upload_attachment($request->attached_file, 'Announcement');
            $announcement->attached_file = $file->id;
        }

        $announcement->save();

        //check if announcement has already been sent to send the update
        if($announcement->publish_date < Carbon::now()){

            $users = userAnnouncement::where('announcement_id', $announcement->id)->pluck('user_id')->unique('user_id');

            //check if there's a students to send for them or skip that part
            if(count($users) > 0){

                //notification object
                $notify_request = new Request ([
                    'id' => $announcement->id,
                    'type' => 'announcement',
                    'publish_date' => Carbon::now()->format('Y-m-d H:i:s'),
                    'title' => $announcement->title,
                    'description' => $announcement->description,
                    'attached_file' => $file,
                    'start_date' => $announcement->start_date,
                    'due_date' => $announcement->due_date,
                    'message' => $announcement->title.' announcement is updated',
                    'from' => $announcement->created_by,
                    'users' => $users->toArray()
                ]);

                // use notify store function to notify users with the announcement
                $notify = (new NotificationsController)->store($notify_request);
            }
        }

        return response()->json(['message' => __('messages.announcement.update'), 'body' => $announcement], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $announcement = Announcement::where('id',$id)->with('attachment')->first();

        if(!isset($announcement))
            return response()->json(['message' => __('messages.error.not_found'), 'body' => [] ], 400);

        $announcement->delete();

        return response()->json(['message' => __('messages.announcement.delete'), 'body' => $announcement], 200);
    }
}
