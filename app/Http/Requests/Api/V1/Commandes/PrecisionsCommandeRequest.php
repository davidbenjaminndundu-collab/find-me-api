<?php

namespace App\Http\Requests\Api\V1\Commandes;

use Illuminate\Foundation\Http\FormRequest;

class PrecisionsCommandeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'description' => [
                'required',
                'string',
                'min:3',
                'max:5000',
            ],
        ];
    }
}