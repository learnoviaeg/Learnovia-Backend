<?php


namespace App\Imports;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Validator;
use App\User;
use App\Installment;
use App\Payment;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;
HeadingRowFormatter::default('none');


class FeesImport implements  ToModel, WithHeadingRow
{
    /**
    * @param Collection $collection
    */
    public function model(array $row)
    {
        Validator::make($row,[
            'username' => 'required|exists:users,username'
        ])->validate();

        $data = [];
        $installments_count = Installment::count();
        foreach($row as $key => $value){
            if($key == 'username'){
                $data['user_id'] = User::where('username', $value)->select('id')->first()->id;
                $current_installment = 0;
            }
            if($key == 'to pay'){
                $data['to_pay'] = $value;
                $data['per_installment'] = $value/$installments_count;
                // dd($data);
            }
            if($key != 'to pay' && $key != 'username'){
                $installment = Installment::select('id')->skip($current_installment)->take(1)->first();
                if($data['to_pay'] == 0)
                    continue;
                if($value == $data['per_installment']){
                
                    Payment::updateOrCreate(
                        ['user_id'=>  $data['user_id'] , 'installment_id' => $installment->id],
                        ['amount' =>  $value ]
                    );
                    $data['to_pay'] = $data['to_pay'] - $value;
                    dd($data);

                }
                $current_installment++;
            }
        }
    }
}