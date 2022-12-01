<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProgramFeeServiceRequest extends FormRequest
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
            'type'=>['string','required', function ($attribute, $value, $fail){
                if(!array_key_exists($value, config('constants.program_service_types'))){
                    $fail('Unable to determine the program service type.');
                }
            }],
            'instalment_plan_enabled' => ['required','boolean'],
            'first_instalment_amount' => ['numeric', Rule::requiredIf($this->get('instalment_plan_enabled')), 'nullable', function ($attribute, $value, $fail) {
                if ($this->get('first_instalment_amount') > $this->get('fee')) {
                    $fail('The First Instalment Amount cannot be greater then the service total amount.');
                }
            }],
            'enabled' => ['required','boolean'],
            'taxable' => ['required','boolean'],
            'fee' => ['required', 'numeric', 'min:0', 'max:10000']
        ];
    }

}
