<?php

namespace App\Http\Requests\Api\V1\Annonces;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ModifierAnnonceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_categorie' => [
                'sometimes',
                'integer',
                'min:1',
                Rule::exists('categories', 'id_categorie')->where('est_active', true),
            ],
            'titre' => ['sometimes', 'string', 'min:1', 'max:200'],
            'description' => ['sometimes', 'string', 'min:1'],
            'prix_base' => ['sometimes', 'numeric', 'gt:0', 'decimal:0,2'],
            'delai_livraison_jours' => ['sometimes', 'integer', 'min:1'],
            'nombre_revisions' => ['sometimes', 'integer', 'min:0'],
            'livrables_inclus' => ['sometimes', 'string', 'min:1'],
            'elements_requis_client' => ['sometimes', 'string', 'min:1'],
            'image_couverture' => ['nullable', 'string', 'max:500'],
            'mots_cles' => ['nullable', 'string'],
            'conditions_particulieres' => ['nullable', 'string'],
            'statut' => ['prohibited'],
            'id_prestataire' => ['prohibited'],
        ];
    }
}