<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {



        return [
            'first_name' => ['required','min:2','max:50'],
            'last_name' => ['required','min:2','max:50'],
            'email' => ['required','min:2','max:50','email',Rule::unique('users')->ignore($this->user->id) ],
            'password' => ['nullable', 'confirmed', Password::min(8)->mixedCase()], // Require at least one uppercase and one lowercase letter...
            'mobile' => ['present'],
            'country_id' => ['required', 'exists:countries,id'],
            'enabled'=>['required','boolean'],
            'user_verified'=>['required','boolean'],
            'email_verified'=>['required','boolean'],
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
            'country_id' => 'country',
        ];
    }


}
