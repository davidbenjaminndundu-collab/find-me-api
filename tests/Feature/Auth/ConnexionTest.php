<?php

namespace Tests\Feature\Auth;

use App\Enums\RoleUtilisateur;
use App\Enums\StatutCompte;
use App\Models\Utilisateur;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ConnexionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withHeader('Origin', 'http://localhost:5173');
    }

    public function test_un_utilisateur_actif_peut_se_connecter(): void
    {
        $utilisateur = Utilisateur::query()->create([
            'telephone' => '+243812345678',
            'mot_de_passe_hash' => Hash::make('Password123!'),
            'nom' => 'Mbambi',
            'prenom' => 'Benjamin',
            'role' => RoleUtilisateur::Client,
            'statut_compte' => StatutCompte::Actif,
            'telephone_verifie_at' => now(),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'telephone' => '+243812345678',
            'mot_de_passe' => 'Password123!',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Connexion réussie.')
            ->assertJsonPath(
                'utilisateur.telephone',
                '+243812345678'
            );

        $this->assertAuthenticatedAs($utilisateur);

        $this->assertNotNull(
            $utilisateur->fresh()->derniere_connexion_at
        );
    }


    public function test_un_mauvais_mot_de_passe_est_refuse(): void
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

        $response = $this->postJson('/api/v1/auth/login', [
            'telephone' => '+243812345678',
            'mot_de_passe' => 'MauvaisPassword',
        ]);

        $response->assertUnauthorized();

        $this->assertGuest();
    }

    public function test_un_telephone_inexistant_est_refuse(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'telephone' => '+243899999999',
            'mot_de_passe' => 'Password123!',
        ]);

        $response->assertUnauthorized();

        $this->assertGuest();
    }

    public function test_un_compte_suspendu_ne_peut_pas_se_connecter(): void
    {
        Utilisateur::query()->create([
            'telephone' => '+243812345678',
            'mot_de_passe_hash' => Hash::make('Password123!'),
            'nom' => 'Mbambi',
            'prenom' => 'Benjamin',
            'role' => RoleUtilisateur::Client,
            'statut_compte' => StatutCompte::Suspendu,
            'telephone_verifie_at' => now(),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'telephone' => '+243812345678',
            'mot_de_passe' => 'Password123!',
        ]);

        $response->assertForbidden();

        $this->assertGuest();
    }

    public function test_un_compte_non_verifie_ne_peut_pas_se_connecter(): void
    {
        Utilisateur::query()->create([
            'telephone' => '+243812345678',
            'mot_de_passe_hash' => Hash::make('Password123!'),
            'nom' => 'Mbambi',
            'prenom' => 'Benjamin',
            'role' => RoleUtilisateur::Client,
            'statut_compte' => StatutCompte::AVerifier,
            'telephone_verifie_at' => null,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'telephone' => '+243812345678',
            'mot_de_passe' => 'Password123!',
        ]);

        $response->assertForbidden();

        $this->assertGuest();
    }

   
}