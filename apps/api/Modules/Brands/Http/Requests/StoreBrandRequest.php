<?php

namespace Modules\Brands\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBrandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'shop_id' => ['nullable', 'integer', 'exists:shops,id'],
            'name' => ['required', 'string', 'max:150'],
            'logo_path' => ['nullable', 'string', 'max:255'],
        ];
    }
}
