<?php

namespace App\Services\Auth;

use App\Enums\StatutCompte;
use App\Models\Utilisateur;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class RenvoiOtpService
{
    public function __construct(
        private readonly OtpService $otpService
    ) {
    }

    public function renvoyer(string $telephone): void
    {
        $utilisateur = Utilisateur::query()
            ->where('telephone', $telephone)
            ->first();

        if ($utilisateur === null) {
            throw ValidationException::withMessages([
                'telephone' => [
                    'Aucun compte en attente de vérification pour ce numéro.',
                ],
            ]);
        }

        if ($utilisateur->statut_compte !== StatutCompte::AVerifier) {
            throw ValidationException::withMessages([
                'telephone' => [
                    'Ce compte ne peut pas recevoir un nouveau code OTP.',
                ],
            ]);
        }

        $code = $this->otpService->generer($telephone);

        if (app()->isLocal()) {
            Log::info('OTP renvoi vérification téléphone (local uniquement)', [
                'telephone' => $telephone,
                'code' => $code,
            ]);
        }
    }
}