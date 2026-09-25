<?php

namespace Tests\Feature\Profile;

use App\Enums\RoleUtilisateur;
use App\Enums\StatutCompte;
use App\Models\Utilisateur;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ModificationProfilTest extends TestCase
{
    use RefreshDatabase;

    private Utilisateur $utilisateur;

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


        $this->utilisateur = Utilisateur::query()->create([
            'telephone' => '+243812345678',
            'mot_de_passe_hash' => Hash::make('Password123!'),
            'nom' => 'AncienNom',
            'prenom' => 'AncienPrenom',
            'postnom' => null,
            'email' => 'ancien@example.com',
            'role' => RoleUtilisateur::Client,
            'statut_compte' => StatutCompte::Actif,
            'telephone_verifie_at' => now(),
        ]);
    }

    public function test_un_utilisateur_authentifie_peut_modifier_son_profil(): void
    {
       $this->actingAs($this->utilisateur, 'web');

        $response = $this->patchJson(
            '/api/v1/profile',
            [
                'nom' => 'Mbambi',
                'prenom' => 'Benjamin',
                'postnom' => 'NdNdu',
                'email' => 'benjamin@example.com',
            ]
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Profil modifié avec succès.'
            )
            ->assertJsonPath(
                'utilisateur.nom',
                'Mbambi'
            )
            ->assertJsonPath(
                'utilisateur.prenom',
                'Benjamin'
            );

        $this->assertDatabaseHas('utilisateurs', [
            'id_utilisateur' => $this->utilisateur->id_utilisateur,
            'nom' => 'Mbambi',
            'prenom' => 'Benjamin',
            'email' => 'benjamin@example.com',
        ]);
    }

    public function test_un_visiteur_ne_peut_pas_modifier_un_profil(): void
    {
        $response = $this->patchJson(
            '/api/v1/profile',
            [
                'nom' => 'Hack',
            ]
        );

        $response->assertUnauthorized();
    }

    public function test_un_utilisateur_peut_modifier_un_seul_champ(): void
    {
       $this->actingAs($this->utilisateur, 'web');

        $response = $this->patchJson(
            '/api/v1/profile',
            [
                'prenom' => 'Benjamin',
            ]
        );

        $response->assertOk();

        $this->utilisateur->refresh();

        $this->assertSame(
            'AncienNom',
            $this->utilisateur->nom
        );

        $this->assertSame(
            'Benjamin',
            $this->utilisateur->prenom
        );
    }

    public function test_un_email_deja_utilise_est_refuse(): void
    {
        Utilisateur::query()->create([
            'telephone' => '+243899999999',
            'mot_de_passe_hash' => Hash::make('Password123!'),
            'nom' => 'Autre',
            'prenom' => 'Utilisateur',
            'email' => 'utilise@example.com',
            'role' => RoleUtilisateur::Client,
            'statut_compte' => StatutCompte::Actif,
            'telephone_verifie_at' => now(),
        ]);

        $this->actingAs($this->utilisateur, 'web');

        $response = $this->patchJson(
            '/api/v1/profile',
            [
                'email' => 'utilise@example.com',
            ]
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'email',
            ]);
    }

    public function test_un_utilisateur_ne_peut_pas_modifier_les_champs_sensibles(): void
    {
        $this->actingAs($this->utilisateur, 'web');
        $response = $this->patchJson(
            '/api/v1/profile',
            [
                'nom' => 'NouveauNom',

                'telephone' => '+243800000000',
                'role' => 'administrateur',
                'statut_compte' => 'bloque',
            ]
        );

        $response->assertOk();

        $this->utilisateur->refresh();

        $this->assertSame(
            '+243812345678',
            $this->utilisateur->telephone
        );

        $this->assertSame(
            RoleUtilisateur::Client,
            $this->utilisateur->role
        );

        $this->assertSame(
            StatutCompte::Actif,
            $this->utilisateur->statut_compte
        );

        $this->assertSame(
            'NouveauNom',
            $this->utilisateur->nom
        );
    }
}