<?php

namespace Tests\Feature\Annonces;

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

class DesactivationAnnonceTest extends TestCase
{
    use RefreshDatabase;

    private Utilisateur $prestataire;

    private ProfilPrestataire $profil;

    private Annonce $annonce;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withHeader(
            'Origin',
            'http://localhost:8000'
        );

        $this->prestataire = Utilisateur::query()->create([
            'telephone' => '+243810000080',
            'mot_de_passe_hash' => Hash::make('password'),
            'nom' => 'Kalilwa',
            'prenom' => 'Jean',
            'role' => RoleUtilisateur::Prestataire,
            'statut_compte' => StatutCompte::Actif,
            'telephone_verifie_at' => now(),
        ]);

        $this->profil = ProfilPrestataire::query()->create([
            'id_utilisateur' =>
                $this->prestataire->id_utilisateur,

            'nom_professionnel' =>
                'Studio Kalilwa',

            'bio' =>
                'Graphiste numerique',

            'competences' =>
                'Logo, identite visuelle',

            'statut_disponibilite' =>
                StatutDisponibilite::Disponible,
        ]);

        $categorie = Categorie::query()->create([
            'nom' => 'Graphisme',
            'description' => 'Services graphiques a distance',
            'est_active' => true,
        ]);

        $this->annonce = Annonce::query()->create([
            'id_categorie' => $categorie->id_categorie,
            'id_prestataire' => $this->profil->id_prestataire,
            'titre' => 'Creation de logo',
            'description' => 'Logo professionnel pour entreprise',
            'prix_base' => '50.00',
            'delai_livraison_jours' => 5,
            'nombre_revisions' => 2,
            'livrables_inclus' => 'PNG et SVG',
            'elements_requis_client' => 'Nom et couleurs',
            'statut' => StatutAnnonce::Publiee,
            'published_at' => now(),
        ]);
    }

    public function test_un_prestataire_peut_desactiver_son_annonce_publiee(): void
    {
        $publishedAt = $this->annonce->published_at;

        $response = $this
            ->actingAs($this->prestataire)
            ->patchJson(
                "/api/v1/annonces/{$this->annonce->id_annonce}/desactiver"
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'annonce.statut',
                'desactivee'
            );

        $this->assertDatabaseHas('annonces', [
            'id_annonce' => $this->annonce->id_annonce,
            'statut' => StatutAnnonce::Desactivee->value,
        ]);

        $this->assertEquals(
            $publishedAt?->timestamp,
            $this->annonce->fresh()->published_at?->timestamp
        );
    }

    public function test_un_prestataire_ne_peut_pas_desactiver_l_annonce_d_un_autre(): void
    {
        $autrePrestataire = Utilisateur::query()->create([
            'telephone' => '+243810000081',
            'mot_de_passe_hash' => Hash::make('password'),
            'nom' => 'Autre',
            'prenom' => 'Prestataire',
            'role' => RoleUtilisateur::Prestataire,
            'statut_compte' => StatutCompte::Actif,
            'telephone_verifie_at' => now(),
        ]);

        ProfilPrestataire::query()->create([
            'id_utilisateur' =>
                $autrePrestataire->id_utilisateur,

            'nom_professionnel' =>
                'Autre Studio',

            'bio' =>
                'Prestataire externe',

            'competences' =>
                'Design',

            'statut_disponibilite' =>
                StatutDisponibilite::Disponible,
        ]);

        $this
            ->actingAs($autrePrestataire)
            ->patchJson(
                "/api/v1/annonces/{$this->annonce->id_annonce}/desactiver"
            )
            ->assertNotFound();

        $this->assertDatabaseHas('annonces', [
            'id_annonce' => $this->annonce->id_annonce,
            'statut' => StatutAnnonce::Publiee->value,
        ]);
    }

    public function test_un_brouillon_ne_peut_pas_etre_desactive(): void
    {
        $this->annonce->update([
            'statut' => StatutAnnonce::Brouillon,
            'published_at' => null,
        ]);

        $this
            ->actingAs($this->prestataire)
            ->patchJson(
                "/api/v1/annonces/{$this->annonce->id_annonce}/desactiver"
            )
            ->assertStatus(409);
    }

    public function test_une_annonce_deja_desactivee_ne_peut_pas_etre_desactivee_de_nouveau(): void
    {
        $this->annonce->update([
            'statut' => StatutAnnonce::Desactivee,
        ]);

        $this
            ->actingAs($this->prestataire)
            ->patchJson(
                "/api/v1/annonces/{$this->annonce->id_annonce}/desactiver"
            )
            ->assertStatus(409);
    }

    public function test_un_visiteur_ne_peut_pas_desactiver_une_annonce(): void
    {
        $this
            ->patchJson(
                "/api/v1/annonces/{$this->annonce->id_annonce}/desactiver"
            )
            ->assertUnauthorized();
    }

    public function test_un_client_ne_peut_pas_desactiver_une_annonce(): void
    {
        $client = Utilisateur::query()->create([
            'telephone' => '+243810000082',
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
                "/api/v1/annonces/{$this->annonce->id_annonce}/desactiver"
            )
            ->assertForbidden();
    }

    public function test_un_prestataire_suspendu_ne_peut_pas_desactiver_une_annonce(): void
    {
        $this->prestataire->update([
            'statut_compte' => StatutCompte::Suspendu,
        ]);

        $this
            ->actingAs($this->prestataire->fresh())
            ->patchJson(
                "/api/v1/annonces/{$this->annonce->id_annonce}/desactiver"
            )
            ->assertForbidden();

        $this->assertDatabaseHas('annonces', [
            'id_annonce' => $this->annonce->id_annonce,
            'statut' => StatutAnnonce::Publiee->value,
        ]);
    }
}