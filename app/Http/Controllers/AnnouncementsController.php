<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\userAnnouncement;
use App\Announcement;
use Auth;

class AnnouncementsController extends Controller
{
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
