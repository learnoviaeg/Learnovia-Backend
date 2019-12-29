<?php

namespace App\Http\Controllers;

use App\attachment;
use App\Contract;
use App\Payment;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContractController extends Controller
{
    public function create(Request $request){
        $request->validate([
            'attached_file' => 'nullable|file|mimes:pdf,docx,doc,xls,xlsx,ppt,pptx,zip,rar,txt',
            'start_date' => 'date',
            'end_date' => 'date',
            'numbers_of_users' => 'integer',
            'total' => 'integer',
            'allowance_period' => 'integer',
        ]);
        if (isset($request->attached_file)) {
            $fileName = attachment::upload_attachment($request->attached_file, 'Event');
            $file_id = $fileName->id;
        }
        $contract =Contract::create([
            'attachment_id' => (isset($file_id))?$file_id:null
            ,'start_date' => (isset($request->start_date))?$request->start_date:null
            ,'end_date'=>(isset($request->end_date))?$request->end_date:null
            ,'numbers_of_users' => (isset($request->numbers_of_users))?$request->numbers_of_users:null
            ,'total' =>(isset($request->total))?$request->total:null
            ,'allowance_period'=>(isset($request->allowance_period))?$request->allowance_period:null
        ]);
        return HelperController::api_response_format(200, $contract, 'Added Successfully');

    }

    public function update(Request $request){
        $request->validate([
            'contract_id' => 'required|exists:contracts,id',
            'attached_file' => 'nullable|file|mimes:pdf,docx,doc,xls,xlsx,ppt,pptx,zip,rar,txt',
            'end_date' => 'date',
            'numbers_of_users' => 'integer',
            'total' => 'integer',
            'allowance_period' => 'integer',
        ]);
        if (isset($request->attached_file)) {
            $fileName = attachment::upload_attachment($request->attached_file, 'Contract');
            $file_id = $fileName->id;
        }
        $contract =Contract::find($request->contract_id);
        $contract->update([
            'attachment_id' => (isset($file_id))?$file_id:$contract->attachment_id
            ,'end_date'=>(isset($request->end_date))?$request->end_date:$contract->end_date
            ,'numbers_of_users' => (isset($request->numbers_of_users))?$request->numbers_of_users:$contract->numbers_of_users
            ,'total' =>(isset($request->total))?$request->total:$contract->total
            ,'allowance_period'=>(isset($request->allowance_period))?$request->allowance_period:$contract->allowance_period
        ]);
        return HelperController::api_response_format(200, $contract, 'updated Successfully');
    }

    public function Contract_Restrict_Alarm(Request $request)
    {
        $return_message =[
            [   
                'message' => 'Your account is suspended for now',
                'color' => '-'
            ],
            [   
                'message' => 'Your allowence period will be expire soon, please Pay',
                'color' => 'red'
            ],
            // [   
            //     'message' => 'Your allowence period will be expire soon, please Pay',
            //     'color' => 'orange'
            // ]
        ];
        $user=User::find(Auth::id());
        $contract = Contract::where('end_date', '>', Carbon::now())->get()->first();

        $today_date=Carbon::now();
        
        $payments = Payment::where('contract_id',$contract->id)->orderBy('date','asc')->get();

        foreach($payments as $payment)
        {
            $data_difference = Carbon::parse($today_date)->diffInDays($payment->date, false);

            $allow_period = $data_difference+$contract->allowance_period;

            #خلصت فترة السماح واتعمل block
            if(($allow_period < 0) && ($payment->status->name == 'NOT Paid'))
            {
                $user->update([
                    'suspend' => 1
                ]);
                return HelperController::api_response_format(200, $payment, $return_message[0]);
            }

            #هو في فترة السماح دلوقتي
            else if(($allow_period > 0) && ($payment->status->name == 'NOT Paid'))
                return HelperController::api_response_format(200, $payment, $return_message[1]);
        }
    }
}
