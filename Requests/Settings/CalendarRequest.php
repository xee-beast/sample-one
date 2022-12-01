<?php

namespace App\Http\Requests\Settings;

use App\Rules\CalendarDateRule;
use Illuminate\Foundation\Http\FormRequest;

class CalendarRequest extends FormRequest
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
            'calendar_category_id' => ['required', 'exists:calendar_categories,id'],
            'deleted_records' => [],
            'dates' => ['required']
        ];
    }

    public function messages(): array
    {
        return[
            'calendar_category_id.required' => 'The Category field is required.'
        ];
    }


}
