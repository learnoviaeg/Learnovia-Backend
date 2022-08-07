<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\NotificationSetting;
use App\Repositories\ChainRepositoryInterface;
use App\Repositories\NotificationRepoInterface;
use App\Jobs\FeesJob;
use App\Events\CreateInstallmentEvent;

class NotificationSettingsController extends Controller
{
    public function __construct(ChainRepositoryInterface $chain , NotificationRepoInterface $notification)
    {
        $this->notification = $notification;
        $this->chain        = $chain;
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $request->validate([
            'type' => 'required|string|in:attendance,fees'
        ]);
        $notificationSetting=NotificationSetting::where('type',$request->type)->first();
        return response()->json(['message' => 'Notification setting.','body' => $notificationSetting], 200);
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
            'after_min' => 'required|integer',
            'roles' => 'nullable',
            'roles.*' => 'exists:roles,id',
            'users' => 'nullable',
            'users.*' => 'exists:users,id',
            'type' => 'required|string|in:attendance,fees'
        ]);

        if($request->type == 'attendance')
            if(!isset($request->roles) && !isset($request->users))
                return response()->json(['message' => __('messages.error.cannot_add'), 'body' => []], 400);

        NotificationSetting::updateOrCreate([
            'type' => $request->type
        ],[
            'after_min' => $request->after_min,
            'roles' => isset($request->roles) ? json_encode($request->roles) : null,
            'users' => isset($request->users) ? json_encode($request->users) : null,
        ]);

        if($request-> type == 'fees'){
            event(new CreateInstallmentEvent($this->chain , $this->notification));

        }

        return response()->json(['message' => 'Notification was set.','body' => null], 200);
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
