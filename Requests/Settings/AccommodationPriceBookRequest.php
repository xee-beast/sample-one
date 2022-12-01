<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class AccommodationPriceBookRequest extends FormRequest
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
            'price_book_category_id' => ['required', 'exists:accommodation_price_book_categories,id'],
            'enabled' => ['required','boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'price_book_category_id.required' => 'The Category field is required.'
        ];
    }


}
