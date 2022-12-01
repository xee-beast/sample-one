<?php

namespace App\Http\Requests\Application;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApplicationDetailRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'salutation' => ['required','string'],
            'other_salutation' => ['string', 'nullable'],
            'gender' => ['required','string'],
            'first_name' => ['required','string'],
            'middle_name' => ['nullable','string'],
            'last_name' => ['required','string'],
            'email' => ['required','string','email'],
            'mobile' => ['string', 'nullable'],
            'marketing_consent' => ['required','boolean'],
            'dob' => ['required','date'],
            'passport_number' => ['required','min:2','max:30'],
            'country' => ['required','exists:countries,id'],
            'language' => ['required','exists:languages,id'],
            'country_of_birth' => ['required','exists:countries,id'],
            'occupation' => ['string', 'nullable'],
            'living_in_australia' => ['required','boolean'],
            'australian_resident' => ['required','boolean'],
            'current_visa_type' => ['exists:visas,id', 'nullable', Rule::requiredIf($this->get('has_australian_visa'))],
            'current_visa_expiry' => ['date', 'nullable', Rule::requiredIf($this->get('has_australian_visa'))],
            'has_had_student_visa' => ['required','boolean'],
            'has_australian_visa' => ['required','boolean'],
            'visa_applying_for' => ['required','exists:visas,id'],
            'visa_application_australia' => ['required','boolean'],
            'visa_application_location' => ['nullable', 'exists:countries,id', Rule::requiredIf(! $this->get('visa_application_australia'))],
            'current_residence_address_line_1' => ['string', 'required'],
            'current_residence_address_line_2' => ['string', 'nullable'],
            'current_residence_address_line_3' => ['string', 'nullable'],
            'current_residence_address_city' => ['required','string'],
            'current_residence_address_state' => ['string', 'nullable'],
            'current_residence_address_postcode' => ['required','string'],
            'current_residence_address_country' => ['required','exists:countries,id'],
            'next_of_kin_full_name' => ['required','string'],
            'next_of_kin_phone' => ['required','string'],
            'next_of_kin_email' => ['required','string'],
            'next_of_kin_relationship' => ['required','string'],
            'agent_assistance' => ['boolean', 'nullable', Rule::requiredIf(auth()->user()->user_type != 'agent')],
            'agent_name' => ['string','nullable', Rule::requiredIf($this->get('agent_assistance'))]
        ];
    }

}
