<?php

namespace Tests\Feature\Categories;

use App\Enums\RoleUtilisateur;
use App\Enums\StatutCompte;
use App\Models\Categorie;
use App\Models\Utilisateur;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreerCategorieTest extends TestCase
{
    use RefreshDatabase;

    private Utilisateur $admin;

    private Utilisateur $client;

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

        $this->client = Utilisateur::query()->create([
            'telephone' => '+243810000050',
            'mot_de_passe_hash' => Hash::make('password'),
            'nom' => 'Client',
            'prenom' => 'Test',
            'role' => RoleUtilisateur::Client,
            'statut_compte' => StatutCompte::Actif,
            'telephone_verifie_at' => now(),
        ]);
    }

    public function test_un_admin_peut_creer_une_categorie(): void
    {
        $response = $this->actingAs($this->admin)->postJson(
            '/api/v1/admin/categories',
            [
                'nom' => 'Développement web',
                'description' => 'Sites et applications',
                'icone' => 'code',
            ]
        );

        $response->assertCreated()
            ->assertJsonPath('message', 'Catégorie créée.')
            ->assertJsonPath('categorie.nom', 'Développement web')
            ->assertJsonPath('categorie.description', 'Sites et applications')
            ->assertJsonPath('categorie.icone', 'code')
            ->assertJsonPath('categorie.est_active', true);

        $this->assertDatabaseHas('categories', [
            'nom' => 'Développement web',
            'est_active' => true,
        ]);
    }

    public function test_est_active_peut_etre_defini_a_false(): void
    {
        $this->actingAs($this->admin)->postJson(
            '/api/v1/admin/categories',
            [
                'nom' => 'Brouillon catégorie',
                'est_active' => false,
            ]
        )->assertCreated()
            ->assertJsonPath('categorie.est_active', false);
    }

    public function test_un_visiteur_ne_peut_pas_creer_une_categorie(): void
    {
        $this->postJson('/api/v1/admin/categories', [
            'nom' => 'Graphisme',
        ])->assertUnauthorized();
    }

    public function test_un_client_ne_peut_pas_creer_une_categorie(): void
    {
        $this->actingAs($this->client)->postJson(
            '/api/v1/admin/categories',
            ['nom' => 'Graphisme']
        )->assertForbidden();
    }

    public function test_un_nom_manquant_est_refuse(): void
    {
        $this->actingAs($this->admin)->postJson(
            '/api/v1/admin/categories',
            ['description' => 'Sans nom']
        )->assertUnprocessable()
            ->assertJsonValidationErrors(['nom']);
    }

    public function test_un_nom_deja_utilise_est_refuse(): void
    {
        Categorie::query()->create([
            'nom' => 'Graphisme',
            'est_active' => true,
        ]);

        $this->actingAs($this->admin)->postJson(
            '/api/v1/admin/categories',
            ['nom' => 'Graphisme']
        )->assertUnprocessable()
            ->assertJsonValidationErrors(['nom']);
    }
}