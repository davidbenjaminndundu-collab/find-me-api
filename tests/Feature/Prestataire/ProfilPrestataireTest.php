<?php

namespace Tests\Feature\Prestataire;

use App\Enums\RoleUtilisateur;
use App\Enums\StatutCompte;
use App\Enums\StatutDisponibilite;
use App\Models\Utilisateur;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfilPrestataireTest extends TestCase
{
    use RefreshDatabase;

    private Utilisateur $prestataire;

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

        $this->prestataire = Utilisateur::query()->create([
            'telephone' => '+243812345678',
            'mot_de_passe_hash' => Hash::make('Password123!'),
            'nom' => 'Mbambi',
            'prenom' => 'Benjamin',
            'role' => RoleUtilisateur::Prestataire,
            'statut_compte' => StatutCompte::Actif,
            'telephone_verifie_at' => now(),
        ]);
    }

    public function test_un_prestataire_actif_peut_completer_son_profil(): void
    {
        $this->actingAs(
            $this->prestataire,
            'web'
        );

        $response = $this->putJson(
            '/api/v1/prestataire/profil',
            [
                'nom_professionnel' => 'Kingdom Design',
                'bio' =>
                    'Graphiste spécialisé dans les identités visuelles.',
                'competences' =>
                    'Photoshop, Illustrator, Figma, Branding',
                'portfolio_url' =>
                    'https://portfolio.example.com',
                'statut_disponibilite' =>
                    StatutDisponibilite::Disponible->value,
            ]
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Profil professionnel enregistré avec succès.'
            )
            ->assertJsonPath(
                'profil.nom_professionnel',
                'Kingdom Design'
            )
            ->assertJsonPath(
                'profil.statut_disponibilite',
                'disponible'
            );

        $this->assertDatabaseHas(
            'profils_prestataires',
            [
                'id_utilisateur' =>
                    $this->prestataire->id_utilisateur,

                'nom_professionnel' =>
                    'Kingdom Design',

                'statut_disponibilite' =>
                    'disponible',
            ]
        );
    }

    public function test_un_client_ne_peut_pas_creer_un_profil_prestataire(): void
    {
        $client = Utilisateur::query()->create([
            'telephone' => '+243899999999',
            'mot_de_passe_hash' => Hash::make('Password123!'),
            'nom' => 'Client',
            'prenom' => 'Test',
            'role' => RoleUtilisateur::Client,
            'statut_compte' => StatutCompte::Actif,
            'telephone_verifie_at' => now(),
        ]);

        $this->actingAs($client, 'web');

        $response = $this->putJson(
            '/api/v1/prestataire/profil',
            [
                'nom_professionnel' => 'Fake',
                'bio' => 'Fake bio',
                'competences' => 'Fake competence',
                'statut_disponibilite' => 'disponible',
            ]
        );

        $response->assertForbidden();
    }

    public function test_un_visiteur_ne_peut_pas_creer_un_profil_prestataire(): void
    {
        $response = $this->putJson(
            '/api/v1/prestataire/profil',
            [
                'nom_professionnel' => 'Kingdom Design',
                'bio' => 'Bio',
                'competences' => 'Figma',
                'statut_disponibilite' => 'disponible',
            ]
        );

        $response->assertUnauthorized();
    }

    public function test_un_prestataire_suspendu_ne_peut_pas_completer_son_profil(): void
    {
        $this->prestataire->update([
            'statut_compte' => StatutCompte::Suspendu,
        ]);

        $this->actingAs(
            $this->prestataire->fresh(),
            'web'
        );

        $response = $this->putJson(
            '/api/v1/prestataire/profil',
            [
                'nom_professionnel' => 'Kingdom Design',
                'bio' => 'Bio',
                'competences' => 'Figma',
                'statut_disponibilite' => 'disponible',
            ]
        );

        $response->assertForbidden();
    }

    public function test_les_champs_professionnels_obligatoires_sont_valides(): void
    {
        $this->actingAs(
            $this->prestataire,
            'web'
        );

        $response = $this->putJson(
            '/api/v1/prestataire/profil',
            []
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'nom_professionnel',
                'bio',
                'competences',
                'statut_disponibilite',
            ]);
    }

    public function test_un_statut_de_disponibilite_invalide_est_refuse(): void
    {
        $this->actingAs(
            $this->prestataire,
            'web'
        );

        $response = $this->putJson(
            '/api/v1/prestataire/profil',
            [
                'nom_professionnel' => 'Kingdom Design',
                'bio' => 'Bio professionnelle',
                'competences' => 'Figma, Photoshop',
                'statut_disponibilite' => 'vacances',
            ]
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'statut_disponibilite',
            ]);
    }

    public function test_un_prestataire_ne_peut_pas_modifier_sa_note(): void
    {
        $this->actingAs(
            $this->prestataire,
            'web'
        );

        $response = $this->putJson(
            '/api/v1/prestataire/profil',
            [
                'nom_professionnel' => 'Kingdom Design',
                'bio' => 'Bio professionnelle',
                'competences' => 'Figma, Photoshop',
                'statut_disponibilite' => 'disponible',

                'note_moyenne' => 5,
                'nombre_avis' => 999,
            ]
        );

        $response->assertOk();

        $this->assertDatabaseHas(
            'profils_prestataires',
            [
                'id_utilisateur' =>
                    $this->prestataire->id_utilisateur,

                'note_moyenne' => 0,
                'nombre_avis' => 0,
            ]
        );
    }

    public function test_completer_le_profil_deux_fois_ne_cree_pas_de_doublon(): void
    {
        $this->actingAs(
            $this->prestataire,
            'web'
        );

        $premierProfil = [
            'nom_professionnel' => 'Ancien Nom',
            'bio' => 'Ancienne bio',
            'competences' => 'Photoshop',
            'statut_disponibilite' => 'disponible',
        ];

        $this->putJson(
            '/api/v1/prestataire/profil',
            $premierProfil
        )->assertOk();

        $deuxiemeProfil = [
            'nom_professionnel' => 'Nouveau Nom',
            'bio' => 'Nouvelle bio',
            'competences' => 'Photoshop, Figma',
            'statut_disponibilite' => 'occupe',
        ];

        $this->putJson(
            '/api/v1/prestataire/profil',
            $deuxiemeProfil
        )->assertOk();

        $this->assertDatabaseCount(
            'profils_prestataires',
            1
        );

        $this->assertDatabaseHas(
            'profils_prestataires',
            [
                'id_utilisateur' =>
                    $this->prestataire->id_utilisateur,

                'nom_professionnel' =>
                    'Nouveau Nom',

                'statut_disponibilite' =>
                    'occupe',
            ]
        );
    }
}