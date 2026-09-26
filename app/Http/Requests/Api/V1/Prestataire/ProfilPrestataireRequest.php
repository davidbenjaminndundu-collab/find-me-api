<?php

namespace App\Http\Requests\Api\V1\Prestataire;

use App\Enums\StatutDisponibilite;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfilPrestataireRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nom_professionnel' => [
                'required',
                'string',
                'max:150',
            ],

            'bio' => [
                'required',
                'string',
                'max:3000',
            ],

            'competences' => [
                'required',
                'string',
                'max:2000',
            ],

            'portfolio_url' => [
                'nullable',
                'url',
                'max:500',
            ],

            'statut_disponibilite' => [
                'required',
                Rule::enum(StatutDisponibilite::class),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nom_professionnel.required' =>
                'Le nom professionnel est obligatoire.',

            'nom_professionnel.max' =>
                'Le nom professionnel ne peut pas dépasser 150 caractères.',

            'bio.required' =>
                'La biographie professionnelle est obligatoire.',

            'competences.required' =>
                'Les compétences sont obligatoires.',

            'portfolio_url.url' =>
                'Le lien du portfolio doit être une URL valide.',

            'statut_disponibilite.required' =>
                'Le statut de disponibilité est obligatoire.',
        ];
    }
}