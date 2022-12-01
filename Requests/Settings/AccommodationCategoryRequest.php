<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AccommodationCategoryRequest extends FormRequest
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
            'age_restricted' => ['required','boolean'],
            'min' => [Rule::requiredIf($this->get('age_restricted')),'integer', 'nullable'],
            'max' => [Rule::requiredIf($this->get('age_restricted')),'integer', 'nullable','gt:min'],
        ];
    }

}
