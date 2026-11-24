<?php

namespace App\Http\Requests\Api\V1\Categories;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreerCategorieRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nom' => [
                'required',
                'string',
                'min:1',
                'max:150',
                Rule::unique('categories', 'nom'),
            ],
            'description' => ['nullable', 'string'],
            'icone' => ['nullable', 'string', 'max:500'],
            'est_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nom.required' => 'Le nom de la catégorie est obligatoire.',
            'nom.unique' => 'Une catégorie avec ce nom existe déjà.',
            'nom.max' => 'Le nom de la catégorie ne peut pas dépasser 150 caractères.',
            'icone.max' => 'L’icône ne peut pas dépasser 500 caractères.',
        ];
    }
}