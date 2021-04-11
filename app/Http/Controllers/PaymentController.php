<?php

namespace App\Http\Controllers;

use App\Payment;
use App\status;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function create(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'amount' => 'integer',
            'status' => 'string',
            'note' => 'string',
            'contract_id' => 'required|exists:contracts,id,allowance_period,NOT_NULL',
        ]);
        $status_id = null;
        if (isset($request->status)) {
            $status_id = status::create([
                'name' => $request->status
            ])->id;
        }
        $Payment = Payment::create([
            'amount' => (isset($request->amount)) ? $request->amount : null
            , 'date' => (isset($request->date)) ? $request->date : null
            , 'note' => (isset($request->note)) ? $request->note : null
            , 'contract_id' => (isset($request->contract_id)) ? $request->contract_id : null
            , 'status_id' => $status_id
        ]);
        return HelperController::api_response_format(200, $Payment, 'Added Successfully.');
    }

    public function delete(Request $request)
    {
        $request->validate([
            'payment_id' => 'required|integer|exists:payments,id'
        ]);
        Payment::find($request->payment_id)->delete();
        return HelperController::api_response_format(200, null, 'Deleted Successfully.');
    }

    public function postponedPayment(Request $request)
    {
        $request->validate([
            'payment_id' => 'required|integer|exists:payments,id',
            'date' => 'required|date'
        ]);
        $old_payment = Payment::find($request->payment_id);
        $Payment = Payment::create([
            'amount' => $old_payment->amount,
            'date' => $request->date, 
            'note' => $old_payment->note,
            'contract_id' => $old_payment->contract_id,
            'status_id' => $old_payment->status_id
        ]);
        $old_payment->update([
            'child_id' => $Payment->id
        ]);
        return HelperController::api_response_format(200, null, ' Postponeded Successfully.');
    }

    public function payPayment(Request $request)
    {
        $request->validate([
            'payment_id' => 'required|integer|exists:payments,id',
            'amount' => 'required|integer'
        ]);
        $payment = Payment::find($request->payment_id);
        $status = [
            ['name' => 'Paid'],
            ['name' => 'Postpond',],
            ['name' => 'NOT Paid'],
        ];
        foreach ($status as $state) {
            status::firstOrCreate($state);
        }
        $status_id_not_paid = status::where('name', "NOT Paid")->first()->id;
        $status_id_paid = status::where('name', "Paid")->first()->id;
        if ($request->amount == $payment->amount) {
            $payment->update(['status_id' => $status_id_paid]);
            return HelperController::api_response_format(200, $payment, 'Paid Successfully.');
        } elseif ($request->amount < $payment->amount) {
            $request->validate([
                'case' => 'required|string|in:new,next'
            ]);
            $status_id_postponed = status::where('name', "Postpand")->first()->id;
            $amount = $payment->amount;
            $payment->update([
                'amount' => $request->amount,
                'status_id' => $status_id_paid]);
            switch ($request->case) {
                case 'new':
                    $request->validate([
                        'date' => 'required|date'
                    ]);
                    $new_payment = Payment::create([
                        'amount' => ($amount - $request->amount),
                        'date' => $request->date,
                        'note' => $payment->note,
                        'contract_id' => $payment->contract_id,
                        'status_id' => $status_id_not_paid
                    ]);
                    return HelperController::api_response_format(200, $new_payment, 'One payment is paid Successfully and a new payment with the remainder added Successfully.');
                    break;
                case 'next':
                    $payment->update([
                        'status_id' => $status_id_paid,
                        'amount' => $request->amount
                    ]);
                $next_payment = Payment::where('contract_id',$payment->contract_id)->where('id','>',$payment->id)->orderBy('date', 'asc')->first();
                $next_payment->update([
                        'amount' => $next_payment->amount + ($amount - $request->amount),
                         'status_id' =>  $status_id_postponed
                    ]);
                    return HelperController::api_response_format(200, $next_payment, 'One payment is paid Successfully and the remainder is added to the next payment Successfully..');
                    break;
            }
        } elseif ($request->amount > $payment->amount) {
            $payment->update(['status_id' => $status_id_paid]);
            $remainder = $request->amount- $payment->amount;
            $all_next_payment = Payment::where('contract_id',$payment->contract_id)->where('id','>',$payment->id)->orderBy('date', 'asc')->get();
            
            foreach ( $all_next_payment as $payment){
                if ($remainder >= $payment->amount) {
                    $payment->update(['status_id' => $status_id_paid]);
                    $remainder = $remainder - $payment->amount;

                }else {

                    $new_payment = Payment::create([
                        'amount' => ($payment->amount - $remainder),
                        'date' => $payment->date,
                        'note' => $payment->note,
                        'contract_id' => $payment->contract_id,
                        'status_id' => $status_id_not_paid
                    ]);
                    $payment->update([
                        'amount' => $remainder,
                        'status_id' => $status_id_paid]);
                }
            }
            return HelperController::api_response_format(200, 'Amount is distributed on all payments Successfully.');
        }
    }
}