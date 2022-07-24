<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Installment;
use App\Fees;

class InstallmentController extends Controller
{
    public function __construct()
    {
        // $this->middleware(['permission:installment/create'],   ['only' => ['store']]);  
    }

    public function index(Request $request)
    {
        return response()->json(['message' => null, 'body' => Installment::all()], 200); 
    }

   public function store(Request $request)
   {
        $request->validate([
            'installments' => 'required|array',
            'installments.date.*' => 'required|date|date_format:Y-m-d',
            'installments.percentage.*' => 'nullable',

        ]);
        $total_percentage = 0;

        if(!isset($request->installments[0]['percentage']))
            $percentage = 100 / count($request->installments);

        foreach($request->installments as $key => $Installment){
            $data[] = [
                'date' => $Installment['date'],
                'percentage' => isset($percentage) ? $percentage  : $Installment['percentage'] ,
            ]; 
                $total_percentage +=  isset($percentage) ? $percentage  : $Installment['percentage'];
        }

        if($total_percentage != 100)
            return response()->json(['message' => 'Percentages total should be 100%' , 'body' => null], 200); 
            
        $Installments = Installment::insert($data);
        return response()->json(['message' => null, 'body' => $Installments], 200); 
   } 

   public function destroy($id)
   {
        Installment::find($id)->delete();
       return response()->json(['message' => null, 'body' =>null], 200); 
   }


   public function reset()
   {
        Installment::truncate();
       return response()->json(['message' => null, 'body' =>null], 200); 
   }

   public function user_installments(Request $request)
   {
        $request->validate([
            'user_id' => 'required|exists:users,id'
        ]);
        $installments = Installment::get();
        $paid = Fees::select('percentage')->where('user_id', $request->user_id)->first();
        $percentage_paid = $paid->percentage;
        $total_percentage_of_installments = 0;
        if(isset($paid->pecentage))
            $percentage_paid = $paid->percentage;

        foreach($installments as $installment){
            $total_percentage_of_installments += $installment->percentage; 
            $installment->paid_or_not = ($percentage_paid >= $total_percentage_of_installments) ? true : false;
        }

        return response()->json(['message' => null, 'body' => $installments], 200); 
   }
}
