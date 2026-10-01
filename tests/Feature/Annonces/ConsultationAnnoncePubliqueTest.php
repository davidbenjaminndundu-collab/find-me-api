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

class ConsultationAnnoncePubliqueTest extends TestCase
{
    use RefreshDatabase;

    private Utilisateur $prestataire;

    private ProfilPrestataire $profil;

    private Categorie $categorie;

    private Annonce $annonce;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prestataire = Utilisateur::query()->create([
            'telephone' => '+243810000100',
            'mot_de_passe_hash' => Hash::make('password'),
            'nom' => 'Kalilwa',
            'prenom' => 'Jean',
            'email' => 'kalilwa@example.com',
            'role' => RoleUtilisateur::Prestataire,
            'statut_compte' => StatutCompte::Actif,
            'telephone_verifie_at' => now(),
        ]);

        $this->profil = ProfilPrestataire::query()->create([
            'id_utilisateur' => $this->prestataire->id_utilisateur,
            'nom_professionnel' => 'Studio Kalilwa',
            'bio' => 'Graphiste spécialisé dans les services numériques.',
            'competences' => 'Logo, branding, identité visuelle',
            'portfolio_url' => 'https://portfolio.example.com',
            'statut_disponibilite' => StatutDisponibilite::Disponible,
        ]);

        $this->categorie = Categorie::query()->create([
            'nom' => 'Graphisme',
            'description' => 'Services graphiques réalisables à distance',
            'est_active' => true,
        ]);

        $this->annonce = Annonce::query()->create([
            'id_categorie' => $this->categorie->id_categorie,
            'id_prestataire' => $this->profil->id_prestataire,
            'titre' => 'Création de logo professionnel',
            'description' => 'Je réalise un logo professionnel adapté à votre entreprise.',
            'prix_base' => 50,
            'delai_livraison_jours' => 5,
            'nombre_revisions' => 2,
            'livrables_inclus' => 'PNG, JPG, SVG et PDF',
            'elements_requis_client' => 'Nom de l’entreprise, couleurs et préférences graphiques',
            'image_couverture' => null,
            'mots_cles' => 'logo, graphisme, branding',
            'conditions_particulieres' => 'Deux révisions sont incluses.',
            'statut' => StatutAnnonce::Publiee,
            'published_at' => now(),
        ]);
    }

    public function test_un_visiteur_peut_consulter_une_annonce_publiee(): void
    {
        $response = $this->getJson(
            "/api/v1/annonces/{$this->annonce->id_annonce}"
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'annonce.id_annonce',
                $this->annonce->id_annonce
            )
            ->assertJsonPath(
                'annonce.titre',
                'Création de logo professionnel'
            )
            ->assertJsonPath(
                'annonce.description',
                'Je réalise un logo professionnel adapté à votre entreprise.'
            )
            ->assertJsonPath(
                'annonce.prix_base',
                '50.00'
            )
            ->assertJsonPath(
                'annonce.delai_livraison_jours',
                5
            )
            ->assertJsonPath(
                'annonce.nombre_revisions',
                2
            )
            ->assertJsonPath(
                'annonce.livrables_inclus',
                'PNG, JPG, SVG et PDF'
            )
            ->assertJsonPath(
                'annonce.categorie.id_categorie',
                $this->categorie->id_categorie
            )
            ->assertJsonPath(
                'annonce.categorie.nom',
                'Graphisme'
            )
            ->assertJsonPath(
                'annonce.prestataire.id_prestataire',
                $this->profil->id_prestataire
            )
            ->assertJsonPath(
                'annonce.prestataire.nom_professionnel',
                'Studio Kalilwa'
            )
            ->assertJsonPath(
                'annonce.prestataire.statut_disponibilite',
                'disponible'
            );
    }

    public function test_la_consultation_est_accessible_sans_authentification(): void
    {
        $this->assertGuest();

        $this->getJson(
            "/api/v1/annonces/{$this->annonce->id_annonce}"
        )->assertOk();
    }

    public function test_un_brouillon_n_est_pas_visible_publiquement(): void
    {
        $this->annonce->update([
            'statut' => StatutAnnonce::Brouillon,
            'published_at' => null,
        ]);

        $this->getJson(
            "/api/v1/annonces/{$this->annonce->id_annonce}"
        )->assertNotFound();
    }

    public function test_une_annonce_refusee_n_est_pas_visible_publiquement(): void
    {
        $this->annonce->update([
            'statut' => StatutAnnonce::Refusee,
            'motif_refus' => 'Annonce non conforme.',
        ]);

        $this->getJson(
            "/api/v1/annonces/{$this->annonce->id_annonce}"
        )->assertNotFound();
    }

    public function test_une_annonce_desactivee_n_est_pas_visible_publiquement(): void
    {
        $this->annonce->update([
            'statut' => StatutAnnonce::Desactivee,
        ]);

        $this->getJson(
            "/api/v1/annonces/{$this->annonce->id_annonce}"
        )->assertNotFound();
    }

    public function test_une_annonce_supprimee_n_est_pas_visible_publiquement(): void
    {
        $this->annonce->update([
            'statut' => StatutAnnonce::Supprimee,
        ]);

        $this->getJson(
            "/api/v1/annonces/{$this->annonce->id_annonce}"
        )->assertNotFound();
    }

    public function test_une_annonce_inexistante_retourne_404(): void
    {
        $this->getJson(
            '/api/v1/annonces/999999'
        )->assertNotFound();
    }

    public function test_les_informations_publiques_du_prestataire_sont_retournees(): void
    {
        $response = $this->getJson(
            "/api/v1/annonces/{$this->annonce->id_annonce}"
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'annonce.prestataire.nom_professionnel',
                'Studio Kalilwa'
            )
            ->assertJsonPath(
                'annonce.prestataire.note_moyenne',
                '0.00'
            )
            ->assertJsonPath(
                'annonce.prestataire.nombre_avis',
                0
            )
            ->assertJsonPath(
                'annonce.prestataire.statut_disponibilite',
                'disponible'
            );
    }

    public function test_les_donnees_privees_du_prestataire_ne_sont_pas_exposees(): void
    {
        $response = $this->getJson(
            "/api/v1/annonces/{$this->annonce->id_annonce}"
        );

        $response
            ->assertOk()
            ->assertJsonMissingPath(
                'annonce.prestataire.telephone'
            )
            ->assertJsonMissingPath(
                'annonce.prestataire.email'
            )
            ->assertJsonMissingPath(
                'annonce.prestataire.mot_de_passe_hash'
            )
            ->assertJsonMissingPath(
                'annonce.prestataire.statut_compte'
            )
            ->assertJsonMissingPath(
                'annonce.prestataire.id_utilisateur'
            );
    }

    public function test_les_informations_de_moderation_ne_sont_pas_exposees(): void
    {
        $this->annonce->update([
            'validee_admin_at' => now(),
        ]);

        $response = $this->getJson(
            "/api/v1/annonces/{$this->annonce->id_annonce}"
        );

        $response
            ->assertOk()
            ->assertJsonMissingPath(
                'annonce.validee_admin_at'
            )
            ->assertJsonMissingPath(
                'annonce.motif_refus'
            );
    }

    public function test_une_annonce_publiee_est_visible_avant_validation_admin(): void
    {
        $this->annonce->update([
            'validee_admin_at' => null,
        ]);

        $response = $this->getJson(
            "/api/v1/annonces/{$this->annonce->id_annonce}"
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'annonce.id_annonce',
                $this->annonce->id_annonce
            );
    }

    public function test_une_annonce_validee_par_admin_reste_visible(): void
    {
        $this->annonce->update([
            'validee_admin_at' => now(),
        ]);

        $response = $this->getJson(
            "/api/v1/annonces/{$this->annonce->id_annonce}"
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'annonce.titre',
                'Création de logo professionnel'
            );
    }
}
