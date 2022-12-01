<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class TransportationFeeAddonRequest extends FormRequest
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
            'fee' => ['required', 'numeric', 'min:0', 'max:10000'],
            'enabled' => ['required','boolean'],
            'taxable' => ['required','boolean'],
        ];
    }

}
