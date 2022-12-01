<?php


namespace App\Http\Requests\Settings;


use Illuminate\Foundation\Http\FormRequest;

class AccommodationFeeServiceRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required','string','min:3', 'max:100'],
            'description' => ['nullable','max:255'],
            'type'=>['string','required', function ($attribute, $value, $fail){
                if(!array_key_exists($value, config('constants.accommodation_service_types'))){
                    $fail('Unable to determine the accommodation service type.');
                }
            }],
            'enabled' => ['required','boolean'],
            'taxable' => ['required','boolean'],
            'fee' => ['required', 'numeric', 'min:0', 'max:10000'],
            "daily_fee" => ['present', 'nullable', 'numeric', 'min:0', 'max:10000']
        ];
    }
}
