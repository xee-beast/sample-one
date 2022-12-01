<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AgentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ebecas_id' => ['required', 'integer','unique:agents']
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
            'ebecas_id' => 'agent',
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
            'ebecas_id.unique' => 'This agent already exists and cannot be imported again.',
        ];
    }


}
