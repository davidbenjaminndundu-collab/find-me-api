<?php

namespace App\Services\Auth;

use App\Enums\StatutCompte;
use App\Http\Requests\Api\V1\Auth\InscriptionRequest;
use App\Models\Utilisateur;
use Illuminate\Support\Facades\Log;

class InscriptionService
{
    public function __construct(
        private OtpService $otp
    ) {}

    public function inscrire(InscriptionRequest $request): Utilisateur
    {
        $donnees = $request->validated();

        $utilisateur = Utilisateur::query()->create([
            'telephone' => $donnees['telephone'],
            'mot_de_passe_hash' => $donnees['mot_de_passe'],
            'nom' => $donnees['nom'],
            'prenom' => $donnees['prenom'],
            'postnom' => $donnees['postnom'] ?? null,
            'email' => $donnees['email'] ?? null,
            'role' => $donnees['role'],
            'statut_compte' => StatutCompte::AVerifier,
        ]);

        $code = $this->otp->generer($utilisateur->telephone);

        if (app()->isLocal()) {
            Log::info('OTP inscription (local uniquement)', [
                'telephone' => $utilisateur->telephone,
                'code' => $code,
            ]);
        }

        return $utilisateur;
    }
}