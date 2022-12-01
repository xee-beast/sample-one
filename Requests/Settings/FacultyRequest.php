<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FacultyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = null;
        if( request()->route('faculty') ){
            $id = request()->route('faculty');
        }
        return [
            'name' => ['required','string','min:3', 'max:100'],
            'description' => ['nullable','max:255'],
            'enabled' => ['required','boolean'],
            'location_id' => ['required', 'exists:locations,id'],
            'ebecas_id' => ['required', Rule::unique('faculties')->ignore($id)],
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
            'ebecas_id' => 'eBECAS Faculty',
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
            'ebecas_id.unique' => 'This :attribute is already associated with another faculty.',
        ];
    }

}
