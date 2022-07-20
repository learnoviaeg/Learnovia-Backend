<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Installment;

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
        'dates'   => 'required',
        'dates.*' => 'date|date_format:Y-m-d',
    ]);
    foreach($request->dates as $date)
        $data[] = [
            'date' => $date,
        ]; 
    $Installments = Installment::insert($data);
    return response()->json(['message' => null, 'body' => $Installments], 200); 
   } 

   public function destroy($id)
   {
        Installment::find($id)->delete();
       return response()->json(['message' => null, 'body' =>null], 200); 
   }


}
