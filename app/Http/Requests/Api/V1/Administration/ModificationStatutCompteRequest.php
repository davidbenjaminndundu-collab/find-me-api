<?php

namespace App\Http\Requests\Api\V1\Administration;

use App\Enums\StatutCompte;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ModificationStatutCompteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'statut_compte' => [
                'required',
                'string',
                Rule::in([
                    StatutCompte::Actif->value,
                    StatutCompte::Suspendu->value,
                    StatutCompte::Bloque->value,
                    StatutCompte::Ferme->value,
                ]),
            ],
            'motif' => ['nullable', 'string'],
        ];
    }
}