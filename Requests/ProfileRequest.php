<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileRequest extends FormRequest
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
            'first_name' => ['required','min:2','max:50'],
            'last_name' => ['required','min:2','max:50'],
            'email' => ['required','min:2','max:50','email',Rule::unique('users')->ignore(auth()->user()->id)],
            'mobile' => ['present'],
        ];
    }
}
