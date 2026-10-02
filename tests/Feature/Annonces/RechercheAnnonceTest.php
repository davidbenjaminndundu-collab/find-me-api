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

class RechercheAnnonceTest extends TestCase
{
    use RefreshDatabase;

    private ProfilPrestataire $profil;

    private Categorie $categorie;

    private Annonce $annonceLogo;

    private Annonce $annonceSite;

    protected function setUp(): void
    {
        parent::setUp();

        $prestataire = Utilisateur::query()->create([
            'telephone' => '+243810000200',
            'mot_de_passe_hash' => Hash::make('password'),
            'nom' => 'Kalilwa',
            'prenom' => 'Jean',
            'email' => 'recherche@example.com',
            'role' => RoleUtilisateur::Prestataire,
            'statut_compte' => StatutCompte::Actif,
            'telephone_verifie_at' => now(),
        ]);

        $this->profil = ProfilPrestataire::query()->create([
            'id_utilisateur' => $prestataire->id_utilisateur,
            'nom_professionnel' => 'Studio Kalilwa',
            'bio' => 'Services numériques',
            'competences' => 'Logo, web',
            'statut_disponibilite' => StatutDisponibilite::Disponible,
        ]);

        $this->categorie = Categorie::query()->create([
            'nom' => 'Graphisme',
            'description' => 'Services graphiques',
            'est_active' => true,
        ]);

        $this->annonceLogo = Annonce::query()->create([
            'id_categorie' => $this->categorie->id_categorie,
            'id_prestataire' => $this->profil->id_prestataire,
            'titre' => 'Création de logo professionnel',
            'description' => 'Je réalise un logo pour votre entreprise.',
            'prix_base' => 50,
            'delai_livraison_jours' => 5,
            'nombre_revisions' => 2,
            'livrables_inclus' => 'PNG, SVG',
            'elements_requis_client' => 'Nom et couleurs',
            'mots_cles' => 'logo, branding',
            'statut' => StatutAnnonce::Publiee,
            'published_at' => now(),
        ]);

        $this->annonceSite = Annonce::query()->create([
            'id_categorie' => $this->categorie->id_categorie,
            'id_prestataire' => $this->profil->id_prestataire,
            'titre' => 'Création de site vitrine',
            'description' => 'Je développe un site web simple.',
            'prix_base' => 200,
            'delai_livraison_jours' => 14,
            'nombre_revisions' => 1,
            'livrables_inclus' => 'Site HTML',
            'elements_requis_client' => 'Contenus textes',
            'mots_cles' => 'site, web',
            'statut' => StatutAnnonce::Publiee,
            'published_at' => now()->subMinute(),
        ]);
    }

    public function test_un_visiteur_peut_lister_les_annonces_publiees_sans_authentification(): void
    {
        $this->assertGuest();

        $response = $this->getJson('/api/v1/annonces');

        $response
            ->assertOk()
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('meta.limit', 20)
            ->assertJsonPath('meta.offset', 0)
            ->assertJsonCount(2, 'data');
    }

    public function test_la_recherche_par_mot_cle_retourne_les_annonces_correspondantes(): void
    {
        $response = $this->getJson('/api/v1/annonces?q=logo');

        $response
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id_annonce', $this->annonceLogo->id_annonce)
            ->assertJsonPath('data.0.titre', 'Création de logo professionnel')
            ->assertJsonPath(
                'data.0.prestataire.nom_professionnel',
                'Studio Kalilwa'
            );
    }

    public function test_la_recherche_sans_resultat_retourne_une_liste_vide(): void
    {
        $this->getJson('/api/v1/annonces?q=inexistantxyz')
            ->assertOk()
            ->assertJsonPath('meta.total', 0)
            ->assertJsonCount(0, 'data');
    }

    public function test_un_brouillon_n_apparait_pas_dans_la_recherche(): void
    {
        $this->annonceLogo->update([
            'statut' => StatutAnnonce::Brouillon,
            'published_at' => null,
        ]);

        $response = $this->getJson('/api/v1/annonces?q=logo');

        $response
            ->assertOk()
            ->assertJsonPath('meta.total', 0)
            ->assertJsonCount(0, 'data');
    }

    public function test_la_pagination_est_appliquee(): void
    {
        $response = $this->getJson('/api/v1/annonces?limit=1&offset=0');

        $response
            ->assertOk()
            ->assertJsonPath('meta.limit', 1)
            ->assertJsonPath('meta.offset', 0)
            ->assertJsonPath('meta.total', 2)
            ->assertJsonCount(1, 'data');
    }

    public function test_une_limite_invalide_est_refusee(): void
    {
        $this->getJson('/api/v1/annonces?limit=0')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['limit']);
    }

    public function test_le_filtre_par_categorie_retourne_uniquement_les_annonces_de_cette_categorie(): void
    {
        $autreCategorie = Categorie::query()->create([
            'nom' => 'Développement web',
            'description' => 'Sites et applications',
            'est_active' => true,
        ]);

        $this->annonceSite->update([
            'id_categorie' => $autreCategorie->id_categorie,
        ]);

        $response = $this->getJson(
            '/api/v1/annonces?id_categorie=' . $this->categorie->id_categorie
        );

        $response
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id_annonce', $this->annonceLogo->id_annonce)
            ->assertJsonPath(
                'data.0.categorie.id_categorie',
                $this->categorie->id_categorie
            );
    }

    public function test_le_filtre_categorie_peut_etre_combine_avec_la_recherche(): void
    {
        $response = $this->getJson(
            '/api/v1/annonces?q=logo&id_categorie=' . $this->categorie->id_categorie
        );

        $response
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id_annonce', $this->annonceLogo->id_annonce);
    }

    public function test_une_categorie_inexistante_est_refusee(): void
    {
        $this->getJson('/api/v1/annonces?id_categorie=999999')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['id_categorie']);
    }

    public function test_le_filtre_prix_min_retourne_les_annonces_assez_cheres(): void
    {
        $response = $this->getJson('/api/v1/annonces?prix_min=100');
        $response
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id_annonce', $this->annonceSite->id_annonce);
    }
    public function test_le_filtre_prix_max_retourne_les_annonces_assez_bon_marche(): void
    {
        $response = $this->getJson('/api/v1/annonces?prix_max=100');
        $response
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id_annonce', $this->annonceLogo->id_annonce);
    }
    public function test_le_filtre_prix_min_et_max_fonctionne_ensemble(): void
    {
        $response = $this->getJson('/api/v1/annonces?prix_min=40&prix_max=60');
        $response
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id_annonce', $this->annonceLogo->id_annonce);
    }
    public function test_prix_max_inferieur_a_prix_min_est_refuse(): void
    {
        $this->getJson('/api/v1/annonces?prix_min=100&prix_max=50')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['prix_max']);
    }
}