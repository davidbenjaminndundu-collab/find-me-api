<?php

namespace App\Services\Auth;

use App\Enums\StatutCompte;
use App\Http\Requests\Api\V1\Auth\VerificationTelephoneRequest;
use App\Models\Utilisateur;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class VerificationTelephoneService
{
    public function __construct(
        private OtpService $otp
    ) {}

    public function verifier(VerificationTelephoneRequest $request): Utilisateur
    {
        $donnees = $request->validated();
        $telephone = $donnees['telephone'];
        $code = $donnees['code_otp'];

        if (! $this->otp->estValide($telephone, $code)) {
            throw ValidationException::withMessages([
                'code_otp' => ['Code OTP invalide ou expire.'],
            ]);
        }

        $utilisateur = Utilisateur::query()
            ->where('telephone', $telephone)
            ->firstOrFail();

        if ($utilisateur->statut_compte !== StatutCompte::AVerifier) {
            throw ValidationException::withMessages([
                'telephone' => ['Ce compte ne peut pas etre active.'],
            ]);
        }

        $utilisateur->update([
            'statut_compte' => StatutCompte::Actif,
            'telephone_verifie_at' => now(),
        ]);

        $this->otp->supprimer($telephone);

        Auth::login($utilisateur);
        $request->session()->regenerate();

        return $utilisateur->fresh();
    }
}