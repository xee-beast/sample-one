<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class TransportationRequest extends FormRequest
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
            'return' => ['required','boolean'],
            'origin' => ['required','required', 'exists:locations,id'],
            'destination' => ['required','required', 'exists:locations,id'],
            'fee' => ['required', 'numeric', 'min:0', 'max:10000'],
            'addons.*.transportation_fee_addon_id' => ['distinct'],
            'services.*.transportation_fee_service_id' =>['distinct']
        ];
    }

}
