<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PackagedProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required','string','min:3', 'max:100'],
            'description' => ['nullable','max:255'],
            'enabled' => ['required','boolean'],
            'gap_limit' => ['required','integer'],
            'programs' => ['present','array','min:1'],
            'programs.*.faculty_id' => ['required'],
            'programs.*.discount' => ['required', 'numeric', 'min:0', 'max:10000'],
            'programs.*.program_id' => ['required'],
        ];
    }

}
