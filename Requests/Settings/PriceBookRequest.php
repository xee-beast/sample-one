<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class PriceBookRequest extends FormRequest
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
            'expiry_date' => ['required','date'],
            'program_price_book_category_id' => ['required', 'exists:program_price_book_categories,id'],
            'enabled' => ['required','boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'program_price_book_category_id.required' => 'The Category field is required.'
        ];
    }


}
