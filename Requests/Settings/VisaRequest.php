<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class VisaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {

        return [
            'name'=>['required', 'min:2','max:100'],
            'max_weeks'=>['required','integer', 'min:0','max:1000'],
            'is_student'=>['required', 'boolean'],
            'enabled'=>['required', 'boolean'],
            'ebecas_id' => ['required','numeric'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'max_weeks' => 'Maximum weeks of study',
            'is_student' => 'Student visa selector',
        ];
    }



}
