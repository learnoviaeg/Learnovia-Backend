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
use App\Fees;
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
        $paid_amout = 0;
        $installments_count = Installment::count();
        foreach($row as $key => $value){
            if($key == 'username'){
                $data['user_id'] = User::where('username', $value)->select('id')->first()->id;
            }
           
            if($key == 'to_pay')
                $data['to_pay'] = $value;
            
            if($key != 'to_pay' && $key != 'username')
                $paid_amout += $value; 
            
        }
        Fees::updateOrCreate(
            ['user_id'=>  $data['user_id']],
            ['paid_amount' =>  ($paid_amout <= $data['to_pay'])  ? $paid_amout : $data['to_pay'], 'total_amount' => $data['to_pay'] , 'percentage' => (($paid_amout / $data['to_pay']) *100) ]
        );
    }
}