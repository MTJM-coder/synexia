<?php

namespace Modules\Marketplace\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubscribeToPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subscription_plan_id' => ['required', 'integer', 'exists:subscription_plans,id'],
            'amount_paid' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
