<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SpecialOfferRequest extends FormRequest
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
            'category_id' => ['required'],
            'region_id' => ['required'],
            'application_submitted_from' => ['required', 'date'],
            'application_submitted_to' => ['required', 'date'],
            'application_program_from' => ['required', 'date'],
            'application_program_to' => ['required', 'date'],
            'restrict_by_program_length_from' => [Rule::requiredIf($this->get('restrict_by_program_length_length'))],
            'restrict_by_program_length_to' => [Rule::requiredIf($this->get('restrict_by_program_length_length'))],

            'onshore' => ['required', 'boolean'],
            'offshore' => ['required', 'boolean'],
            'locations' => ['required'],
            'restrict_by_program_length' => ['required', 'boolean'],

            'pricebooks.*.program_id' => ['distinct'],
            'services.*.override_duration' => ['required', 'boolean'],
        ];
    }


}
