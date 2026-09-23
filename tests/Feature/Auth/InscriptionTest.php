<?php

namespace Tests\Feature\Auth;

use App\Enums\RoleUtilisateur;
use App\Enums\StatutCompte;
use App\Models\Utilisateur;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_visiteur_peut_creer_un_compte(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'telephone' => '+243974657633',
            'mot_de_passe' => 'Postman123!"',
            'nom' => 'Kalilwa',
            'prenom' => 'Jean',
            'role' => 'client',
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['message', 'id_utilisateur']);

        $this->assertDatabaseHas('utilisateurs', [
            'telephone' => '+243974657633',
            'nom' => 'Kalilwa',
            'prenom' => 'Jean',
        ]);

        $utilisateur = Utilisateur::query()->where('telephone', '+243974657633')->first();

        $this->assertSame(RoleUtilisateur::Client, $utilisateur->role);
        $this->assertSame(StatutCompte::AVerifier, $utilisateur->statut_compte);
        $this->assertNotEquals('Postman123!', $utilisateur->mot_de_passe_hash);
    }

    public function test_le_role_administrateur_est_refuse(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'telephone' => '+243810000011',
            'mot_de_passe' => 'SecurePass123!',
            'nom' => 'Admin',
            'prenom' => 'Faux',
            'role' => 'administrateur',
        ])->assertUnprocessable();
    }

    public function test_un_telephone_invalide_est_refuse(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'telephone' => '0974657633',
            'mot_de_passe' => 'Postman123!"',
            'nom' => 'Kalilwa',
            'prenom' => 'Jean',
            'role' => 'client',
        ])->assertUnprocessable();
    }
}