<?php

namespace Tests\Feature\Administration;

use App\Enums\RoleUtilisateur;
use App\Enums\StatutAnnonce;
use App\Enums\StatutCompte;
use App\Enums\StatutDisponibilite;
use App\Models\Annonce;
use App\Models\Categorie;
use App\Models\ProfilPrestataire;
use App\Models\Utilisateur;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ModerationAnnonceTest extends TestCase
{
    use RefreshDatabase;

    private Utilisateur $admin;
    private Utilisateur $prestataire;
    private Annonce $annonce;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withHeader(
            'Origin',
            'http://localhost:8000'
        );

        $this->admin = Utilisateur::query()->create([
            'telephone' => '+243810000090',
            'mot_de_passe_hash' => Hash::make('password'),
            'nom' => 'Admin',
            'prenom' => 'FindMe',
            'role' => RoleUtilisateur::Administrateur,
            'statut_compte' => StatutCompte::Actif,
            'telephone_verifie_at' => now(),
        ]);

        $this->prestataire = Utilisateur::query()->create([
            'telephone' => '+243810000091',
            'mot_de_passe_hash' => Hash::make('password'),
            'nom' => 'Kalilwa',
            'prenom' => 'Jean',
            'role' => RoleUtilisateur::Prestataire,
            'statut_compte' => StatutCompte::Actif,
            'telephone_verifie_at' => now(),
        ]);

        $profil = ProfilPrestataire::query()->create([
            'id_utilisateur' =>
                $this->prestataire->id_utilisateur,

            'nom_professionnel' =>
                'Studio Kalilwa',

            'bio' =>
                'Graphiste numérique',

            'competences' =>
                'Logo, branding',

            'statut_disponibilite' =>
                StatutDisponibilite::Disponible,
        ]);

        $categorie = Categorie::query()->create([
            'nom' => 'Graphisme',
            'description' => 'Services graphiques',
            'est_active' => true,
        ]);

        $this->annonce = Annonce::query()->create([
            'id_categorie' =>
                $categorie->id_categorie,

            'id_prestataire' =>
                $profil->id_prestataire,

            'titre' =>
                'Création de logo',

            'description' =>
                'Création de logo professionnel',

            'prix_base' => 50,

            'delai_livraison_jours' => 5,

            'nombre_revisions' => 2,

            'livrables_inclus' =>
                'PNG, SVG',

            'elements_requis_client' =>
                'Nom et couleurs',

            'statut' =>
                StatutAnnonce::Publiee,

            'published_at' => now(),
        ]);
    }

    public function test_un_admin_actif_peut_valider_une_annonce_publiee(): void
    {
        $response = $this
            ->actingAs($this->admin)
            ->patchJson(
                "/api/v1/admin/annonces/{$this->annonce->id_annonce}/valider"
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'annonce.statut',
                'publiee'
            );

        $annonce = $this->annonce->fresh();

        $this->assertNotNull(
            $annonce->validee_admin_at
        );

        $this->assertNull(
            $annonce->motif_refus
        );
    }

    public function test_un_admin_actif_peut_refuser_une_annonce_publiee(): void
    {
        $response = $this
            ->actingAs($this->admin)
            ->patchJson(
                "/api/v1/admin/annonces/{$this->annonce->id_annonce}/refuser",
                [
                    'motif_refus' =>
                        'Le contenu ne respecte pas les règles de la plateforme.',
                ]
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'annonce.statut',
                'refusee'
            );

        $this->assertDatabaseHas('annonces', [
            'id_annonce' =>
                $this->annonce->id_annonce,

            'statut' =>
                StatutAnnonce::Refusee->value,

            'motif_refus' =>
                'Le contenu ne respecte pas les règles de la plateforme.',
        ]);
    }

    public function test_un_refus_sans_motif_est_refuse(): void
    {
        $this
            ->actingAs($this->admin)
            ->patchJson(
                "/api/v1/admin/annonces/{$this->annonce->id_annonce}/refuser",
                []
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'motif_refus',
            ]);
    }

    public function test_un_prestataire_ne_peut_pas_valider_une_annonce(): void
    {
        $this
            ->actingAs($this->prestataire)
            ->patchJson(
                "/api/v1/admin/annonces/{$this->annonce->id_annonce}/valider"
            )
            ->assertForbidden();
    }

    public function test_un_client_ne_peut_pas_refuser_une_annonce(): void
    {
        $client = Utilisateur::query()->create([
            'telephone' => '+243810000092',
            'mot_de_passe_hash' => Hash::make('password'),
            'nom' => 'Client',
            'prenom' => 'Test',
            'role' => RoleUtilisateur::Client,
            'statut_compte' => StatutCompte::Actif,
            'telephone_verifie_at' => now(),
        ]);

        $this
            ->actingAs($client)
            ->patchJson(
                "/api/v1/admin/annonces/{$this->annonce->id_annonce}/refuser",
                [
                    'motif_refus' => 'Tentative non autorisée',
                ]
            )
            ->assertForbidden();
    }

    public function test_un_admin_suspendu_ne_peut_pas_moderer(): void
    {
        $this->admin->update([
            'statut_compte' =>
                StatutCompte::Suspendu,
        ]);

        $this
            ->actingAs($this->admin->fresh())
            ->patchJson(
                "/api/v1/admin/annonces/{$this->annonce->id_annonce}/valider"
            )
            ->assertForbidden();
    }

    public function test_un_brouillon_ne_peut_pas_etre_valide(): void
    {
        $this->annonce->update([
            'statut' =>
                StatutAnnonce::Brouillon,

            'published_at' => null,
        ]);

        $this
            ->actingAs($this->admin)
            ->patchJson(
                "/api/v1/admin/annonces/{$this->annonce->id_annonce}/valider"
            )
            ->assertStatus(409);
    }

    public function test_une_annonce_desactivee_ne_peut_pas_etre_refusee(): void
    {
        $this->annonce->update([
            'statut' =>
                StatutAnnonce::Desactivee,
        ]);

        $this
            ->actingAs($this->admin)
            ->patchJson(
                "/api/v1/admin/annonces/{$this->annonce->id_annonce}/refuser",
                [
                    'motif_refus' =>
                        'Annonce non conforme',
                ]
            )
            ->assertStatus(409);
    }

    public function test_une_annonce_deja_validee_ne_peut_pas_etre_validee_de_nouveau(): void
    {
        $this->annonce->update([
            'validee_admin_at' => now(),
        ]);

        $this
            ->actingAs($this->admin)
            ->patchJson(
                "/api/v1/admin/annonces/{$this->annonce->id_annonce}/valider"
            )
            ->assertStatus(409);
    }

}