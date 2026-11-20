<?php

namespace App\Http\Requests\Api\V1\Paiements;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InitierPaiementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'cle_idempotence' => $this->header('Idempotency-Key'),
        ]);
    }

    public function rules(): array
    {
        return [
            'cle_idempotence' => [
                'required',
                'uuid',
            ],
            'operateur' => [
                'required',
                'string',
                Rule::in([
                    'mpesa',
                    'airtel_money',
                    'orange_money',
                ]),
            ],
            'numero_payeur' => [
                'required',
                'string',
                'regex:/^\+243[0-9]{9}$/',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'cle_idempotence.required' =>
                'La clé d’idempotence est obligatoire.',
            'cle_idempotence.uuid' =>
                'La clé d’idempotence est invalide.',
            'operateur.required' =>
                'L’opérateur est obligatoire.',
            'operateur.in' =>
                'L’opérateur n’est pas pris en charge.',
            'numero_payeur.required' =>
                'Le numéro du payeur est obligatoire.',
            'numero_payeur.regex' =>
                'Le numéro du payeur est invalide.',
        ];
    }
}