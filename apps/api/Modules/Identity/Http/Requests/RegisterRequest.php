<?php

namespace Modules\Identity\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'required_without:phone', 'email', 'max:190', 'unique:users,email'],
            'phone' => ['nullable', 'required_without:email', 'string', 'max:30', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'locale' => ['nullable', 'string', 'in:fr,en'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required_without' => 'Un email ou un numéro de téléphone est requis.',
            'phone.required_without' => 'Un email ou un numéro de téléphone est requis.',
        ];
    }
}
