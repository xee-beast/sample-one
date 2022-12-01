<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FacultyAccommodationRequest extends FormRequest
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
            'accommodations' =>['present'],
            'accommodations.*.accommodation_id' =>['required','distinct'],
            'accommodations.*.ebecas_product_id' =>['required','distinct'],
            'services' =>['present'],
            'services.*.fee_service_id' =>['distinct'],
            'services.*.ebecas_product_id' =>['required','distinct'],
            'addons.*.addon_id' =>['distinct'],
            'addons.*.ebecas_product_id' =>['required','distinct'],
        ];
    }
}
