<?php

namespace App\Http\Requests\Api\V1\Annonces;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RechercheAnnonceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:200'],
            'id_categorie' => [
                'nullable',
                'integer',
                'min:1',
                Rule::exists('categories', 'id_categorie'),
            ],
            'prix_min' => [
                'nullable',
                'numeric',
                'min:0',
                'decimal:0,2',
            ],
            'prix_max' => [
                'nullable',
                'numeric',
                'min:0',
                'decimal:0,2',
                Rule::when(
                    $this->filled('prix_min'),
                    ['gte:prix_min']
                ),
            ],
            'delai_max' => [
                'nullable',
                'integer',
                'min:1',
            ],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'offset' => ['nullable', 'integer', 'min:0'],
        ];
    }
}