<?php

namespace Tests\Feature\Commandes;

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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RefusDemandeTest extends TestCase
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

    public function test_le_prestataire_peut_refuser_sa_demande(): void
    {
        $response = $this
            ->actingAs($this->prestataire)
            ->postJson(
                "/api/v1/commandes/{$this->commande->id_commande}/actions",
                [
                    'type_action' => 'refuser',
                    'motif' =>
                        'Je ne suis pas disponible pour réaliser cette prestation.',
                ]
            );

        $response
            ->assertStatus(202)
            ->assertJsonPath(
                'commande.statut',
                'refusee'
            );

        $commande = $this->commande->fresh();

        $this->assertSame(
            StatutCommande::Refusee,
            $commande->statut
        );

        $this->assertSame(
            'Je ne suis pas disponible pour réaliser cette prestation.',
            $commande->motif_refus
        );

        $this->assertNotNull(
            $commande->date_fin
        );
    }

    public function test_le_motif_est_obligatoire_pour_refuser(): void
    {
        $this
            ->actingAs($this->prestataire)
            ->postJson(
                "/api/v1/commandes/{$this->commande->id_commande}/actions",
                [
                    'type_action' => 'refuser',
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'motif',
            ]);
    }

    public function test_un_client_ne_peut_pas_refuser_une_demande(): void
    {
        $this
            ->actingAs($this->client)
            ->postJson(
                "/api/v1/commandes/{$this->commande->id_commande}/actions",
                [
                    'type_action' => 'refuser',
                    'motif' => 'Tentative interdite.',
                ]
            )
            ->assertForbidden();
    }

    public function test_un_visiteur_ne_peut_pas_refuser_une_demande(): void
    {
        $this
            ->postJson(
                "/api/v1/commandes/{$this->commande->id_commande}/actions",
                [
                    'type_action' => 'refuser',
                    'motif' => 'Tentative interdite.',
                ]
            )
            ->assertUnauthorized();
    }

    public function test_un_prestataire_suspendu_ne_peut_pas_refuser(): void
    {
        $this->prestataire->update([
            'statut_compte' =>
                StatutCompte::Suspendu,
        ]);

        $this
            ->actingAs($this->prestataire->fresh())
            ->postJson(
                "/api/v1/commandes/{$this->commande->id_commande}/actions",
                [
                    'type_action' => 'refuser',
                    'motif' => 'Je refuse.',
                ]
            )
            ->assertForbidden();
    }

    public function test_une_demande_deja_refusee_ne_peut_pas_etre_refusee_a_nouveau(): void
    {
        $this->commande->update([
            'statut' =>
                StatutCommande::Refusee,

            'motif_refus' =>
                'Premier refus.',

            'date_fin' =>
                now(),
        ]);

        $this
            ->actingAs($this->prestataire)
            ->postJson(
                "/api/v1/commandes/{$this->commande->id_commande}/actions",
                [
                    'type_action' => 'refuser',
                    'motif' => 'Deuxième refus.',
                ]
            )
            ->assertStatus(409);
    }

    public function test_une_demande_acceptee_ne_peut_plus_etre_refusee(): void
    {
        $this->commande->update([
            'statut' =>
                StatutCommande::EnAttentePaiement,

            'date_acceptation' =>
                now(),
        ]);

        $this
            ->actingAs($this->prestataire)
            ->postJson(
                "/api/v1/commandes/{$this->commande->id_commande}/actions",
                [
                    'type_action' => 'refuser',
                    'motif' => 'Refus tardif.',
                ]
            )
            ->assertStatus(409);
    }

    public function test_une_commande_inexistante_retourne_404(): void
    {
        $this
            ->actingAs($this->prestataire)
            ->postJson(
                '/api/v1/commandes/999999/actions',
                [
                    'type_action' => 'refuser',
                    'motif' =>
                        'Cette commande n’existe pas.',
                ]
            )
            ->assertNotFound();
    }
}