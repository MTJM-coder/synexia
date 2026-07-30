<?php

namespace Modules\Marketplace\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateShopRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:30'],
            'country' => ['nullable', 'string', 'max:100'],
            'subscription_plan_id' => ['required', 'integer', 'exists:subscription_plans,id'],
        ];
    }
}
