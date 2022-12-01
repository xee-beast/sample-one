<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoleRequest extends FormRequest
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
            'name' => ['string','min:3','max:50',
                        Rule::unique('roles')->ignore($this->role->id ?? null),
                        function ($attribute, $value, $fail){
                            if(in_array($value,array_keys(config('constants.system_roles')))){
                                $fail('This is a reserved role and it cannot be used. Try another one!');
                            }}
                ],
            'description' => ['nullable','max:255']
        ];
    }
}
