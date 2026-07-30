<?php

namespace Modules\Marketplace\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Marketplace\Models\Shop;

class ChangeShopStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:'.implode(',', [
                Shop::STATUS_PENDING,
                Shop::STATUS_ACTIVE,
                Shop::STATUS_SUSPENDED,
                Shop::STATUS_CLOSED,
            ])],
        ];
    }
}
