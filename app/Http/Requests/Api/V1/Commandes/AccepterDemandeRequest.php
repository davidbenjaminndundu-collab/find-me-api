<?php

namespace App\Http\Requests\Api\V1\Commandes;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AccepterDemandeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type_action' => [
                'required',
                'string',
                Rule::in(['accepter']),
            ],
        ];
    }
}
