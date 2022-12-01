<?php

namespace App\Http\Requests\Settings;

use App\Models\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaymentMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {

        $paymentPlanExists = false;
        if ( $this->get('is_payment_plan') ) {
            $query = PaymentMethod::whereIsPaymentPlan(1)->whereEnabled(1);
            if ($this->route('payment_method')) {
                $id = $this->route('payment_method')->id;
                $paymentPlanExists = $query->where('id', '!=', $id)->exists();
            } else {
                $paymentPlanExists = $query->exists();
            }
        }

        return [
            'name' => ['required','string','min:3', 'max:100'],
            'description' => ['nullable','max:255'],
            'surcharge_type'=>['string','required', function ($attribute, $value, $fail){
                if(!array_key_exists($value, config('constants.payment_methods'))){
                    $fail('Unable to determine the payment type.');
                }
            }],
            'is_payment_plan'=>['boolean','required', Rule::prohibitedIf($paymentPlanExists)],
            'enabled' => ['required','boolean'],
            'taxable' => ['required','boolean'],
            'value' => ['required', 'numeric', 'min:0', 'max:10000'],
        ];
    }

    public function messages(): array
    {
        return [
            'is_payment_plan.prohibited' => 'Another Method is already set as a Payment Plan. Only one method is available. '
        ];
    }

}
