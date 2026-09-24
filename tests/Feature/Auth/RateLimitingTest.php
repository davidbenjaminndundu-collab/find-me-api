<?php

namespace Tests\Feature\Auth;

use App\Enums\RoleUtilisateur;
use App\Enums\StatutCompte;
use App\Models\Utilisateur;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RateLimitingTest extends TestCase
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

    public function test_trop_de_tentatives_de_connexion_sont_limitees(): void
    {
        Utilisateur::query()->create([
            'telephone' => '+243812345678',
            'mot_de_passe_hash' => Hash::make('Password123!'),
            'nom' => 'Mbambi',
            'prenom' => 'Benjamin',
            'role' => RoleUtilisateur::Client,
            'statut_compte' => StatutCompte::Actif,
            'telephone_verifie_at' => now(),
        ]);

        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson(
                '/api/v1/auth/login',
                [
                    'telephone' => '+243812345678',
                    'mot_de_passe' => 'MauvaisPassword',
                ]
            );

            $response->assertUnauthorized();
        }

        $response = $this->postJson(
            '/api/v1/auth/login',
            [
                'telephone' => '+243812345678',
                'mot_de_passe' => 'MauvaisPassword',
            ]
        );

        $response->assertStatus(429);
    }

    public function test_la_limitation_d_un_numero_ne_bloque_pas_un_autre_numero(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson(
                '/api/v1/auth/login',
                [
                    'telephone' => '+243811111111',
                    'mot_de_passe' => 'MauvaisPassword',
                ]
            );
        }

        $response = $this->postJson(
            '/api/v1/auth/login',
            [
                'telephone' => '+243822222222',
                'mot_de_passe' => 'MauvaisPassword',
            ]
        );

        /*
        * Le numéro n'existe pas, donc A04 retourne 401.
        *
        * L'important ici est qu'il ne retourne PAS 429.
        */
        $response->assertUnauthorized();
    }

    public function test_trop_de_demandes_de_code_de_recuperation_sont_limitees(): void
    {
        $payload = [
            'telephone' => '+243812345678',
        ];

        for ($i = 0; $i < 3; $i++) {
            $response = $this->postJson(
                '/api/v1/auth/password/forgot',
                $payload
            );

            $response->assertOk();
        }

        $response = $this->postJson(
            '/api/v1/auth/password/forgot',
            $payload
        );

        $response->assertStatus(429);
    }


    public function test_trop_de_codes_otp_invalides_sont_limites(): void
    {
        Utilisateur::query()->create([
            'telephone' => '+243812345678',
            'mot_de_passe_hash' => Hash::make('Password123!'),
            'nom' => 'Mbambi',
            'prenom' => 'Benjamin',
            'role' => RoleUtilisateur::Client,
            'statut_compte' => StatutCompte::Actif,
            'telephone_verifie_at' => now(),
        ]);

        $payload = [
            'telephone' => '+243812345678',
            'code_otp' => '000000',
            'mot_de_passe' => 'NouveauPassword123!',
            'mot_de_passe_confirmation' => 'NouveauPassword123!',
        ];

        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson(
                '/api/v1/auth/password/reset',
                $payload
            );

            $response->assertUnprocessable();
        }

        $response = $this->postJson(
            '/api/v1/auth/password/reset',
            $payload
        );

        $response->assertStatus(429);
    }
}