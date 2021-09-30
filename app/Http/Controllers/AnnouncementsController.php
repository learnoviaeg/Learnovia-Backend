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
            'attached_file' => 'nullable|file|mimetypes:application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/msword,application/pdf,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation,application/zip,application/x-rar,text/plain,video/mp4,audio/ogg,audio/mpeg,video/mpeg,video/ogg,jpg,image/jpeg,image/png,mp3,audio/aac,application/x-abiword,application/x-freearc,video/x-msvideo,application/vnd.amazon.ebook,application/octet-stream,image/bmp,application/x-bzip,application/x-bzip2,application/x-cdf,application/x-csh,text/csv,application/vnd.ms-fontobject,application/gzip,image/gif,image/vnd.microsoft.icon,application/json,application/ld+json,audio/midi,application/vnd.apple.installer+xml,application/vnd.oasis.opendocument.presentation,application/vnd.oasis.opendocument.spreadsheet,application/vnd.oasis.opendocument.text,application/ogg,audio/opus,font/otf,	application/vnd.ms-powerpoint,application/vnd.rar,application/rtf,application/x-sh,image/svg+xml,application/x-shockwave-flash,application/x-tar,image/tiff,video/mp2t,font/ttf,application/vnd.visio,audio/wav,audio/webm,video/webm,image/webp,font/woff,application/xml,text/xml,application/atom+xml,application/vnd.mozilla.xul+xml,video/3gpp,audio/3gpp,video/3gpp2,audio/3gpp2,application/x-7z-compressed',
            'start_date' => 'before:due_date',
            'due_date' => 'after:' . Carbon::now(),
            'publish_date' => 'nullable|date',
            'topic' => 'nullable | exists:topics,id',
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

        if($request->has('start_date') && $request->has('publish_date')){
            $request->validate([
                'publish_date' => 'before_or_equal:start_date',
            ]);
        }

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
            'topic' => $request->topic,
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
            $enrolls = $this->chain->getEnrollsByChain($chain_request);
            $query=clone $enrolls;
            
            $enrolls->where('user_id','!=' ,Auth::id());

            if(isset($chain['roles']) && count($chain['roles']) > 0){
                $enrolls->whereIn('role_id',$chain['roles']);
            }

            if(!isset($chain['roles'])){
                $enrolls->where('role_id','!=', 1 );
            }

            // to get users that on my chain
            $query_course=$query->where('user_id',Auth::id())->pluck('course');
            if(isset($query_class))
                $enrolls->whereIn('course',$query_course);

            $users->push($enrolls->whereHas('user')->select('user_id')->distinct()->pluck('user_id'));

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
                'from' => $announcement->created_by['id'],
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
    public function update(Request $request , Announcement $announcement)
    {
        $request->validate([
            'id' => 'required|integer|exists:announcements,id',
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
            $request->validate([
            'attached_file' => 'nullable|file|mimetypes:application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/msword,application/pdf,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation,application/zip,application/x-rar,text/plain,video/mp4,audio/ogg,audio/mpeg,video/mpeg,video/ogg,jpg,image/jpeg,image/png,mp3,audio/aac,application/x-abiword,application/x-freearc,video/x-msvideo,application/vnd.amazon.ebook,application/octet-stream,image/bmp,application/x-bzip,application/x-bzip2,application/x-cdf,application/x-csh,text/csv,application/vnd.ms-fontobject,application/gzip,image/gif,image/vnd.microsoft.icon,application/json,application/ld+json,audio/midi,application/vnd.apple.installer+xml,application/vnd.oasis.opendocument.presentation,application/vnd.oasis.opendocument.spreadsheet,application/vnd.oasis.opendocument.text,application/ogg,audio/opus,font/otf,	application/vnd.ms-powerpoint,application/vnd.rar,application/rtf,application/x-sh,image/svg+xml,application/x-shockwave-flash,application/x-tar,image/tiff,video/mp2t,font/ttf,application/vnd.visio,audio/wav,audio/webm,video/webm,image/webp,font/woff,application/xml,text/xml,application/atom+xml,application/vnd.mozilla.xul+xml,video/3gpp,audio/3gpp,video/3gpp2,audio/3gpp2,application/x-7z-compressed',
            ]);
            $file = attachment::upload_attachment($request->attached_file, 'Announcement');
            $announcement->attached_file = $file->id;
        }
        if($request->attached_file == 'No_file')
            $announcement->attached_file = null;

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
                    'from' => $announcement->created_by['id'],
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
