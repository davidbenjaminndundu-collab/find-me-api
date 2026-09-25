<?php

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;

class DemandeRecuperationRequest extends FormRequest
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
        ];
    }

    public function messages(): array
    {
        return [
            'telephone.required' =>
                'Le numéro de téléphone est obligatoire.',

            'telephone.string' =>
                'Le numéro de téléphone est invalide.',

            'telephone.max' =>
                'Le numéro de téléphone est invalide.',
        ];
    }
}