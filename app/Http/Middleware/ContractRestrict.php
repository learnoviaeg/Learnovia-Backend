<?php

namespace App\Http\Middleware;

use App\Contract;
use App\Http\Controllers\HelperController;
use App\Payment;
use App\User;
use Carbon\Carbon;
use Closure;
use Illuminate\Support\Facades\Auth;

class ContractRestrict
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        $return_message =[
            [   
                'message' => 'Your account is suspended for now',
                'color' => '-'
            ],
            [   
                'message' => 'Your allowence period will be expire soon, please Pay',
                'color' => 'red'
            ]
        ];
        $user=User::find(Auth::id());
        $contract = Contract::where('end_date', '>', Carbon::now())->get()->first();
        if(!isset($contract))
            return HelperController::api_response_format(200, null, 'you can\'t add users');

        $today_date=Carbon::now();        
        $payments = Payment::where('contract_id',$contract->id)->orderBy('date','asc')->get();

        foreach($payments as $payment)
        {
            if($payment->status->name == 'Paid')
                continue;
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
            {
                return HelperController::api_response_format(200, $response, $return_message[1]);
            }
        }
        
        return $response;
    }
}
