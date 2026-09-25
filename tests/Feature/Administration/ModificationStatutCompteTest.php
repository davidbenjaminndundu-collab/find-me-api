<?php

namespace Tests\Feature\Administration;

use App\Enums\RoleUtilisateur;
use App\Enums\StatutCompte;
use App\Models\Utilisateur;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ModificationStatutCompteTest extends TestCase
{
    use RefreshDatabase;

    private Utilisateur $admin;

    private Utilisateur $cible;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withHeader('Origin', 'http://localhost:8000');

        $this->admin = Utilisateur::query()->create([
            'telephone' => '+243900000010',
            'mot_de_passe_hash' => Hash::make('password'),
            'nom' => 'Admin',
            'prenom' => 'Test',
            'role' => RoleUtilisateur::Administrateur,
            'statut_compte' => StatutCompte::Actif,
            'telephone_verifie_at' => now(),
        ]);

        $this->cible = Utilisateur::query()->create([
            'telephone' => '+243810000050',
            'mot_de_passe_hash' => Hash::make('password'),
            'nom' => 'Client',
            'prenom' => 'Cible',
            'role' => RoleUtilisateur::Client,
            'statut_compte' => StatutCompte::Actif,
            'telephone_verifie_at' => now(),
        ]);
    }

    public function test_un_admin_peut_suspendre_un_autre_compte(): void
    {
        $response = $this->actingAs($this->admin)->patchJson(
            '/api/v1/users/'.$this->cible->id_utilisateur,
            ['statut_compte' => 'suspendu']
        );

        $response->assertOk()
            ->assertJsonPath('utilisateur.statut_compte', 'suspendu');

        $this->assertDatabaseHas('utilisateurs', [
            'id_utilisateur' => $this->cible->id_utilisateur,
            'statut_compte' => StatutCompte::Suspendu->value,
        ]);
    }

    public function test_un_visiteur_ne_peut_pas_modifier_un_statut(): void
    {
        $this->patchJson(
            '/api/v1/users/'.$this->cible->id_utilisateur,
            ['statut_compte' => 'suspendu']
        )->assertUnauthorized();
    }

    public function test_un_client_ne_peut_pas_modifier_un_statut(): void
    {
        $this->actingAs($this->cible)->patchJson(
            '/api/v1/users/'.$this->cible->id_utilisateur,
            ['statut_compte' => 'bloque']
        )->assertForbidden();
    }

    public function test_un_admin_ne_peut_pas_modifier_son_propre_statut(): void
    {
        $this->actingAs($this->admin)->patchJson(
            '/api/v1/users/'.$this->admin->id_utilisateur,
            ['statut_compte' => 'suspendu']
        )->assertForbidden();
    }

    public function test_un_id_inconnu_renvoie_404(): void
    {
        $this->actingAs($this->admin)->patchJson(
            '/api/v1/users/999999',
            ['statut_compte' => 'suspendu']
        )->assertNotFound();
    }

    public function test_un_statut_invalide_est_refuse(): void
    {
        $this->actingAs($this->admin)->patchJson(
            '/api/v1/users/'.$this->cible->id_utilisateur,
            ['statut_compte' => 'brouillon']
        )->assertUnprocessable();
    }
}