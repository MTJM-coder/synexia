<?php

namespace Modules\Identity\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SetShopEmployeePermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'permission_id' => ['required', 'integer', 'exists:permissions,id'],
            // null = supprime la surcharge (retour au comportement du rôle)
            'is_granted' => ['nullable', 'boolean'],
        ];
    }
}
