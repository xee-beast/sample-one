<?php

namespace App\Http\Requests\Settings;


use Illuminate\Foundation\Http\FormRequest;

class FacultyPaymentMethodRequest extends FormRequest
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
            'payment_methods' =>['present'],
            'payment_methods.*.payment_method_id' =>['required','distinct'],
            'payment_methods.*.ebecas_product_id' =>['required','distinct'],
        ];
    }
}
