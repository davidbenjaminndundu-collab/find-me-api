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

class ModificationAnnonceTest extends TestCase
{
    use RefreshDatabase;

    private Utilisateur $prestataire;

    private ProfilPrestataire $profil;

    private Categorie $categorie;

    private Annonce $annonce;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withHeader('Origin', 'http://localhost:8000');

        $this->prestataire = Utilisateur::query()->create([
            'telephone' => '+243810000070',
            'mot_de_passe_hash' => Hash::make('password'),
            'nom' => 'Kalilwa',
            'prenom' => 'Jean',
            'role' => RoleUtilisateur::Prestataire,
            'statut_compte' => StatutCompte::Actif,
            'telephone_verifie_at' => now(),
        ]);

        $this->profil = ProfilPrestataire::query()->create([
            'id_utilisateur' => $this->prestataire->id_utilisateur,
            'nom_professionnel' => 'Studio Kalilwa',
            'bio' => 'Graphiste numerique',
            'competences' => 'Logo, identite visuelle',
            'statut_disponibilite' => StatutDisponibilite::Disponible,
        ]);

        $this->categorie = Categorie::query()->create([
            'nom' => 'Graphisme',
            'est_active' => true,
        ]);

        $this->annonce = Annonce::query()->create([
            'id_categorie' => $this->categorie->id_categorie,
            'id_prestataire' => $this->profil->id_prestataire,
            'titre' => 'Creation de logo',
            'description' => 'Logo professionnel',
            'prix_base' => '50.00',
            'delai_livraison_jours' => 5,
            'nombre_revisions' => 2,
            'livrables_inclus' => 'PNG et SVG',
            'elements_requis_client' => 'Nom de la marque',
            'statut' => StatutAnnonce::Brouillon,
        ]);
    }

    public function test_le_proprietaire_peut_modifier_un_brouillon(): void
    {
        $response = $this->actingAs($this->prestataire)->patchJson(
            '/api/v1/annonces/'.$this->annonce->id_annonce,
            ['titre' => 'Logo premium']
        );

        $response->assertOk()
            ->assertJsonPath('annonce.titre', 'Logo premium')
            ->assertJsonPath('annonce.statut', 'brouillon');

        $this->assertDatabaseHas('annonces', [
            'id_annonce' => $this->annonce->id_annonce,
            'titre' => 'Logo premium',
            'statut' => StatutAnnonce::Brouillon->value,
        ]);
    }

    public function test_une_annonce_refusee_peut_etre_modifiee(): void
    {
        $this->annonce->update(['statut' => StatutAnnonce::Refusee]);

        $this->actingAs($this->prestataire)->patchJson(
            '/api/v1/annonces/'.$this->annonce->id_annonce,
            ['description' => 'Nouvelle description']
        )->assertOk()
            ->assertJsonPath('annonce.statut', 'refusee');
    }

    public function test_un_visiteur_ne_peut_pas_modifier_une_annonce(): void
    {
        $this->patchJson(
            '/api/v1/annonces/'.$this->annonce->id_annonce,
            ['titre' => 'Hack']
        )->assertUnauthorized();
    }

    public function test_un_autre_prestataire_ne_peut_pas_modifier_l_annonce(): void
    {
        $autre = Utilisateur::query()->create([
            'telephone' => '+243810000071',
            'mot_de_passe_hash' => Hash::make('password'),
            'nom' => 'Autre',
            'prenom' => 'Prestataire',
            'role' => RoleUtilisateur::Prestataire,
            'statut_compte' => StatutCompte::Actif,
            'telephone_verifie_at' => now(),
        ]);

        ProfilPrestataire::query()->create([
            'id_utilisateur' => $autre->id_utilisateur,
            'nom_professionnel' => 'Autre Studio',
            'bio' => 'Bio',
            'competences' => 'Dev',
            'statut_disponibilite' => StatutDisponibilite::Disponible,
        ]);

        $this->actingAs($autre)->patchJson(
            '/api/v1/annonces/'.$this->annonce->id_annonce,
            ['titre' => 'Hack']
        )->assertForbidden();
    }

    public function test_une_annonce_publiee_n_est_pas_modifiable(): void
    {
        $this->annonce->update(['statut' => StatutAnnonce::Publiee]);

        $this->actingAs($this->prestataire)->patchJson(
            '/api/v1/annonces/'.$this->annonce->id_annonce,
            ['titre' => 'Trop tard']
        )->assertStatus(409);
    }

    public function test_une_annonce_inconnue_renvoie_404(): void
    {
        $this->actingAs($this->prestataire)
            ->patchJson('/api/v1/annonces/999999', ['titre' => 'X'])
            ->assertNotFound();
    }

    public function test_le_statut_ne_peut_pas_etre_change_par_patch(): void
    {
        $this->actingAs($this->prestataire)->patchJson(
            '/api/v1/annonces/'.$this->annonce->id_annonce,
            ['statut' => 'publiee']
        )->assertUnprocessable();
    }
}