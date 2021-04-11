<?php

namespace App\Http\Controllers;

use App\attachment;
use App\Enroll;
use App\Event;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EventController extends Controller
{

    public function create(Request $request)
    {
        //Validtaion
        $request->validate([
            'name' => 'required|string',
            'description' => 'required|string',
            'attached_file' => 'nullable|file|mimes:pdf,docx,doc,xls,xlsx,ppt,pptx,zip,rar,txt,
                                            jpeg,jpg,png,gif,mp4,avi,flv,wav,mpga,ogg,ogv,oga',
            'from' => 'required|date',
            'to' => 'date',
            'cover' => 'nullable|file|mimes:jpeg,jpg,png,gif,mp4,avi,flv,wav,mpga,ogg,ogv,oga',
            'users' => 'array',
            'users.*' => 'nullable|exists:users,id',
            'classes' => 'array',
            'classes.*' => 'nullable|exists:users,class_id',
            'levels' => 'array',
            'levels.*' => 'nullable|exists:users,level'
        ]);
        $user_ids = collect();
            $flag = false;
            $file_id = null;
            $cover_id = null;
            $id_number = null;
            if(Auth::user()->can('event/add-bulk')){
            if ($request->filled('classes')) {
                $user_ids->push(User::whereIn('class_id', $request->classes)->pluck('id'));
                $flag = true;
            }
            if ($request->filled('levels')) {
                $user_ids->push(User::whereIn('level', $request->levels)->pluck('id'));
                $flag = true;
            }
            if ($request->filled('users')) {
                $user_ids->push(User::whereIn('id', $request->users)->pluck('id'));
            }

            if ($flag) {
                $id_number = Event::max('id') + 1;
            }
            $users_ids = [];
            switch (count($user_ids)) {
                case 1 :
                    $users_ids = $user_ids[0]->toArray();
                    break;
                case 2 :
                    $users_ids = array_values(array_unique(array_merge($user_ids[0]->toArray(), $user_ids[1]->toArray())));
                    break;
                case 3 :
                    $users_ids = array_values(array_unique(array_merge($user_ids[0]->toArray(), $user_ids[1]->toArray(), $user_ids[2]->toArray())));
                    break;
                default:
                    $users_ids = [];
            }
    }else{
        $users_ids = [];
        array_push($users_ids,Auth::id());
    }
    
        if (isset($request->attached_file)) {
            $fileName = attachment::upload_attachment($request->attached_file, 'Event');
            $file_id = $fileName->id;
        }
        if (isset($request->cover)) {
            $cover_name = attachment::upload_attachment($request->cover, 'Event');
            $cover_id = $cover_name->id;
        }
        $events = [];
        foreach ($users_ids as $user_id) {
            $events [] = Event::create([
                'name' => $request->name,
                'description' => $request->description,
                'attached_file' => $file_id,
                'from' => $request->from,
                'to' => isset($request->to) ? $request->to : null,
                'cover' => $cover_id,
                'id_number' => $id_number,
                'user_id' => $user_id,
            ]);
        }
        if (count($users_ids) == 0) {
            return HelperController::api_response_format(201, $events, ' there is no users ');
        }
        return HelperController::api_response_format(201, $events, 'Added Successfully');
    }

    public function delete(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'users' => 'array',
            'users.*' => 'nullable|exists:users,id',
            'classes' => 'array',
            'classes.*' => 'nullable|exists:users,class_id',
            'levels' => 'array',
            'levels.*' => 'nullable|exists:users,level'
        ]);

        $user_ids = collect();
        if ($request->filled('classes')) {
            $user_ids->push(User::whereIn('class_id', $request->classes)->pluck('id'));
        }
        if ($request->filled('levels')) {
            $user_ids->push(User::whereIn('level', $request->levels)->pluck('id'));
        }
        if ($request->filled('users')) {
            $user_ids->push(User::whereIn('id', $request->users)->pluck('id'));
        }

        $users_ids = [];
        switch (count($user_ids)) {
            case 1 :
                $users_ids = $user_ids[0]->toArray();
                break;
            case 2 :
                $users_ids = array_values(array_unique(array_merge($user_ids[0]->toArray(), $user_ids[1]->toArray())));
                break;
            case 3 :
                $users_ids = array_values(array_unique(array_merge($user_ids[0]->toArray(), $user_ids[1]->toArray(), $user_ids[2]->toArray())));
                break;
            default:
                $users_ids = [];
        }
        $events = Event::where('name', $request->name);
        if (count($users_ids) > 0) {
            $events->whereIn('user_id', $users_ids);
        }
        $events->delete();
        return HelperController::api_response_format(201,null, 'deleted Successfully');
    }

    public function update(Request $request)
    {
        $request->validate([
            'name' => 'string',
            'old_name' => 'required|string|exists:events,name',
            'description' => 'string',
            'attached_file' => 'nullable|file|mimes:pdf,docx,doc,xls,xlsx,ppt,pptx,zip,rar,txt',
            'from' => 'required|date',
            'to' => 'date',
            'cover' => 'nullable|file|mimes:jpeg,jpg,png,gif,mp4,avi,flv,wav,mpga,ogg,ogv,oga',
            'users' => 'array',
            'users.*' => 'nullable|exists:users,id',
            'classes' => 'array',
            'classes.*' => 'nullable|exists:users,class_id',
            'levels' => 'array',
            'levels.*' => 'nullable|exists:users,level'
        ]);
        $user_ids = collect();
        $flag = false;
        $file_id = null;
        $cover_id = null;
        $id_number = null;
        if ($request->filled('classes')) {
            $user_ids->push(User::whereIn('class_id', $request->classes)->pluck('id'));
            $flag = true;
        }
        if ($request->filled('levels')) {
            $user_ids->push(User::whereIn('level', $request->levels)->pluck('id'));
            $flag = true;
        }
        if ($request->filled('users')) {
            $user_ids->push(User::whereIn('id', $request->users)->pluck('id'));
        }

        if ($flag) {
            $id_number = Event::max('id') + 1;
        }
        $users_ids = [];
        switch (count($user_ids)) {
            case 1 :
                $users_ids = $user_ids[0]->toArray();
                break;
            case 2 :
                $users_ids = array_values(array_unique(array_merge($user_ids[0]->toArray(), $user_ids[1]->toArray())));
                break;
            case 3 :
                $users_ids = array_values(array_unique(array_merge($user_ids[0]->toArray(), $user_ids[1]->toArray(), $user_ids[2]->toArray())));
                break;
            default:
                $users_ids = [];
        }
        if (isset($request->attached_file)) {
            $fileName = attachment::upload_attachment($request->attached_file, 'Event');
            $file_id = $fileName->id;
        }
        if (isset($request->cover)) {
            $cover_name = attachment::upload_attachment($request->cover, 'Event');
            $cover_id = $cover_name->id;
        }

        $events = Event::where('name', $request->old_name);
        if(count($user_ids)>0)
            $events->whereIn('user_id', $users_ids);
        $events->update([
                'name' =>isset( $request->name)? $request->name :$request->old_name ,
                'description' => $request->description,
                'attached_file' => $file_id,
                'from' => $request->from,
                'to' => isset($request->to) ? $request->to : null,
                'cover' => $cover_id,
                'id_number' => $id_number,
            ]
        );
        return HelperController::api_response_format(201, null,'updated Successfully');
    }


    public function get_my_events(Request $request){

        $request->validate([
            'from' => 'date|required_with:to',
            'to' => 'nullable|date',
        ]);
        $events = Event::where('user_id',Auth::user()->id);

        if($request->filled('from') && !$request->filled('to'))
            $events->where('from' , $request->from);

        if($request->filled('to'))
            $events->whereBetween('from' , [$request->from , $request->to]);

        return HelperController::api_response_format(201,$events->get(),'there are your events ... ' );
    }

    public function GetAllEvents(Request $request)
    {
        $request->validate([
            'from' => 'nullable|date|required_with:to',
            'to' => 'nullable|date',
            'users' => 'array',
            'users.*' => 'nullable|exists:users,id',
            'classes' => 'array',
            'classes.*' => 'nullable|exists:users,class_id',
            'levels' => 'array',
            'levels.*' => 'nullable|exists:users,level'
        ]);
        $users=collect();
        $events = Event::whereNotNull('id');

        if($request->filled('from') && !$request->filled('to'))
            $events->where('from' , $request->from);

        if($request->filled('to'))
            $events->whereBetween('from' , [$request->from , $request->to]);

        if($request->filled('users'))
            $events->whereIn('user_id' , $request->users);

        if ($request->filled('classes')) {
            $users->push(User::whereIn('class_id', $request->classes)->pluck('id'));
        }

        if ($request->filled('levels')) {
            $users->push(User::whereIn('level', $request->levels)->pluck('id'));
        }
        if(count($users) > 0)
            $events->whereIn('user_id',$users[0]);

        return HelperController::api_response_format(201,$events->get(),'there are all events ... ' );
    }
}
