<?php

namespace Modules\Categories\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $categoryId = $this->route('category')?->id;

        return [
            'parent_id' => [
                'sometimes', 'nullable', 'integer', 'exists:categories,id',
                // Une catégorie ne peut pas être son propre parent direct.
                // NOTE : ne détecte pas les cycles indirects (A parent de B,
                // B parent de A) — acceptable pour un module simple, à revoir
                // si ça devient un vrai problème en pratique.
                Rule::notIn([$categoryId]),
            ],
            'name' => ['sometimes', 'string', 'max:150'],
            'icon_path' => ['sometimes', 'nullable', 'string', 'max:255'],
            'image_path' => ['sometimes', 'nullable', 'string', 'max:255'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
