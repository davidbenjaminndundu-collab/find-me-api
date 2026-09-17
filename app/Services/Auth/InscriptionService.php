<?php

namespace App\Services\Auth;

use App\Enums\StatutCompte;
use App\Models\Utilisateur;
use App\Http\Requests\Api\V1\Auth\InscriptionRequest;

class InscriptionService
{
    public function inscrire(InscriptionRequest $request): Utilisateur
    {
        $donnees = $request->validated();

        return Utilisateur::query()->create([
            'telephone' => $donnees['telephone'],
            'mot_de_passe_hash' => $donnees['mot_de_passe'],
            'nom' => $donnees['nom'],
            'prenom' => $donnees['prenom'],
            'postnom' => $donnees['postnom'] ?? null,
            'email' => $donnees['email'] ?? null,
            'role' => $donnees['role'],
            'statut_compte' => StatutCompte::AVerifier,
        ]);
    }
}