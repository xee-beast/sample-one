<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;

class UserStoreRequest extends FormRequest
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
            'email' => ['required','min:2','max:50','email','unique:users'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()], // Require at least one uppercase and one lowercase letter...
            'mobile' => ['present'],
            'country_id' => ['required', 'exists:countries,id'],
            'enabled'=>['required','boolean'],
            'user_verified'=>['required','boolean'],
            'email_verified'=>['required','boolean'],
            'user_type'=>['string','required', function ($attribute, $value, $fail){
                if(!in_array($value, config('constants.user_types'))){
                    $fail('Unable to determine the user type.');
                }
            }],
            'parent_user_id' => ['sometimes', 'nullable', Rule::exists(User::class,'id')]
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
            'parent_user_id'=>'account manager'
        ];
    }


}
