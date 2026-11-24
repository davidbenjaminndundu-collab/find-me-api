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
use Tests\TestCase;

class RecevoirWebhookPaiementTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'af_test_sec_fake';

    private Commande $commande;

    private Paiement $paiement;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.nyole.secret' => self::SECRET]);

        $client = Utilisateur::query()->create([
            'telephone' => '+243810006001',
            'mot_de_passe_hash' => Hash::make('password'),
            'nom' => 'Client',
            'prenom' => 'Test',
            'role' => RoleUtilisateur::Client,
            'statut_compte' => StatutCompte::Actif,
            'telephone_verifie_at' => now(),
        ]);

        $prestataire = Utilisateur::query()->create([
            'telephone' => '+243810006002',
            'mot_de_passe_hash' => Hash::make('password'),
            'nom' => 'Prestataire',
            'prenom' => 'Test',
            'role' => RoleUtilisateur::Prestataire,
            'statut_compte' => StatutCompte::Actif,
            'telephone_verifie_at' => now(),
        ]);

        $profil = ProfilPrestataire::query()->create([
            'id_utilisateur' => $prestataire->id_utilisateur,
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
            'id_client' => $client->id_utilisateur,
            'id_annonce' => $annonce->id_annonce,
            'reference_commande' => 'CMD-TEST-E03-001',
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
            'statut' => StatutCommande::PaiementEnCours,
        ]);

        $this->paiement = Paiement::query()->create([
            'id_commande' => $this->commande->id_commande,
            'reference_interne' => 'PAY-TEST-E03-001',
            'montant' => '100.00',
            'devise' => 'USD',
            'operateur' => 'mpesa',
            'numero_payeur_masque' => '+243*****0001',
            'reference_operateur' => 'sess_test_e03',
            'statut' => StatutPaiement::EnAttente,
            'idempotency_key' => '77777777-7777-4777-8777-777777777777',
            'initiated_at' => now(),
        ]);
    }

    public function test_un_webhook_signe_confirme_le_paiement(): void
    {
        $this->envoyer($this->payload())
            ->assertOk()
            ->assertJsonPath('message', 'Paiement confirmé.');

        $this->assertSame(StatutPaiement::Confirme, $this->paiement->fresh()->statut);
        $this->assertNotNull($this->paiement->fresh()->confirmed_at);
        $this->assertSame(StatutCommande::Payee, $this->commande->fresh()->statut);
        $this->assertNotNull($this->commande->fresh()->date_paiement);

        $this->assertDatabaseHas('callbacks_paiement', [
            'id_paiement' => $this->paiement->id_paiement,
            'operateur' => 'nyole',
            'identifiant_evenement' => 'sess_test_e03:payment.completed',
            'signature_valide' => true,
            'resultat_traitement' => 'traite',
        ]);
    }

    public function test_une_signature_invalide_est_refusee(): void
    {
        $corps = json_encode($this->payload(), JSON_THROW_ON_ERROR);

        $this->call(
            'POST',
            '/api/v1/webhooks/paiements',
            [],
            [],
            [],
            $this->transformHeadersToServerVars([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'X-Afriflow-Timestamp' => (string) time(),
                'X-Afriflow-Signature' => 'v1=signature-fausse',
            ]),
            $corps
        )->assertUnauthorized();

        $this->assertSame(StatutPaiement::EnAttente, $this->paiement->fresh()->statut);
        $this->assertSame(StatutCommande::PaiementEnCours, $this->commande->fresh()->statut);
    }

    public function test_un_horodatage_trop_ancien_est_refuse(): void
    {
        $this->envoyer($this->payload(), time() - 301)
            ->assertUnauthorized();

        $this->assertSame(StatutCommande::PaiementEnCours, $this->commande->fresh()->statut);
    }

    public function test_un_autre_evenement_est_ignore(): void
    {
        $this->envoyer($this->payload('payment.failed'))
            ->assertOk()
            ->assertJsonPath('message', 'Événement ignoré.');

        $this->assertSame(StatutPaiement::EnAttente, $this->paiement->fresh()->statut);
        $this->assertDatabaseCount('callbacks_paiement', 0);
    }

    public function test_une_session_inconnue_demande_un_nouvel_essai(): void
    {
        $payload = $this->payload();
        $payload['data']['id'] = 'sess_inconnue';

        $this->envoyer($payload)
            ->assertStatus(503)
            ->assertJsonPath('message', 'Paiement introuvable, réessayez plus tard.');

        $this->assertSame(StatutCommande::PaiementEnCours, $this->commande->fresh()->statut);
        $this->assertDatabaseCount('callbacks_paiement', 0);
    }

    public function test_un_montant_different_ne_confirme_pas_la_commande(): void
    {
        $payload = $this->payload();
        $payload['data']['amount'] = 1;

        $this->envoyer($payload)
            ->assertOk()
            ->assertJsonPath('message', 'Événement reçu sans changement de statut.');

        $this->assertSame(StatutPaiement::EnAttente, $this->paiement->fresh()->statut);
        $this->assertSame(StatutCommande::PaiementEnCours, $this->commande->fresh()->statut);
        $this->assertDatabaseHas('callbacks_paiement', [
            'resultat_traitement' => 'incoherent',
        ]);
    }


    public function test_le_meme_evenement_ne_confirme_pas_deux_fois(): void
    {
        $this->envoyer($this->payload())
            ->assertOk()
            ->assertJsonPath('message', 'Paiement confirmé.');

        $confirmedAt = $this->paiement->fresh()->confirmed_at;
        $datePaiement = $this->commande->fresh()->date_paiement;

        $this->travel(5)->seconds();

        $this->envoyer($this->payload())
            ->assertOk()
            ->assertJsonPath('message', 'Événement déjà traité.');

        $this->assertDatabaseCount('callbacks_paiement', 1);
        $this->assertTrue($confirmedAt->equalTo($this->paiement->fresh()->confirmed_at));
        $this->assertTrue($datePaiement->equalTo($this->commande->fresh()->date_paiement));
        $this->assertSame(StatutPaiement::Confirme, $this->paiement->fresh()->statut);
        $this->assertSame(StatutCommande::Payee, $this->commande->fresh()->statut);
    }

    private function payload(string $event = 'payment.completed'): array
    {
        return [
            'event' => $event,
            'livemode' => false,
            'data' => [
                'id' => 'sess_test_e03',
                'order_id' => 'CS-TEST-E03',
                'status' => 'SUCCESS',
                'amount' => 10000,
                'currency' => 'USD',
                'provider_reference' => 'pd_test_e03',
            ],
        ];
    }

    private function envoyer(array $payload, ?int $horodatage = null)
    {
        $corps = json_encode($payload, JSON_THROW_ON_ERROR);
        $horodatage ??= time();
        $signature = hash_hmac('sha256', $horodatage.'.'.$corps, self::SECRET);

        return $this->call(
            'POST',
            '/api/v1/webhooks/paiements',
            [],
            [],
            [],
            $this->transformHeadersToServerVars([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'X-Afriflow-Timestamp' => (string) $horodatage,
                'X-Afriflow-Signature' => 'v1='.$signature,
            ]),
            $corps
        );
    }
}