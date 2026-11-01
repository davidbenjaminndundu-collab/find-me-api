<?php

namespace App\Http\Requests\Api\V1\Commandes;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CommandeActionRequest extends FormRequest
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
                Rule::in([
                    'accepter',
                    'refuser',
                ]),
            ],

            'motif' => [
                Rule::requiredIf(
                    fn () => $this->input('type_action') === 'refuser'
                ),
                'nullable',
                'string',
                'min:3',
                'max:2000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'type_action.required' =>
                'Le type d’action est obligatoire.',

            'type_action.in' =>
                'Cette action n’est pas prise en charge.',

            'motif.required' =>
                'Le motif du refus est obligatoire.',
        ];
    }
}