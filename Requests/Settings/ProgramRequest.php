<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class ProgramRequest extends FormRequest
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
            'name' => ['required','string','min:3', 'max:100'],
            'description' => ['nullable','max:255'],
            'program_price_book_id' => ['required','exists:program_price_books,id'],
            'enabled' => ['required','boolean'],
            'is_language' => ['required','boolean'],
            'min_length' => [Rule::requiredIf($this->get('length_restriction_enabled')),'nullable','numeric'],
            'max_length' => [Rule::requiredIf($this->get('length_restriction_enabled')),'nullable','numeric','gte:min_length'],
            'min_age' => [Rule::requiredIf($this->get('age_restriction_enabled')),'nullable','numeric'],
            'max_age' => [Rule::requiredIf($this->get('age_restriction_enabled')),'nullable','numeric','gte:min_age'],
            'active_visas' => [Rule::requiredIf($this->get('visa_restriction_enabled'))],
            'services.*.program_fee_service_id' =>['distinct']
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
            'program_price_book_id' => 'price book',
            'services.*.program_fee_service_id' => 'Program Service'
        ];
    }
}
