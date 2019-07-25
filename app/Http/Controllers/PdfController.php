<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use TCPDF;
use TXPDF;
use Spatie\PdfToImage\Pdf;
use App\PdfAssigment;
use Illuminate\Support\Facades\Storage;

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

        TXPDF::AddPage();
        TXPDF::Write(0, $pdf->text);
        TXPDF::Output(Storage_path('app\public\PDF\\'.$pdf->title.'.pdf'), 'F');
        return response()->json($pdf , 200);

 }

public function convertPdfToImage(Request $request){

    $pdf=new Pdf($request->pdf);
    $pdf->saveImage('public\\'.$request->title.".jpg");

}

public function convertImageToPdf(Request $request){
    $request->validate([
        'img' => 'required',
    ]);

        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->AddPage();
        $encoded =base64_encode(file_get_contents($request->file('img')));
        $imgdata = base64_decode($encoded);
        $pdf->Image('@'.$imgdata);
        $pdf->Output(Storage_path('app\public\\'.$request->title.'.pdf'), 'F');
 }

public function pdfSave(Request $request){

        $request->validate([
            'pdf' => 'required|mimes:pdf',
        ]);

        Storage::disk('public')->put(
            $request->pdf->getClientOriginalName(),
            $request->file
        );

}



}

