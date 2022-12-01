<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransportationFeeServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required','string','min:3', 'max:100'],
            'description' => ['nullable','max:255'],
            'enabled' => ['required','boolean'],
            'taxable' => ['required','boolean'],
            'fee' => ['required', 'numeric', 'min:0', 'max:10000'],
            'age_restriction_enabled' => ['required','boolean'],
            'min_age' => [Rule::requiredIf($this->get('age_restriction_enabled')),'nullable','numeric'],
            'max_age' => [Rule::requiredIf($this->get('age_restriction_enabled')),'nullable','numeric','gte:min_age'],
        ];
    }

}
