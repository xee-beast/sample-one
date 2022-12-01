<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AccommodationRequest extends FormRequest
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
            'accommodation_price_book_id' => ['required','exists:accommodation_price_books,id'],
            'accommodation_category_id' => ['required','exists:accommodation_categories,id'],
            'enabled' => ['required','boolean'],
            'addons.*.accommodation_fee_addon_id' => ['distinct'],
            'min_length' => [Rule::requiredIf($this->get('length_restriction_enabled')),'nullable','numeric'],
            'max_length' => [Rule::requiredIf($this->get('length_restriction_enabled')),'nullable','numeric','gte:min_length'],
            'services.*.accommodation_fee_service_id' =>['distinct']
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
            'accommodation_price_book_id' => 'price book',
            'accommodation_category_id' => 'category',
        ];
    }
}
