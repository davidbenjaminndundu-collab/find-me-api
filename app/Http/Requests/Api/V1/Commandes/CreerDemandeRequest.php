<?php

namespace App\Http\Requests\Api\V1\Commandes;

use Illuminate\Foundation\Http\FormRequest;

class CreerDemandeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_annonce' => [
                'required',
                'integer',
                'min:1',
            ],

            'description_commande' => [
                'required',
                'string',
                'min:10',
                'max:5000',
            ],

            // Ces champs sont contrôlés par le backend.
            'id_client' => ['prohibited'],
            'id_prestataire' => ['prohibited'],
            'reference_commande' => ['prohibited'],
            'titre_service' => ['prohibited'],
            'montant_total' => ['prohibited'],
            'taux_commission' => ['prohibited'],
            'montant_commission' => ['prohibited'],
            'montant_prestataire' => ['prohibited'],
            'delai_livraison_jours' => ['prohibited'],
            'nombre_revisions_incluses' => ['prohibited'],
            'nombre_revisions_utilisees' => ['prohibited'],
            'livrables_convenus' => ['prohibited'],
            'statut' => ['prohibited'],
            'est_offre_personnalisee' => ['prohibited'],
        ];
    }
}