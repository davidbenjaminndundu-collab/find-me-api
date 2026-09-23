<?php

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ReinitialisationMotDePasseRequest extends FormRequest
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
                'max:20',
            ],

            'code_otp' => [
                'required',
                'string',
                'size:6',
            ],

            'mot_de_passe' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'telephone.required' =>
                'Le numéro de téléphone est obligatoire.',

            'code_otp.required' =>
                'Le code de récupération est obligatoire.',

            'code_otp.size' =>
                'Le code de récupération doit contenir 6 chiffres.',

            'mot_de_passe.required' =>
                'Le nouveau mot de passe est obligatoire.',

            'mot_de_passe.min' =>
                'Le mot de passe doit contenir au moins 8 caractères.',

            'mot_de_passe.confirmed' =>
                'La confirmation du mot de passe ne correspond pas.',
        ];
    }
}