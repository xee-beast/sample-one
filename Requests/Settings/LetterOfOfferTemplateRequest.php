<?php

namespace App\Http\Requests\Settings;

use App\Models\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LetterOfOfferTemplateRequest extends FormRequest
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
            'faculty_id' => ['required', 'exists:faculties,id',Rule::unique('letter_of_offer_templates')->ignore(request()->template->id ?? null)],
        ];
    }

    public function messages(): array
    {
        return [
            'faculty_id.unique' => 'Template already exist for selected faculty.'
        ];
    }
}
