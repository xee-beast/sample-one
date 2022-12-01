<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AgentCommissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'language_commission_id' => ['integer', 'nullable'],
            'vet_commission_id' => ['integer', 'nullable'],
        ];
    }

}
