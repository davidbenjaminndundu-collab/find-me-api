<?php

namespace Tests\Feature\Auth;

use App\Enums\RoleUtilisateur;
use App\Enums\StatutCompte;
use App\Models\Utilisateur;
use App\Services\Auth\OtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RecuperationCompteTest extends TestCase
{
    use RefreshDatabase;

    private OtpService $otpService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->otpService = app(OtpService::class);
    }

    public function test_un_utilisateur_peut_demander_un_code_de_recuperation(): void
    {
        Utilisateur::query()->create([
            'telephone' => '+243812345678',
            'mot_de_passe_hash' => Hash::make('AncienPassword123!'),
            'nom' => 'Mbambi',
            'prenom' => 'Benjamin',
            'role' => RoleUtilisateur::Client,
            'statut_compte' => StatutCompte::Actif,
            'telephone_verifie_at' => now(),
        ]);

        $response = $this->postJson(
            '/api/v1/auth/password/forgot',
            [
                'telephone' => '+243812345678',
            ]
        );

        $response->assertOk();

        $response->assertJsonPath(
            'message',
            'Si ce numéro correspond à un compte, un code de récupération a été envoyé.'
        );
    }

    public function test_un_code_valide_permet_de_reinitialiser_le_mot_de_passe(): void
    {
        $utilisateur = Utilisateur::query()->create([
            'telephone' => '+243812345678',
            'mot_de_passe_hash' => Hash::make('AncienPassword123!'),
            'nom' => 'Mbambi',
            'prenom' => 'Benjamin',
            'role' => RoleUtilisateur::Client,
            'statut_compte' => StatutCompte::Actif,
            'telephone_verifie_at' => now(),
        ]);

        $code = $this->otpService->generer(
            $utilisateur->telephone,
            'recuperation_compte'
        );

        $response = $this->postJson(
            '/api/v1/auth/password/reset',
            [
                'telephone' => $utilisateur->telephone,
                'code_otp' => $code,
                'mot_de_passe' => 'NouveauPassword123!',
                'mot_de_passe_confirmation' => 'NouveauPassword123!',
            ]
        );

        $response->assertOk();

        $response->assertJsonPath(
            'message',
            'Mot de passe réinitialisé avec succès. Vous pouvez maintenant vous connecter.'
        );

        $utilisateur->refresh();

        $this->assertTrue(
            Hash::check(
                'NouveauPassword123!',
                $utilisateur->mot_de_passe_hash
            )
        );
    }

    public function test_un_code_invalide_est_refuse(): void
    {
        Utilisateur::query()->create([
            'telephone' => '+243812345678',
            'mot_de_passe_hash' => Hash::make('AncienPassword123!'),
            'nom' => 'Mbambi',
            'prenom' => 'Benjamin',
            'role' => RoleUtilisateur::Client,
            'statut_compte' => StatutCompte::Actif,
            'telephone_verifie_at' => now(),
        ]);

        $response = $this->postJson(
            '/api/v1/auth/password/reset',
            [
                'telephone' => '+243812345678',
                'code_otp' => '000000',
                'mot_de_passe' => 'NouveauPassword123!',
                'mot_de_passe_confirmation' => 'NouveauPassword123!',
            ]
        );

        $response->assertUnprocessable();

        $response->assertJsonValidationErrors([
            'code_otp',
        ]);
    }

    public function test_un_code_ne_peut_pas_etre_reutilise(): void
    {
        $utilisateur = Utilisateur::query()->create([
            'telephone' => '+243812345678',
            'mot_de_passe_hash' => Hash::make('AncienPassword123!'),
            'nom' => 'Mbambi',
            'prenom' => 'Benjamin',
            'role' => RoleUtilisateur::Client,
            'statut_compte' => StatutCompte::Actif,
            'telephone_verifie_at' => now(),
        ]);

        $code = $this->otpService->generer(
            $utilisateur->telephone,
            'recuperation_compte'
        );

        $payload = [
            'telephone' => $utilisateur->telephone,
            'code_otp' => $code,
            'mot_de_passe' => 'NouveauPassword123!',
            'mot_de_passe_confirmation' => 'NouveauPassword123!',
        ];

        $this->postJson(
            '/api/v1/auth/password/reset',
            $payload
        )->assertOk();

        $this->postJson(
            '/api/v1/auth/password/reset',
            $payload
        )->assertUnprocessable();
    }

    public function test_le_nouveau_mot_de_passe_permet_la_connexion(): void
    {
        $utilisateur = Utilisateur::query()->create([
            'telephone' => '+243812345678',
            'mot_de_passe_hash' => Hash::make('AncienPassword123!'),
            'nom' => 'Mbambi',
            'prenom' => 'Benjamin',
            'role' => RoleUtilisateur::Client,
            'statut_compte' => StatutCompte::Actif,
            'telephone_verifie_at' => now(),
        ]);

        $code = $this->otpService->generer(
            $utilisateur->telephone,
            'recuperation_compte'
        );

        $this->postJson(
            '/api/v1/auth/password/reset',
            [
                'telephone' => $utilisateur->telephone,
                'code_otp' => $code,
                'mot_de_passe' => 'NouveauPassword123!',
                'mot_de_passe_confirmation' => 'NouveauPassword123!',
            ]
        )->assertOk();

        $response = $this
            ->withHeader('Origin', 'http://localhost:5173')
            ->postJson('/api/v1/auth/login', [
                'telephone' => $utilisateur->telephone,
                'mot_de_passe' => 'NouveauPassword123!',
            ]);

        $response->assertOk();
    }
}