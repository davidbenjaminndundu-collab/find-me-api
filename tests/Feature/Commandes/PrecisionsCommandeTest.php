<?php

namespace Tests\Feature\Commandes;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Enums\RoleUtilisateur;
use App\Enums\StatutAnnonce;
use App\Enums\StatutCommande;
use App\Enums\StatutCompte;
use App\Enums\StatutDisponibilite;
use App\Models\Annonce;
use App\Models\Categorie;
use App\Models\Commande;
use App\Models\ProfilPrestataire;
use App\Models\Utilisateur;
use App\Models\PrecisionCommande;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PrecisionsCommandeTest extends TestCase
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

        $this->client = Utilisateur::query()->create([
            'telephone' => '+243810004001',
            'mot_de_passe_hash' => Hash::make('password'),
            'nom' => 'Client',
            'prenom' => 'Test',
            'role' => RoleUtilisateur::Client,
            'statut_compte' => StatutCompte::Actif,
            'telephone_verifie_at' => now(),
        ]);

        $this->prestataire = Utilisateur::query()->create([
            'telephone' => '+243810004002',
            'mot_de_passe_hash' => Hash::make('password'),
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
                    'Studio de création numérique',

                'competences' =>
                    'Design graphique',

                'statut_disponibilite' =>
                    StatutDisponibilite::Disponible,
            ]);

        $categorie = Categorie::query()->create([
            'nom' => 'Graphisme',
            'description' => 'Services graphiques',
            'est_active' => true,
        ]);

        $annonce = Annonce::query()->create([
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
                'Nom de la société et préférences',

            'statut' =>
                StatutAnnonce::Publiee,

            'published_at' =>
                now(),
        ]);

        $this->commande = Commande::query()->create([
            'id_prestataire' =>
                $this->profilPrestataire->id_prestataire,

            'id_client' =>
                $this->client->id_utilisateur,

            'id_annonce' =>
                $annonce->id_annonce,

            'reference_commande' =>
                'CMD-TEST-D04-001',

            'description_commande' =>
                'Je souhaite un logo professionnel.',

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
        ]);
    }

    public function test_le_prestataire_peut_demander_des_precisions(): void
    {
        $response = $this
            ->actingAs($this->prestataire)
            ->postJson(
                "/api/v1/commandes/{$this->commande->id_commande}/actions",
                [
                    'type_action' =>
                        'demander_precisions',

                    'motif' =>
                        'Pouvez-vous préciser les dimensions souhaitées ?',
                ]
            );

        $response
            ->assertStatus(202)
            ->assertJsonPath(
                'commande.statut',
                'informations_demandees'
            );

        $this->assertDatabaseHas(
            'precisions_commandes',
            [
                'id_commande' =>
                    $this->commande->id_commande,

                'demande' =>
                    'Pouvez-vous préciser les dimensions souhaitées ?',
            ]
        );
    }

    public function test_le_client_peut_repondre_a_la_demande_de_precisions(): void
    {
        // Arrange : on prépare directement l'état attendu
        $this->commande->update([
            'statut' => StatutCommande::InformationsDemandees,
        ]);

        PrecisionCommande::query()->create([
            'id_commande' => $this->commande->id_commande,
            'demande' => 'Quelles dimensions souhaitez-vous ?',
            'demande_at' => now(),
        ]);

        // Act : seul le comportement du client est testé ici
        $response = $this
            ->actingAs($this->client, 'web')
            ->postJson(
                "/api/v1/commandes/{$this->commande->id_commande}/precisions",
                [
                    'description' =>
                        'Je souhaite un format 1080x1080 pixels.',
                ]
            );

        // Assert
        $response
            ->assertOk()
            ->assertJsonPath(
                'commande.statut',
                'en_attente_reponse'
            );

        $this->assertDatabaseHas(
            'precisions_commandes',
            [
                'id_commande' =>
                    $this->commande->id_commande,

                'demande' =>
                    'Quelles dimensions souhaitez-vous ?',

                'reponse' =>
                    'Je souhaite un format 1080x1080 pixels.',
            ]
        );

        $this->assertSame(
            StatutCommande::EnAttenteReponse,
            $this->commande->fresh()->statut
        );
    }
}
