<?php

namespace Tests\Feature\Commandes;

use App\Enums\RoleUtilisateur;
use App\Enums\StatutAnnonce;
use App\Enums\StatutCommande;
use App\Enums\StatutCompte;
use App\Enums\StatutDisponibilite;
use App\Models\Annonce;
use App\Models\Categorie;
use App\Models\ProfilPrestataire;
use App\Models\Utilisateur;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreationDemandeTest extends TestCase
{
    use RefreshDatabase;

    private Utilisateur $client;

    private Utilisateur $prestataire;

    private ProfilPrestataire $profilPrestataire;

    private Annonce $annonce;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withHeader(
            'Origin',
            'http://localhost:5173'
        );

        $this->client = Utilisateur::query()->create([
            'telephone' => '+243810001001',
            'mot_de_passe_hash' =>
                Hash::make('password'),
            'nom' => 'Client',
            'prenom' => 'Test',
            'role' => RoleUtilisateur::Client,
            'statut_compte' => StatutCompte::Actif,
            'telephone_verifie_at' => now(),
        ]);

        $this->prestataire = Utilisateur::query()->create([
            'telephone' => '+243810001002',
            'mot_de_passe_hash' =>
                Hash::make('password'),
            'nom' => 'Prestataire',
            'prenom' => 'Test',
            'role' => RoleUtilisateur::Prestataire,
            'statut_compte' => StatutCompte::Actif,
            'telephone_verifie_at' => now(),
        ]);

        $this->profilPrestataire =
            ProfilPrestataire::query()->create([
                'id_utilisateur' =>
                    $this->prestataire->id_utilisateur,

                'nom_professionnel' =>
                    'Studio FindMe',

                'bio' =>
                    'Studio graphique',

                'competences' =>
                    'Logo et identité visuelle',

                'statut_disponibilite' =>
                    StatutDisponibilite::Disponible,
            ]);

        $categorie = Categorie::query()->create([
            'nom' => 'Graphisme',
            'description' =>
                'Services graphiques à distance',
            'est_active' => true,
        ]);

        $this->annonce = Annonce::query()->create([
            'id_categorie' =>
                $categorie->id_categorie,

            'id_prestataire' =>
                $this->profilPrestataire->id_prestataire,

            'titre' =>
                'Création de logo',

            'description' =>
                'Création de logo professionnel',

            'prix_base' =>
                '100.00',

            'delai_livraison_jours' =>
                5,

            'nombre_revisions' =>
                2,

            'livrables_inclus' =>
                'PNG, SVG et PDF',

            'elements_requis_client' =>
                'Nom de la société et couleurs',

            'statut' =>
                StatutAnnonce::Publiee,

            'published_at' =>
                now(),
        ]);
    }

    public function test_un_client_actif_peut_envoyer_une_demande(): void
    {
        $response = $this
            ->actingAs($this->client)
            ->postJson('/api/v1/commandes', [
                'id_annonce' =>
                    $this->annonce->id_annonce,

                'description_commande' =>
                    'Je souhaite un logo pour mon entreprise de transport.',
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath(
                'commande.id_annonce',
                $this->annonce->id_annonce
            )
            ->assertJsonPath(
                'commande.titre_service',
                'Création de logo'
            )
            ->assertJsonPath(
                'commande.statut',
                'en_attente_reponse'
            )
            ->assertJsonPath(
                'commande.est_offre_personnalisee',
                false
            );

        $this->assertDatabaseHas('commandes', [
            'id_client' =>
                $this->client->id_utilisateur,

            'id_prestataire' =>
                $this->profilPrestataire->id_prestataire,

            'id_annonce' =>
                $this->annonce->id_annonce,

            'statut' =>
                StatutCommande::EnAttenteReponse->value,

            'titre_service' =>
                'Création de logo',

            'montant_total' =>
                '100.00',

            'delai_livraison_jours' =>
                5,

            'nombre_revisions_incluses' =>
                2,

            'livrables_convenus' =>
                'PNG, SVG et PDF',
        ]);
    }

    public function test_les_conditions_de_l_annonce_sont_figees_dans_la_commande(): void
    {
        $response = $this
            ->actingAs($this->client)
            ->postJson('/api/v1/commandes', [
                'id_annonce' =>
                    $this->annonce->id_annonce,

                'description_commande' =>
                    'Je souhaite commander ce service avec les conditions actuelles.',
            ]);

        $response->assertCreated();

        $commandeId =
            $response->json('commande.id_commande');

        $this->annonce->update([
            'prix_base' => '250.00',
            'delai_livraison_jours' => 10,
            'nombre_revisions' => 5,
            'livrables_inclus' => 'PNG uniquement',
        ]);

        $this->assertDatabaseHas('commandes', [
            'id_commande' => $commandeId,
            'montant_total' => '100.00',
            'delai_livraison_jours' => 5,
            'nombre_revisions_incluses' => 2,
            'livrables_convenus' => 'PNG, SVG et PDF',
        ]);
    }

    public function test_une_demande_calcule_correctement_la_commission(): void
    {
        $response = $this
            ->actingAs($this->client)
            ->postJson('/api/v1/commandes', [
                'id_annonce' =>
                    $this->annonce->id_annonce,

                'description_commande' =>
                    'Je souhaite commander ce service numérique.',
            ]);

        $response->assertCreated();

        $this->assertDatabaseHas('commandes', [
            'id_annonce' =>
                $this->annonce->id_annonce,

            'montant_total' =>
                '100.00',

            'taux_commission' =>
                '10.00',

            'montant_commission' =>
                '10.00',

            'montant_prestataire' =>
                '90.00',
        ]);
    }

    public function test_une_demande_genere_une_reference_unique(): void
    {
        $response = $this
            ->actingAs($this->client)
            ->postJson('/api/v1/commandes', [
                'id_annonce' =>
                    $this->annonce->id_annonce,

                'description_commande' =>
                    'Je souhaite commander ce service numérique.',
            ]);

        $response
            ->assertCreated()
            ->assertJsonStructure([
                'commande' => [
                    'reference_commande',
                ],
            ]);

        $reference =
            $response->json(
                'commande.reference_commande'
            );

        $this->assertNotEmpty($reference);

        $this->assertDatabaseHas('commandes', [
            'reference_commande' => $reference,
        ]);
    }

    public function test_une_annonce_brouillon_ne_peut_pas_recevoir_de_demande(): void
    {
        $this->annonce->update([
            'statut' =>
                StatutAnnonce::Brouillon,
        ]);

        $this
            ->actingAs($this->client)
            ->postJson('/api/v1/commandes', [
                'id_annonce' =>
                    $this->annonce->id_annonce,

                'description_commande' =>
                    'Je souhaite commander ce service numérique.',
            ])
            ->assertNotFound();

        $this->assertDatabaseCount(
            'commandes',
            0
        );
    }

    public function test_une_annonce_desactivee_ne_peut_pas_recevoir_de_demande(): void
    {
        $this->annonce->update([
            'statut' =>
                StatutAnnonce::Desactivee,
        ]);

        $this
            ->actingAs($this->client)
            ->postJson('/api/v1/commandes', [
                'id_annonce' =>
                    $this->annonce->id_annonce,

                'description_commande' =>
                    'Je souhaite commander ce service numérique.',
            ])
            ->assertNotFound();

        $this->assertDatabaseCount(
            'commandes',
            0
        );
    }

    public function test_une_annonce_refusee_ne_peut_pas_recevoir_de_demande(): void
    {
        $this->annonce->update([
            'statut' =>
                StatutAnnonce::Refusee,

            'motif_refus' =>
                'Annonce non conforme.',
        ]);

        $this
            ->actingAs($this->client)
            ->postJson('/api/v1/commandes', [
                'id_annonce' =>
                    $this->annonce->id_annonce,

                'description_commande' =>
                    'Je souhaite commander ce service numérique.',
            ])
            ->assertNotFound();
    }

    public function test_un_prestataire_ne_peut_pas_envoyer_de_demande(): void
    {
        $this
            ->actingAs($this->prestataire)
            ->postJson('/api/v1/commandes', [
                'id_annonce' =>
                    $this->annonce->id_annonce,

                'description_commande' =>
                    'Je tente de créer une demande.',
            ])
            ->assertForbidden();
    }

    public function test_un_visiteur_ne_peut_pas_envoyer_de_demande(): void
    {
        $this
            ->postJson('/api/v1/commandes', [
                'id_annonce' =>
                    $this->annonce->id_annonce,

                'description_commande' =>
                    'Je souhaite commander ce service numérique.',
            ])
            ->assertUnauthorized();
    }

    public function test_un_client_suspendu_ne_peut_pas_envoyer_de_demande(): void
    {
        $this->client->update([
            'statut_compte' =>
                StatutCompte::Suspendu,
        ]);

        $this
            ->actingAs($this->client->fresh())
            ->postJson('/api/v1/commandes', [
                'id_annonce' =>
                    $this->annonce->id_annonce,

                'description_commande' =>
                    'Je souhaite commander ce service numérique.',
            ])
            ->assertForbidden();
    }

    public function test_une_annonce_inexistante_retourne_404(): void
    {
        $this
            ->actingAs($this->client)
            ->postJson('/api/v1/commandes', [
                'id_annonce' => 999999,

                'description_commande' =>
                    'Je souhaite commander ce service numérique.',
            ])
            ->assertNotFound();
    }

    public function test_la_description_est_obligatoire(): void
    {
        $this
            ->actingAs($this->client)
            ->postJson('/api/v1/commandes', [
                'id_annonce' =>
                    $this->annonce->id_annonce,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'description_commande',
            ]);
    }

    public function test_le_client_ne_peut_pas_imposer_le_statut_ou_le_prix(): void
    {
        $this
            ->actingAs($this->client)
            ->postJson('/api/v1/commandes', [
                'id_annonce' =>
                    $this->annonce->id_annonce,

                'description_commande' =>
                    'Je souhaite commander ce service numérique.',

                'statut' =>
                    'payee',

                'montant_total' =>
                    1,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'statut',
                'montant_total',
            ]);
    }
}