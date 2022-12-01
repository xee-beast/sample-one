<?php

namespace App\Http\Requests\Application;

use Illuminate\Foundation\Http\FormRequest;

class ApplicationInsuranceRequest extends FormRequest
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
            '*' =>['present'],
            '*.duration' =>['required'],
            '*.faculty_id' =>['required'],
            '*.start_date' =>['required'],
            '*.end_date' =>['required'],
            '*.insurance_id' =>['required'],
        ];
    }
}
