<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CountryRequest extends FormRequest
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
            'name' => ['string', 'required','min:2', 'max:40'],
            'code' => ['nullable','string', 'min:2', 'max:20'],
            'ebecas_id' => ['nullable','numeric'],
            'region_id' => ['nullable','numeric'],
            'enabled' => ['boolean']
        ];
    }


    public function prepareForValidation()
    {
        $this->merge([
            'code' => strtoupper(Str::slug($this->code,'')),
        ]);

    }
}
