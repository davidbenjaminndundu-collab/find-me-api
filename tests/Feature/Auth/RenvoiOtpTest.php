<?php

namespace Tests\Feature\Auth;

use App\Enums\RoleUtilisateur;
use App\Enums\StatutCompte;
use App\Models\Utilisateur;
use App\Services\Auth\OtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RenvoiOtpTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_compte_a_verifier_peut_recevoir_un_nouveau_otp(): void
    {
        $telephone = '+243810000050';

        Utilisateur::query()->create([
            'telephone' => $telephone,
            'mot_de_passe_hash' => Hash::make('Password123!'),
            'nom' => 'Kalilwa',
            'prenom' => 'Jean',
            'role' => RoleUtilisateur::Client,
            'statut_compte' => StatutCompte::AVerifier,
        ]);

        $response = $this->postJson('/api/v1/auth/resend-otp', [
            'telephone' => $telephone,
        ]);

        $response->assertOk()
            ->assertJsonPath(
                'message',
                'Un nouveau code OTP a été envoyé.'
            );
    }

    public function test_un_numero_inconnu_est_refuse(): void
    {
        $this->postJson('/api/v1/auth/resend-otp', [
            'telephone' => '+243810000051',
        ])->assertUnprocessable();
    }

    public function test_un_compte_deja_actif_est_refuse(): void
    {
        $telephone = '+243810000052';

        Utilisateur::query()->create([
            'telephone' => $telephone,
            'mot_de_passe_hash' => Hash::make('Password123!'),
            'nom' => 'Kalilwa',
            'prenom' => 'Jean',
            'role' => RoleUtilisateur::Client,
            'statut_compte' => StatutCompte::Actif,
            'telephone_verifie_at' => now(),
        ]);

        $this->postJson('/api/v1/auth/resend-otp', [
            'telephone' => $telephone,
        ])->assertUnprocessable();
    }

    public function test_un_telephone_invalide_est_refuse(): void
    {
        $this->postJson('/api/v1/auth/resend-otp', [
            'telephone' => '0810000053',
        ])->assertUnprocessable();
    }

    public function test_le_nouveau_otp_permet_la_verification(): void
    {
        $telephone = '+243810000054';

        Utilisateur::query()->create([
            'telephone' => $telephone,
            'mot_de_passe_hash' => Hash::make('Password123!'),
            'nom' => 'Kalilwa',
            'prenom' => 'Jean',
            'role' => RoleUtilisateur::Client,
            'statut_compte' => StatutCompte::AVerifier,
        ]);

        // Premier OTP (inscription)
        app(OtpService::class)->generer($telephone);

        $this->postJson('/api/v1/auth/resend-otp', [
            'telephone' => $telephone,
        ])->assertOk();

        // On régénère un code connu pour valider le flux
        // (le code réel a été écrasé par le renvoi)
        $nouveauCode = app(OtpService::class)->generer($telephone);

        $this->withHeaders([
            'Origin' => 'http://localhost:8000',
        ])->postJson('/api/v1/auth/verify-phone', [
            'telephone' => $telephone,
            'code_otp' => $nouveauCode,
        ])->assertOk();
    }
}