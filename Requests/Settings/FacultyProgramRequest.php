<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class FacultyProgramRequest extends FormRequest
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
            'programs' =>['present'],
            'programs.*.program_id' =>['required','distinct'],
            'programs.*.ebecas_product_id' =>['required','distinct'],
            'services' =>['present'],
            'services.*.program_fee_service_id' =>['required','distinct'],
            'services.*.ebecas_product_id' =>['required','distinct'],
        ];
    }
}
