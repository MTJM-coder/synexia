<?php

namespace Modules\Catalog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * NOTE : accepte un `path` déjà résolu (chaîne), pas un fichier binaire —
 * cohérent avec ce qu'on a vu ailleurs dans le projet (avatar_path côté
 * Identity). Le vrai upload/stockage de fichier n'a pas encore de mécanisme
 * commun défini dans Synexia ; à standardiser plus tard si besoin.
 */
class StoreProductImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'path' => ['required', 'string', 'max:255'],
            'is_primary' => ['sometimes', 'boolean'],
        ];
    }
}
