<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\attachment;

class SittingsController extends Controller
{
    public function setLogo(Request $request)
    {
        $request->validate([
            'school_logo' => 'required|mimes::jpg,jpeg,png',
            'school_name' => 'required|string',
        ]);
        $check=attachment::where('type','Logo')->first();
        if($check)
            $check->delete();

        $attachment = attachment::upload_attachment($request->school_logo, 'Logo',null,$request->school_name);

        // return $attachment;
        return response()->json(['message' => __('messages.logo.set'), 'body' => $attachment], 200);
    }

    public function deleteLogo(Request $request)
    {
        $request->validate([
            'attachment_id' => 'required|exists::attachments,id',
        ]);
        $check=attachment::whereId($request->attachment_id)->first();
        if($check)
            $check->delete();

        return response()->json(['message' => __('messages.logo.delete'), 'body' => null], 200);
    }

    public function getLogo(Request $request)
    {
        $attachment=attachment::where('type','Logo')->first();
        if(!$attachment)
            return response()->json(['message' => __('messages.logo.faild'), 'body' => null], 200);

        return response()->json(['message' => __('messages.logo.get'), 'body' => $attachment], 200);
    }
}
