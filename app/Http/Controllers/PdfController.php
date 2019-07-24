<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PDF;
use App\PdfAssigment;
class PdfController extends Controller
{


    public function pdf(Request $request){

        $request->validate([
            'text' => 'required',
            'title' => 'required',
        ]);
        
        $pdf = new PdfAssigment;
        $pdf->text = $request->text;
        $pdf->title = $request->title;
        $pdf->save();

        PDF::AddPage();
        PDF::Write(0, $pdf->text);
        PDF::Output(Storage_path('app\public\PDF\\'.$pdf->title.'.pdf'), 'F');
        return response()->json($pdf , 200);

//   for ($i = 0; $i < 1; $i++) {
//     PDF::SetTitle('Hello World'.$i);
//     PDF::AddPage();
//     PDF::Write(0, 'Hello World'.$i);
//     PDF::Output(public_path('hello_world' . $i . '.pdf'), 'F');
//     PDF::reset();
//   }

 }
}
