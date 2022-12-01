<?php

namespace App\Http\Requests\Application;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApplicationStatusUpdateRequest extends FormRequest
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
            'status'=>['string','required', function ($attribute, $value, $fail){
                if(!array_key_exists($value, config('constants.application_form.status'))){
                    $fail('Unable to determine the application status.');
                }
            }],
        ];
    }

}
