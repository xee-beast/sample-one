<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class LocationStoreRequest extends FormRequest
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
            'enabled'=>['required', 'boolean'],
            'ebecas_id' => ['required', 'integer','unique:locations']
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
            'ebecas_id' => 'location',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'ebecas_id.unique' => 'This location is already associated with another record.',
        ];
    }


}
