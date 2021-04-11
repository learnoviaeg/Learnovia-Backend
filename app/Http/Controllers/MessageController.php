<?php

namespace App\Http\Controllers;

use App\User;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;
use Validator;
use App\Http\Resources\Messageresource;
use App\Http\Resources\MessageFromToResource;
use App\Message_Role;
use Illuminate\Support\Facades\Auth;
use App\Message;
use App\attachment;
use Carbon\Carbon;
use App\Enroll;

class MessageController extends Controller
{
    public function getTypeFile($extension)
    {
        $imageCollection = collect([
            'jpg','JPG',
            'jpeg','JPEG',
            'png','PNG',
            'gif','GIF'
        ]);

        $fileCollection = collect([
            'pdf','PDF',
            'docx','DOCX',
            'doc','DOC',
            'xls','XLS',
            'xlsx','XLSX',
            'ppt','PPT',
            'pptx','PPTX',
            'zip','ZIP',
            'rar','RAR',
        ]);

        $videoCollection = collect([
            'mp4','MP4',
            'avi','AVI',
            'flv','FLV',
        ]);

        $audioCollection = collect([
            'mp3','MP3',
            'ogg','OGG',
            'ogv','OGV',
            'oga','OGA',
            'wav','WAV',
        ]);

        if ($imageCollection->contains($extension)) {
            $type = 'image';
        }
        else if($fileCollection->contains($extension)){
            $type = 'file';
        }
        else if($videoCollection->contains($extension)){
            $type = 'video';
        }
        else if($audioCollection->contains($extension)){
            $type = 'audio';
        }
        else{
            $type = null;
        }
        return $type;
    }
    
    /**
     * => Function send_message_of_all_user sends message for all ids which get from request
     * @param: => from request
     *         - text of message
     *         - file and it's optional
     *         - id/s of users as an array
     *         - about what this mssage
     *         - from who for now \\next will be with session
     * @return: => Successfully Sent Message! if will success
     */
    // please before  excute this fun  run  php artisan Storage:link
    public function Send_message_of_all_user(Request $req)
    {
        //return response()->json($req->file->getClientOriginalExtension());
        $session_id = Auth::User()->id;
        $valid = Validator::make($req->all(), [
            'text' => 'nullable',
            'about' => 'exists:users,id',
            'users' => 'required|array',
            'users.*' => 'required|integer|exists:users,id',
            'file' => 'mimes:pdf,docx,doc,xls,xlsx,ppt,pptx,zip,rar,jpeg,jpg,png,gif,mp4,avi,flv,wav,mpga,ogg,ogv,oga',
        ]);
        if ($valid->fails()) {
            return HelperController::api_response_format(404, null, $valid->errors());
        }
        foreach ($req->users as $userId) {
            if ($session_id != $userId) {
                $user = User::find($userId);
                $to_Role_ids = $user->roles->pluck('id');
                $loged_user = User::find($session_id);
                $From_Role_ids = $loged_user->roles->pluck('id');
                $is_send = false;
                foreach ($to_Role_ids as $to_Role_id) {
                    foreach ($From_Role_ids as $From_Role_id) {
                        $permission = Message_Role::where('From_Role', $From_Role_id)->where('To_Role', $to_Role_id)->first();
                        if ($permission) {
                            $message = Message::Create(array(
                                'text' => $req->text,
                                'about' => (!$req->filled('about')) ? Auth::id() : $req->about,
                                'From' => Auth::id(),
                                'seen' => null,
                                'deleted' => 0,
                                'To' => $userId,
                            ));
                            if ($req->hasFile('file')) {
                                $attachment = attachment::upload_attachment($req->file, 'message');
                                $extension = $attachment->extension;
                                $message->file = $attachment->path;
                                $message->attachment_id = $attachment->id;
                            }

                            $message->save();
                            $message->about=User::find($message->about);
                            if(isset($message->about->attachment))
                                $message->about->picture=$message->about->attachment->path;
                            $message->From=User::find($message->From);
                            if(isset($message->From->attachment))
                                $message->From->picture=$message->From->attachment->path;
                            $message->To=User::find($message->To);
                            if(isset($message->To->attachment))
                                $message->To->picture=$message->To->attachment->path;
                            $message->Message = $message->text;
                            $message->type='text';

                            if(isset($attachment))
                            {
                                $message['type']=self::getTypeFile($extension);
                                $message['extension']=$attachment->extension;
                                // $message['name']=pathinfo($req->file->getClientOriginalName(), PATHINFO_FILENAME);
                                $message['name']=$attachment->name;
                            }
                            $is_send = true;
                            break;
                        }
                    }
                    if ($is_send) {
                        break;
                    }
                }
                if ($is_send == false) {
                    // return HelperController::api_response_format(404, User::find($userId), 'Fail ,  you do not have a permission to send message to this user!');
                    return HelperController::api_response_format(404, $userId, 'Fail ,  you do not have a permission to send message to this user!');
                }
                event(new \App\Events\sendMessage($message));

            } else {
                return HelperController::api_response_format(404, null, 'Fail , you can not send message for yourself!');
            }
        }

        return HelperController::api_response_format(201, $message, 'Successfully Sent Message!');
    }

    /**
     * @description:  delete message for all
     * @param Request $req => id of message that you want to delete
     * @return deleted if message was deleted  for all
     */
    public function deleteMessageForAll(Request $req)
    {
        $session_id = Auth::User()->id;
        $valid = Validator::make($req->all(), [
            'id' => 'required|exists:messages,id',
        ]);
        if ($valid->fails()) {
            return HelperController::api_response_format(404, $valid->errors());
        }
        $message = Message::find($req->id);
        if ($message->From == $session_id || $message->To == $session_id) {
            $message->update(array(
            'deleted' => Message::$DELETE_FROM_ALL
            ));
            $message->save();
            // return $message->get();
            $msg = MessageFromToResource::collection($message->get());
            foreach($msg as $messag)
                if($messag->id == $req->id)
                    return HelperController::api_response_format(201, $messag, 'message was deleted');
        }
        return HelperController::api_response_format(404,null , 'You do not have permission delete this message');
    }

    /*
        @Description: delete Message for user
                            0=default
                            1=deleted for all
                            2=deleted by receiver
                            3=deleted by sender
        @param: id of message and my_id if not use session
        @return: 'message' => 'message was deleted'
    */
    public function deleteMessageforMe(Request $req)
    {
        $session_id = Auth::User()->id;
        $valid = Validator::make($req->all(), [
            'id' => 'required|exists:messages,id',
        ]);
        if ($valid->fails()) {
            return HelperController::api_response_format(201,  $valid->errors());
        }
        $message = Message::find($req->id);
        // foreach($messages as $message)
        // {
            if ($message->From == $session_id || $message->To == $session_id) {

                if ($session_id == $message->To) {
                    if ($message) {
                        if ($message->deleted == Message::$DELETE_FOR_SENDER || $message->deleted == Message::$DELETE_FROM_ALL) {
                            $message->update(array(
                                'deleted' => Message::$DELETE_FROM_ALL
                            ));
    
                        } else {
                            $message->update(array(
                                'deleted' => Message::$DELETE_FOR_RECEIVER
                            ));
                        }
                    }
    
                } elseif ($session_id == $message->From) {
    
                    if ($message) {
                        if ($message->deleted == Message::$DELETE_FOR_RECEIVER || $message->deleted == Message::$DELETE_FROM_ALL) {
                            $message->update(array(
                                'deleted' => Message::$DELETE_FROM_ALL
                            ));
                        } else {
                            $message->update(array(
                                'deleted' => Message::$DELETE_FOR_SENDER
                            ));
                        }
                    }
                }
    
                $message->save();
                $msg = MessageFromToResource::collection($message->get());

                foreach($msg as $messag)
                if($messag->id == $req->id)
                    return HelperController::api_response_format(201, $messag, 'message was deleted');return HelperController::api_response_format(201, $msg, 'message was deleted');

            } else {
                return HelperController::api_response_format(404, null, 'You do not have permission delete this message');
            }
        // }
    }

    /**
     * Function list all messages take no parameter
     * @return all messages
     */
    public function List_All_Message()
    {
        $Message = Messageresource::collection(Message::get());
        return HelperController::api_response_format(200, $Message, 'All messages');
    }

    /**
     * @param Request $req --> id for message that you want see it.
     * @return message was seen
     */
    public function SeenMessage(Request $req)
    {
        $session_id = Auth::User()->id;
        $req->validate([
            'from' => 'required|exists:users,id'
        ]);
        $messages = Message::where('From', $req->from)->where('To', $session_id)->where('seen', null)->get();
        foreach ($messages as $msg) {
            $msg->seen = Carbon::now()->toDateTimeString();
            $msg->save();
        }
        return HelperController::api_response_format(200, $messages, 'message was seen');
    }

    public function ViewAllMSG_from_to(Request $request)
    {
        $request->validate([
            'search' => 'nullable|string',
            'user_id'     => 'required|exists:users,id'
        ]);
         $check = Message::where('From', $request->user_id)->orWhere('To', $request->user_id)->first();
            if ($check) {
            $current_user = Auth::id();
            $messages = Message::where(function ($query) use ($request, $current_user) {
                $query->whereIn('From', [$request->user_id, $current_user])->whereIn('To', [$request->user_id, $current_user]);
            });
            if($request->filled('search')){
                $messages->where(function ($query) use ($request) {
                    $query->where('text', 'LIKE' , "%$request->search%");
                });
            }
            $messages =$messages->orderBy('id')->get();
            // return ($messages);
            $msg = MessageFromToResource::collection($messages);
            return HelperController::api_response_format(200, $msg);
        }
        return HelperController::api_response_format(200, [], 'No Messages to this user');
    }

    /**
     * @Description: add Roles to send Message Permission
     * @param: Request  to access From_Role and To_Role
     * @return : if succss -> return MSG -> 'Message Role insertion sucess'
     *          IF NOT ->   return MSG -> 'Message Role insertion Fail'
     *
     */
    public function add_send_Permission_for_role(Request $req)
    {
        $valid = Validator::make($req->all(), [
            'From_Role' => 'required | exists:roles,id',
            'To_Role' => 'required | exists:roles,id'
        ]);
        if ($valid->fails()) {
            return response()->json(['msg' => $valid->errors()], 404);
        }
        $Message_Role = Message_Role::firstOrCreate([
            'From_Role' => $req->From_Role,
            'To_Role' => $req->To_Role,
        ]);
        if ($Message_Role) {
            return HelperController::api_response_format(200, null, 'Message Role insertion sucess');
        }
        return HelperController::api_response_format(404, null, 'Message Role insertion Fail');
    }

    /**
     * @description: view all threads of a user.
     * @param Request $req => id of a user.
     * @return all threads.
     */
    public function GetMyThreads(Request $request)
    {
        $messages = Message::where('From', $request->user()->id)->orWhere('To', $request->user()->id)->orderBy('created_at','desc')->get();
        $users = Message::GetMessageDetails($messages , $request->user()->id);
        return HelperController::api_response_format(200 , $users);
    }

    public function SearchMessage(Request $request)
    {
        $request->validate([
            'search' => 'required'
        ]);
        $current_user = Auth::id();
        $msgs = Message::where(function ($query) use ($request, $current_user) {
            $query->where('From', $current_user)->orWhere('To', $current_user);
        })->where(function ($query) use ($request) {
            $query->where('text', 'LIKE' , "%$request->search%");
        })->get();
        return HelperController::api_response_format(200 , $msgs,'Messages are....');
    }

    public function RolesWithAssiocatedUsers(Request $request)
    {
        $request->validate([
            'id' => 'array|exists:roles,id'
        ]);
        $roles = Role::get()->each(function($role){
            $role->users = User::role($role)->get();
        });
        if($request->filled('id'))
            $roles = Role::whereIn('id', $request->id)->get()->each(function($role){
                $role->users = User::role($role)->get();
            });
        return HelperController::api_response_format(200 , $roles);
    }

    public function BulkMessage(Request $request)
    {
        $request->validate([
            'role' => 'array|exists:roles,id',
            'type' => 'array|exists:academic_types,id',
            'levels' => 'array|exists:levels,id',
            'classes' => 'array|exists:classes,id',
            'courses' => 'array|exists:courses,id',
            'message' => 'string|required'
        ]);

        $session_id=Auth::id();
        $courseSeg=GradeCategoryController::getCourseSegmentWithArray($request);
        if(isset($request->role))
            $users=Enroll::whereIn('course_segment',$courseSeg)->whereIn('role_id',$request->role)->pluck('user_id');
        else
            $users=Enroll::whereIn('course_segment',$courseSeg)->pluck('user_id');
        $user_ids= array_values(array_unique($users->toArray()));

        $key=array_search($session_id,$user_ids);
        if($key !== false)
            unset($user_ids[$key]);
        $req = new Request([
            'users' => array_values(array_unique($user_ids)),
            'text' => $request->message
        ]);
        $message=self::Send_message_of_all_user($req);
        return HelperController::api_response_format(201, $message, 'Successfully Sent Message!');
    }
}
