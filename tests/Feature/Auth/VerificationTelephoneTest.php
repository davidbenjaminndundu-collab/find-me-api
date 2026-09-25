<?php

namespace Tests\Feature\Auth;

use App\Enums\RoleUtilisateur;
use App\Enums\StatutCompte;
use App\Models\Utilisateur;
use App\Services\Auth\OtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VerificationTelephoneTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'sanctum.stateful' => [
                'localhost',
                'localhost:5173',
                '127.0.0.1',
                '127.0.0.1:5173',
            ],
        ]);

        $this->withHeader(
            'Origin',
            'http://localhost:5173'
        );
    }

    public function test_un_code_valide_active_le_compte(): void
    {
        $telephone = '+243810000040';

        Utilisateur::query()->create([
            'telephone' => $telephone,
            'mot_de_passe_hash' => 'password',
            'nom' => 'Kalilwa',
            'prenom' => 'Jean',
            'role' => RoleUtilisateur::Client,
            'statut_compte' => StatutCompte::AVerifier,
        ]);

        $code = app(OtpService::class)->generer($telephone);

        $response = $this->withHeaders([
            'Origin' => 'http://localhost:8000',
        ])->postJson('/api/v1/auth/verify-phone', [
            'telephone' => $telephone,
            'code_otp' => $code,
        ]);

        $response->assertOk()
            ->assertJsonPath('utilisateur.statut_compte', 'actif');

        $this->assertAuthenticated();
        $this->assertDatabaseHas('utilisateurs', [
            'telephone' => $telephone,
            'statut_compte' => StatutCompte::Actif->value,
        ]);
    }

    public function test_un_code_invalide_est_refuse(): void
    {
        $telephone = '+243810000041';

        Utilisateur::query()->create([
            'telephone' => $telephone,
            'mot_de_passe_hash' => 'password',
            'nom' => 'Kalilwa',
            'prenom' => 'Jean',
            'role' => RoleUtilisateur::Client,
            'statut_compte' => StatutCompte::AVerifier,
        ]);

        app(OtpService::class)->generer($telephone);

        $this->withHeaders([
            'Origin' => 'http://localhost:8000',
        ])->postJson('/api/v1/auth/verify-phone', [
            'telephone' => $telephone,
            'code_otp' => '000000',
        ])->assertUnprocessable();

        $this->assertDatabaseHas('utilisateurs', [
            'telephone' => $telephone,
            'statut_compte' => StatutCompte::AVerifier->value,
        ]);
    }
}