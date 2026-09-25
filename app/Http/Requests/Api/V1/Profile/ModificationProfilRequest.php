<?php

namespace App\Http\Requests\Api\V1\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ModificationProfilRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $utilisateur = $this->user();

        return [
            'nom' => [
                'sometimes',
                'required',
                'string',
                'max:100',
            ],

            'prenom' => [
                'sometimes',
                'required',
                'string',
                'max:100',
            ],

            'postnom' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
            ],

            'email' => [
                'sometimes',
                'nullable',
                'email',
                'max:255',
                Rule::unique('utilisateurs', 'email')
                    ->ignore(
                        $utilisateur?->id_utilisateur,
                        'id_utilisateur'
                    ),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nom.required' => 'Le nom est obligatoire.',
            'nom.max' => 'Le nom ne peut pas dépasser 100 caractères.',

            'prenom.required' => 'Le prénom est obligatoire.',
            'prenom.max' => 'Le prénom ne peut pas dépasser 100 caractères.',

            'postnom.max' => 'Le postnom ne peut pas dépasser 100 caractères.',

            'email.email' => 'L’adresse électronique est invalide.',
            'email.unique' => 'Cette adresse électronique est déjà utilisée.',
        ];
    }
}