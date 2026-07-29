<?php

namespace Modules\Identity\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AcceptInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            // Requis uniquement si l'appelant n'est pas authentifié ET
            // qu'aucun compte n'existe déjà pour l'email de l'invitation —
            // cette double condition ne peut pas être exprimée en règle de
            // validation pure, elle est vérifiée dans AcceptShopInvitationAction.
            'first_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ];
    }
}
