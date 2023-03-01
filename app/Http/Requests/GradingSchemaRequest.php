<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GradingSchemaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name'=>'required',
            'chain' => 'required',
            'chain.*.level_id'=>'required',
            'chain.*.segment_id'=>'required'
        ];
    }
}
