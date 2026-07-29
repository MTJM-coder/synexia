<?php

namespace Modules\Identity\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InviteEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        // TODO : brancher sur la permission "employees.manage" une fois les
        // conventions d'autorisation par module tranchées (voir étape C du
        // briefing — bloque tous les Http/Controllers, pas propre à Identity).
        return true;
    }

    public function rules(): array
    {
        return [
            'shop_id' => ['required', 'integer'],
            'email' => ['required', 'email'],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
        ];
    }
}
