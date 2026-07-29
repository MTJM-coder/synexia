<?php

namespace Modules\Identity\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    
    protected function prepareForValidation(): void
    {
        // Si 'email' est transmis à la place de 'login', on le bascule vers 'login'
        if ($this->has('email') && !$this->has('login')) {
            $this->merge([
                'login' => $this->input('email'),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ];
    }
}
