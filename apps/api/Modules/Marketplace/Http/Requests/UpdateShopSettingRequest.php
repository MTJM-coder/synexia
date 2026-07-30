<?php

namespace Modules\Marketplace\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateShopSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'currency' => ['sometimes', 'string', 'size:3'],
            'language' => ['sometimes', 'string', 'max:10'],
            'timezone' => ['sometimes', 'string', 'max:60'],
            'tax_rate' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'tax_inclusive' => ['sometimes', 'boolean'],
            'allow_pickup' => ['sometimes', 'boolean'],
            'allow_delivery' => ['sometimes', 'boolean'],
            'delivery_radius_km' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'opening_hours' => ['sometimes', 'array'],
        ];
    }
}
