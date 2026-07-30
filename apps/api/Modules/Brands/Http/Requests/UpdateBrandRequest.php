<?php

namespace Modules\Brands\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBrandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:150'],
            'logo_path' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
