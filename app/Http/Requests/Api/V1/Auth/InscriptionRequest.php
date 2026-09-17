<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Enums\RoleUtilisateur;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'telephone' => [
                'required',
                'string',
                'regex:/^\+243[0-9]{9}$/',
                'unique:utilisateurs,telephone',
            ],
            'mot_de_passe' => ['required', 'string', 'min:8'],
            'nom' => ['required', 'string', 'max:100'],
            'prenom' => ['required', 'string', 'max:100'],
            'postnom' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'unique:utilisateurs,email'],
            'role' => [
                'required',
                'string',
                Rule::in([
                    RoleUtilisateur::Client->value,
                    RoleUtilisateur::Prestataire->value,
                ]),
            ],
        ];
    }
}