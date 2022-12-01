<?php

namespace App\Http\Requests\Settings;


use Illuminate\Foundation\Http\FormRequest;

class FacultyInsuranceRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'insurances' =>['present'],
            'insurances.*.insurance_fee_id' =>['required'],
            'insurances.*.ebecas_product_id' =>['required'],
            'insurances.*.duration' =>['required'],
            'insurance_id' => ['required'],

        ];
    }
}
