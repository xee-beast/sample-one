<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class FacultyTransportationRequest extends FormRequest
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
            'transportations' =>['present'],
            'transportations.*.transportation_id' =>['required','distinct'],
            'transportations.*.ebecas_product_id' =>['required','distinct'],
            'services' =>['present'],
            'services.*.fee_service_id' =>['required','distinct'],
            'services.*.ebecas_product_id' =>['required','distinct'],
            'addons' =>['present'],
            'addons.*.addon_id' =>['required','distinct'],
            'addons.*.ebecas_product_id' =>['required','distinct'],
        ];
    }
}
