<?php

namespace Modules\Marketplace\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Volontairement absents ici : `slug` (évite de casser des liens existants
 * sans le vouloir), `status`/`subscription_plan_id` (endpoints dédiés,
 * avec leurs propres règles d'autorisation), `is_featured`/`verified_at`
 * (réservés à une action Super Admin, pas encore construite), `owner_id`
 * (transfert de propriété = décision séparée, plus sensible).
 */
class UpdateShopRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:150'],
            'slogan' => ['sometimes', 'nullable', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'email' => ['sometimes', 'nullable', 'email', 'max:190'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'whatsapp' => ['sometimes', 'nullable', 'string', 'max:30'],
            'website' => ['sometimes', 'nullable', 'url', 'max:255'],
            'social_links' => ['sometimes', 'nullable', 'array'],
            'address_line' => ['sometimes', 'nullable', 'string', 'max:255'],
            'city' => ['sometimes', 'nullable', 'string', 'max:100'],
            'region' => ['sometimes', 'nullable', 'string', 'max:100'],
            'country' => ['sometimes', 'nullable', 'string', 'max:100'],
            'latitude' => ['sometimes', 'nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['sometimes', 'nullable', 'numeric', 'between:-180,180'],
        ];
    }
}
