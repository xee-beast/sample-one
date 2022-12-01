<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class LocationUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'=> ['required','string', 'min:3','max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'enabled'=>['required', 'boolean']
        ];
    }
}
