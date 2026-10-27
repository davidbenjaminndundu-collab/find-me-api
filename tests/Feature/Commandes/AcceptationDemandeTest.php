<?php

namespace Tests\Feature\Commandes;

use App\Enums\RoleUtilisateur;
use App\Enums\StatutCommande;
use App\Enums\StatutCompte;
use App\Enums\StatutDisponibilite;
use App\Models\Commande;
use App\Models\ProfilPrestataire;
use App\Models\Utilisateur;
use App\Enums\StatutAnnonce;
use App\Models\Annonce;
use App\Models\Categorie;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AcceptationDemandeTest extends TestCase
{
    use RefreshDatabase;

    private Utilisateur $client;

    private Utilisateur $prestataire;

    private ProfilPrestataire $profilPrestataire;

    private Commande $commande;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withHeader(
            'Origin',
            'http://localhost:5173'
        );

        $this->client =
            Utilisateur::query()->create([
                'telephone' =>
                    '+243810003001',

                'mot_de_passe_hash' =>
                    Hash::make('password'),

                'nom' =>
                    'Client',

                'prenom' =>
                    'Test',

                'role' =>
                    RoleUtilisateur::Client,

                'statut_compte' =>
                    StatutCompte::Actif,

                'telephone_verifie_at' =>
                    now(),
            ]);

        $this->prestataire =
            Utilisateur::query()->create([
                'telephone' =>
                    '+243810003002',

                'mot_de_passe_hash' =>
                    Hash::make('password'),

                'nom' =>
                    'Prestataire',

                'prenom' =>
                    'Test',

                'role' =>
                    RoleUtilisateur::Prestataire,

                'statut_compte' =>
                    StatutCompte::Actif,

                'telephone_verifie_at' =>
                    now(),
            ]);

        $this->profilPrestataire =
            ProfilPrestataire::query()->create([
                'id_utilisateur' =>
                    $this->prestataire
                        ->id_utilisateur,

                'nom_professionnel' =>
                    'Studio FindMe',

                'bio' =>
                    'Studio de création numérique',

                'competences' =>
                    'Design graphique',

                'statut_disponibilite' =>
                    StatutDisponibilite::Disponible,
            ]);
        $categorie = Categorie::query()->create([
            'nom' => 'Graphisme',
            'description' => 'Services graphiques à distance',
            'est_active' => true,
        ]);

        $annonce = Annonce::query()->create([
            'id_categorie' => $categorie->id_categorie,
            'id_prestataire' => $this->profilPrestataire->id_prestataire,

            'titre' => 'Création de logo',
            'description' => 'Création de logo professionnel',

            'prix_base' => '100.00',
            'delai_livraison_jours' => 5,
            'nombre_revisions' => 2,

            'livrables_inclus' => 'PNG, SVG et PDF',

            'elements_requis_client' =>
                'Nom de la société, couleurs et préférences graphiques',

            'statut' => StatutAnnonce::Publiee,
            'published_at' => now(),
        ]);
        $this->commande =
            Commande::query()->create([
                'id_prestataire' =>
                    $this->profilPrestataire
                        ->id_prestataire,

                'id_client' =>
                    $this->client
                        ->id_utilisateur,

                'reference_commande' =>
                    'CMD-TEST-D03-001',

                'description_commande' =>
                    'Je souhaite un logo professionnel pour mon entreprise.',

                'titre_service' =>
                    'Création de logo',

                'est_offre_personnalisee' =>
                    false,

                'montant_total' =>
                    '100.00',

                'taux_commission' =>
                    '10.00',

                'montant_commission' =>
                    '10.00',

                'montant_prestataire' =>
                    '90.00',

                'delai_livraison_jours' =>
                    5,

                'nombre_revisions_incluses' =>
                    2,

                'nombre_revisions_utilisees' =>
                    0,

                'livrables_convenus' =>
                    'PNG, SVG et PDF',

                'statut' =>
                    StatutCommande::EnAttenteReponse,
                'id_annonce' => $annonce->id_annonce,
            ]);
    }

    public function test_le_prestataire_peut_accepter_sa_demande_standard(): void
    {
        $response = $this
            ->actingAs($this->prestataire)
            ->patchJson(
                "/api/v1/commandes/{$this->commande->id_commande}/accepter"
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'commande.id_commande',
                $this->commande->id_commande
            )
            ->assertJsonPath(
                'commande.statut',
                'en_attente_paiement'
            );

        $commande =
            $this->commande->fresh();

        $this->assertSame(
            StatutCommande::EnAttentePaiement,
            $commande->statut
        );

        $this->assertNotNull(
            $commande->date_acceptation
        );
    }

    public function test_les_conditions_figees_restent_inchangees_apres_acceptation(): void
    {
        $this
            ->actingAs($this->prestataire)
            ->patchJson(
                "/api/v1/commandes/{$this->commande->id_commande}/accepter"
            )
            ->assertOk();

        $this->assertDatabaseHas(
            'commandes',
            [
                'id_commande' =>
                    $this->commande->id_commande,

                'montant_total' =>
                    '100.00',

                'delai_livraison_jours' =>
                    5,

                'nombre_revisions_incluses' =>
                    2,

                'livrables_convenus' =>
                    'PNG, SVG et PDF',

                'statut' =>
                    StatutCommande::EnAttentePaiement
                        ->value,
            ]
        );
    }

    public function test_un_prestataire_ne_peut_pas_accepter_la_demande_d_un_autre(): void
    {
        $autrePrestataire =
            Utilisateur::query()->create([
                'telephone' =>
                    '+243810003003',

                'mot_de_passe_hash' =>
                    Hash::make('password'),

                'nom' =>
                    'Autre',

                'prenom' =>
                    'Prestataire',

                'role' =>
                    RoleUtilisateur::Prestataire,

                'statut_compte' =>
                    StatutCompte::Actif,

                'telephone_verifie_at' =>
                    now(),
            ]);

        ProfilPrestataire::query()->create([
            'id_utilisateur' =>
                $autrePrestataire
                    ->id_utilisateur,

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
                "/api/v1/commandes/{$this->commande->id_commande}/accepter"
            )
            ->assertNotFound();

        $this->assertDatabaseHas(
            'commandes',
            [
                'id_commande' =>
                    $this->commande->id_commande,

                'statut' =>
                    StatutCommande::EnAttenteReponse
                        ->value,
            ]
        );
    }

    public function test_un_client_ne_peut_pas_accepter_une_demande(): void
    {
        $this
            ->actingAs($this->client)
            ->patchJson(
                "/api/v1/commandes/{$this->commande->id_commande}/accepter"
            )
            ->assertForbidden();
    }

    public function test_un_visiteur_ne_peut_pas_accepter_une_demande(): void
    {
        $this
            ->patchJson(
                "/api/v1/commandes/{$this->commande->id_commande}/accepter"
            )
            ->assertUnauthorized();
    }

    public function test_un_prestataire_suspendu_ne_peut_pas_accepter_une_demande(): void
    {
        $this->prestataire->update([
            'statut_compte' =>
                StatutCompte::Suspendu,
        ]);

        $this
            ->actingAs(
                $this->prestataire->fresh()
            )
            ->patchJson(
                "/api/v1/commandes/{$this->commande->id_commande}/accepter"
            )
            ->assertForbidden();

        $this->assertDatabaseHas(
            'commandes',
            [
                'id_commande' =>
                    $this->commande->id_commande,

                'statut' =>
                    StatutCommande::EnAttenteReponse
                        ->value,
            ]
        );
    }

    public function test_une_demande_deja_acceptee_ne_peut_pas_etre_acceptee_a_nouveau(): void
    {
        $this->commande->update([
            'statut' =>
                StatutCommande::EnAttentePaiement,

            'date_acceptation' =>
                now(),
        ]);

        $this
            ->actingAs($this->prestataire)
            ->patchJson(
                "/api/v1/commandes/{$this->commande->id_commande}/accepter"
            )
            ->assertStatus(409);
    }

    public function test_une_demande_refusee_ne_peut_pas_etre_acceptee(): void
    {
        $this->commande->update([
            'statut' =>
                StatutCommande::Refusee,
        ]);

        $this
            ->actingAs($this->prestataire)
            ->patchJson(
                "/api/v1/commandes/{$this->commande->id_commande}/accepter"
            )
            ->assertStatus(409);
    }

    public function test_une_demande_en_attente_d_informations_ne_peut_pas_etre_acceptee_directement(): void
    {
        $this->commande->update([
            'statut' =>
                StatutCommande::InformationsDemandees,
        ]);

        $this
            ->actingAs($this->prestataire)
            ->patchJson(
                "/api/v1/commandes/{$this->commande->id_commande}/accepter"
            )
            ->assertStatus(409);
    }

    public function test_une_offre_personnalisee_ne_peut_pas_etre_acceptee_avec_l_endpoint_demande_standard(): void
    {
        $this->commande->update([
            'est_offre_personnalisee' =>
                true,

            'statut' =>
                StatutCommande::EnAttenteReponse,
        ]);

        $this
            ->actingAs($this->prestataire)
            ->patchJson(
                "/api/v1/commandes/{$this->commande->id_commande}/accepter"
            )
            ->assertStatus(409);
    }

    public function test_une_commande_inexistante_retourne_404(): void
    {
        $this
            ->actingAs($this->prestataire)
            ->patchJson(
                '/api/v1/commandes/999999/accepter'
            )
            ->assertNotFound();
    }
}