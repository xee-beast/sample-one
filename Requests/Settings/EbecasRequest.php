<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class EbecasRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'college_code'=>['string','min:3'],
            'api_url'=>['url'],
            'api_key'=>['string','min:5'],
            'api_secret'=>['string','min:5'],
            'api_username'=>['string','min:3'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
