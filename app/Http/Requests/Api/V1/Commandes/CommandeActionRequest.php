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
                    'demander_precisions',
                ]),
            ],

            'motif' => [
                 Rule::requiredIf(
                    fn () => in_array(
                        $this->input('type_action'),
                        [
                            'refuser',
                            'demander_precisions',
                        ],
                        true
                    )
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
                'Le motif est obligatoire pour cette action.',
        ];
    }
}