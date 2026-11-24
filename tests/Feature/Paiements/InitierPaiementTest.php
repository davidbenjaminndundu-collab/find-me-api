<?php

namespace Tests\Feature\Paiements;

use App\Enums\RoleUtilisateur;
use App\Enums\StatutAnnonce;
use App\Enums\StatutCommande;
use App\Enums\StatutCompte;
use App\Enums\StatutDisponibilite;
use App\Enums\StatutPaiement;
use App\Models\Annonce;
use App\Models\Categorie;
use App\Models\Commande;
use App\Models\Paiement;
use App\Models\ProfilPrestataire;
use App\Models\Utilisateur;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class InitierPaiementTest extends TestCase
{
    use RefreshDatabase;

    private Utilisateur $client;

    private Utilisateur $autreClient;

    private Utilisateur $prestataire;

    private Commande $commande;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.nyole.secret' => 'af_test_sec_fake',
            'services.nyole.base_url' => 'https://app.nyole.com/api',
            'services.nyole.success_url' => 'http://localhost:5173/paiement/succes',
            'services.nyole.cancel_url' => 'http://localhost:5173/paiement/annule',
        ]);

        Http::fake([
            'https://app.nyole.com/api/v1/checkout/sessions' => Http::response([
                'id' => 'sess_test_e01',
                'url' => 'https://app.nyole.com/checkout/sess_test_e01',
                'status' => 'pending',
            ], 201),
        ]);

        $this->withHeader('Origin', 'http://localhost:5173');

        $this->client = Utilisateur::query()->create([
            'telephone' => '+243810005001',
            'mot_de_passe_hash' => Hash::make('password'),
            'nom' => 'Client',
            'prenom' => 'Test',
            'role' => RoleUtilisateur::Client,
            'statut_compte' => StatutCompte::Actif,
            'telephone_verifie_at' => now(),
        ]);

        $this->autreClient = Utilisateur::query()->create([
            'telephone' => '+243810005003',
            'mot_de_passe_hash' => Hash::make('password'),
            'nom' => 'Autre',
            'prenom' => 'Client',
            'role' => RoleUtilisateur::Client,
            'statut_compte' => StatutCompte::Actif,
            'telephone_verifie_at' => now(),
        ]);

        $this->prestataire = Utilisateur::query()->create([
            'telephone' => '+243810005002',
            'mot_de_passe_hash' => Hash::make('password'),
            'nom' => 'Prestataire',
            'prenom' => 'Test',
            'role' => RoleUtilisateur::Prestataire,
            'statut_compte' => StatutCompte::Actif,
            'telephone_verifie_at' => now(),
        ]);

        $profil = ProfilPrestataire::query()->create([
            'id_utilisateur' => $this->prestataire->id_utilisateur,
            'nom_professionnel' => 'Studio FindMe',
            'bio' => 'Studio de création numérique',
            'competences' => 'Design graphique',
            'statut_disponibilite' => StatutDisponibilite::Disponible,
        ]);

        $categorie = Categorie::query()->create([
            'nom' => 'Graphisme',
            'description' => 'Services graphiques',
            'est_active' => true,
        ]);

        $annonce = Annonce::query()->create([
            'id_categorie' => $categorie->id_categorie,
            'id_prestataire' => $profil->id_prestataire,
            'titre' => 'Création de logo',
            'description' => 'Création de logo professionnel',
            'prix_base' => '100.00',
            'delai_livraison_jours' => 5,
            'nombre_revisions' => 2,
            'livrables_inclus' => 'PNG, SVG et PDF',
            'elements_requis_client' => 'Nom de la société et préférences',
            'statut' => StatutAnnonce::Publiee,
            'published_at' => now(),
        ]);

        $this->commande = Commande::query()->create([
            'id_prestataire' => $profil->id_prestataire,
            'id_client' => $this->client->id_utilisateur,
            'id_annonce' => $annonce->id_annonce,
            'reference_commande' => 'CMD-TEST-E01-001',
            'description_commande' => 'Je souhaite un logo professionnel.',
            'titre_service' => 'Création de logo',
            'est_offre_personnalisee' => false,
            'montant_total' => '100.00',
            'taux_commission' => '10.00',
            'montant_commission' => '10.00',
            'montant_prestataire' => '90.00',
            'delai_livraison_jours' => 5,
            'nombre_revisions_incluses' => 2,
            'nombre_revisions_utilisees' => 0,
            'livrables_convenus' => 'PNG, SVG et PDF',
            'statut' => StatutCommande::EnAttentePaiement,
        ]);
    }

    public function test_un_client_peut_initier_le_paiement_apres_acceptation(): void
    {
        $response = $this->actingAs($this->client)->postJson(
            $this->url(),
            $this->payload(),
            ['Idempotency-Key' => '11111111-1111-4111-8111-111111111111']
        );

        $response->assertStatus(202)
            ->assertJsonPath('url_paiement', 'https://app.nyole.com/checkout/sess_test_e01')
            ->assertJsonPath('montant', '100.00')
            ->assertJsonPath('devise', 'USD')
            ->assertJsonPath('operateur', 'mpesa')
            ->assertJsonPath('numero_payeur_masque', '+243*****0050')
            ->assertJsonPath('statut', 'en_attente')
            ->assertJsonMissingPath('message')
            ->assertJsonMissingPath('paiement');

        $this->assertDatabaseHas('paiements', [
            'id_commande' => $this->commande->id_commande,
            'reference_operateur' => 'sess_test_e01',
            'statut' => StatutPaiement::EnAttente->value,
            'devise' => 'USD',
        ]);

        $this->assertSame(
            StatutCommande::PaiementEnCours,
            $this->commande->fresh()->statut
        );

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://app.nyole.com/api/v1/checkout/sessions'
                && $request['amount'] === 10000
                && $request['currency'] === 'USD'
                && $request['customer_phone'] === '+243810000050';
        });
    }

    public function test_la_meme_cle_idempotence_renvoie_le_meme_paiement(): void
    {
        $headers = ['Idempotency-Key' => '22222222-2222-4222-8222-222222222222'];

        $premiere = $this->actingAs($this->client)
            ->postJson($this->url(), $this->payload(), $headers)
            ->assertStatus(202);

        $this->actingAs($this->client)
            ->postJson($this->url(), $this->payload(), $headers)
            ->assertStatus(202)
            ->assertJsonPath(
                'id_paiement',
                $premiere->json('id_paiement')
            )
            ->assertJsonPath(
                'reference_interne',
                $premiere->json('reference_interne')
            )
            ->assertJsonPath(
                'url_paiement',
                $premiere->json('url_paiement')
            );

        $this->assertDatabaseCount('paiements', 1);
        Http::assertSentCount(1);
    }

    public function test_une_autre_cle_ne_ouvre_pas_une_deuxieme_session_active(): void
    {
        $this->actingAs($this->client)->postJson(
            $this->url(),
            $this->payload(),
            ['Idempotency-Key' => 'cccccccc-cccc-4ccc-8ccc-cccccccccccc']
        )->assertStatus(202);

        $this->actingAs($this->client)->postJson(
            $this->url(),
            $this->payload(),
            ['Idempotency-Key' => 'dddddddd-dddd-4ddd-8ddd-dddddddddddd']
        )->assertStatus(409);

        $this->assertDatabaseCount('paiements', 1);
        Http::assertSentCount(1);
    }

    public function test_une_tentative_creee_est_reprise_apres_une_coupure(): void
    {
        $paiement = Paiement::query()->create([
            'id_commande' => $this->commande->id_commande,
            'reference_interne' => 'PAY-TEST-RESUME-001',
            'montant' => '100.00',
            'devise' => 'USD',
            'operateur' => 'mpesa',
            'numero_payeur_masque' => '+243*****0050',
            'reference_operateur' => null,
            'statut' => StatutPaiement::Cree,
            'idempotency_key' => 'eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee',
            'initiated_at' => now(),
        ]);

        $this->commande->update([
            'statut' => StatutCommande::PaiementEnCours,
        ]);

        $this->actingAs($this->client)->postJson(
            $this->url(),
            $this->payload(),
            ['Idempotency-Key' => 'eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee']
        )->assertStatus(202)
            ->assertJsonPath('id_paiement', $paiement->id_paiement)
            ->assertJsonPath('reference_interne', 'PAY-TEST-RESUME-001')
            ->assertJsonPath('statut', 'en_attente')
            ->assertJsonPath('url_paiement', 'https://app.nyole.com/checkout/sess_test_e01');

        $this->assertDatabaseCount('paiements', 1);
        $this->assertDatabaseHas('paiements', [
            'id_paiement' => $paiement->id_paiement,
            'reference_operateur' => 'sess_test_e01',
            'statut' => StatutPaiement::EnAttente->value,
        ]);
        Http::assertSentCount(1);
    }

    public function test_un_visiteur_ne_peut_pas_payer(): void
    {
        $this->postJson(
            $this->url(),
            $this->payload(),
            ['Idempotency-Key' => '33333333-3333-4333-8333-333333333333']
        )->assertUnauthorized();
    }

    public function test_un_autre_client_ne_peut_pas_payer_cette_commande(): void
    {
        $this->actingAs($this->autreClient)->postJson(
            $this->url(),
            $this->payload(),
            ['Idempotency-Key' => '44444444-4444-4444-8444-444444444444']
        )->assertNotFound();
    }

    public function test_une_commande_non_acceptee_ne_peut_pas_etre_payee(): void
    {
        $this->commande->update([
            'statut' => StatutCommande::EnAttenteReponse,
        ]);

        $this->actingAs($this->client)->postJson(
            $this->url(),
            $this->payload(),
            ['Idempotency-Key' => '55555555-5555-4555-8555-555555555555']
        )->assertStatus(409);

        $this->assertDatabaseCount('paiements', 0);
    }

    public function test_un_operateur_invalide_est_refuse(): void
    {
        $this->actingAs($this->client)->postJson(
            $this->url(),
            [
                'operateur' => 'wave',
                'numero_payeur' => '+243810000050',
            ],
            ['Idempotency-Key' => '66666666-6666-4666-8666-666666666666']
        )->assertUnprocessable()
            ->assertJsonValidationErrors(['operateur']);
    }

    public function test_la_cle_idempotence_est_obligatoire(): void
    {
        $this->actingAs($this->client)
            ->postJson($this->url(), $this->payload())
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['cle_idempotence']);
    }

    public function test_une_commande_inconnue_renvoie_404(): void
    {
        $this->actingAs($this->client)->postJson(
            '/api/v1/commandes/999999/paiements',
            $this->payload(),
            ['Idempotency-Key' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa']
        )->assertNotFound();
    }

    public function test_un_autre_client_ne_peut_pas_rejouer_une_cle_existante(): void
    {
        Paiement::query()->create([
            'id_commande' => $this->commande->id_commande,
            'reference_interne' => 'PAY-TEST-FIX-OWNER',
            'montant' => '100.00',
            'devise' => 'USD',
            'operateur' => 'mpesa',
            'numero_payeur_masque' => '+243*****0050',
            'reference_operateur' => 'sess_fix_owner',
            'statut' => StatutPaiement::EnAttente,
            'idempotency_key' => 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb',
            'initiated_at' => now(),
        ]);

        $this->commande->update([
            'statut' => StatutCommande::PaiementEnCours,
        ]);

        $this->actingAs($this->autreClient)->postJson(
            $this->url(),
            $this->payload(),
            ['Idempotency-Key' => 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb']
        )->assertNotFound();
    }

    private function url(): string
    {
        return '/api/v1/commandes/'.$this->commande->id_commande.'/paiements';
    }

    private function payload(): array
    {
        return [
            'operateur' => 'mpesa',
            'numero_payeur' => '+243810000050',
        ];
    }
}