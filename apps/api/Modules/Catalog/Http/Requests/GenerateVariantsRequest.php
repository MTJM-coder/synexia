<?php

namespace Modules\Catalog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateVariantsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Ex: {"value_ids_by_type": [[1,2], [5,6,7]]} = un sous-tableau
            // par type d'attribut (Couleur: [Noir,Blanc], Taille: [S,M,L]).
            'value_ids_by_type' => ['required', 'array', 'min:1'],
            'value_ids_by_type.*' => ['array', 'min:1'],
            'value_ids_by_type.*.*' => ['integer', 'exists:attribute_values,id'],
        ];
    }
}
