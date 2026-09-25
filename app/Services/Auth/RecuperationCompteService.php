<?php

namespace App\Services\Auth;

use App\Enums\StatutCompte;
use App\Models\Utilisateur;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class RecuperationCompteService
{
    private const CONTEXTE_OTP = 'recuperation_compte';

    public function __construct(
        private readonly OtpService $otpService
    ) {
    }

    public function demanderCode(string $telephone): void
    {
        $utilisateur = Utilisateur::query()
            ->where('telephone', $telephone)
            ->first();

        /*
         * On ne révèle pas si le numéro existe ou non.
         * Cela évite l'énumération des comptes.
         */
        if (! $utilisateur) {
            return;
        }

        if (
            in_array(
                $utilisateur->statut_compte,
                [
                    StatutCompte::Bloque,
                    StatutCompte::Ferme,
                ],
                true
            )
        ) {
            return;
        }

        $code = $this->otpService->generer(
            $telephone,
            self::CONTEXTE_OTP
        );

        /*
         * Pour le développement local seulement.
         * Plus tard : service SMS.
         */
        if (app()->isLocal()) {
            Log::info('OTP récupération de compte (local uniquement)', [
                'telephone' => $telephone,
                'code' => $code,
            ]);
        }
    }

    public function reinitialiserMotDePasse(
        string $telephone,
        string $code,
        string $nouveauMotDePasse
    ): void {
        $utilisateur = Utilisateur::query()
            ->where('telephone', $telephone)
            ->first();

        if (! $utilisateur) {
            throw ValidationException::withMessages([
                'code_otp' => [
                    'Code de récupération invalide ou expiré.',
                ],
            ]);
        }

        if (
            in_array(
                $utilisateur->statut_compte,
                [
                    StatutCompte::Bloque,
                    StatutCompte::Ferme,
                ],
                true
            )
        ) {
            throw ValidationException::withMessages([
                'code_otp' => [
                    'La récupération de ce compte est impossible.',
                ],
            ]);
        }

        $codeValide = $this->otpService->estValide(
            $telephone,
            $code,
            self::CONTEXTE_OTP
        );

        if (! $codeValide) {
            throw ValidationException::withMessages([
                'code_otp' => [
                    'Code de récupération invalide ou expiré.',
                ],
            ]);
        }

        $utilisateur->update([
            'mot_de_passe_hash' => $nouveauMotDePasse,
        ]);

        $this->otpService->supprimer(
            $telephone,
            self::CONTEXTE_OTP
        );
    }
}