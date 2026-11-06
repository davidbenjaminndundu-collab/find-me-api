<?php

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RenvoiOtpRequest extends FormRequest
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
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'telephone.required' =>
                'Le numéro de téléphone est obligatoire.',

            'telephone.regex' =>
                'Le numéro de téléphone est invalide.',
        ];
    }
}