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

class ListerPaiementsCommandeTest extends TestCase
{
    use RefreshDatabase;

    private Utilisateur $client;

    private Utilisateur $autreClient;

    private Utilisateur $prestataire;

    private Commande $commande;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withHeader('Origin', 'http://localhost:5173');

        $this->client = Utilisateur::query()->create([
            'telephone' => '+243810007001',
            'mot_de_passe_hash' => Hash::make('password'),
            'nom' => 'Client',
            'prenom' => 'Test',
            'role' => RoleUtilisateur::Client,
            'statut_compte' => StatutCompte::Actif,
            'telephone_verifie_at' => now(),
        ]);

        $this->autreClient = Utilisateur::query()->create([
            'telephone' => '+243810007003',
            'mot_de_passe_hash' => Hash::make('password'),
            'nom' => 'Autre',
            'prenom' => 'Client',
            'role' => RoleUtilisateur::Client,
            'statut_compte' => StatutCompte::Actif,
            'telephone_verifie_at' => now(),
        ]);

        $this->prestataire = Utilisateur::query()->create([
            'telephone' => '+243810007002',
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
            'reference_commande' => 'CMD-TEST-E06-001',
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
    }

    public function test_le_client_voit_ses_paiements_du_plus_recent_au_plus_ancien(): void
    {
        Paiement::query()->create([
            'id_commande' => $this->commande->id_commande,
            'reference_interne' => 'PAY-TEST-E06-001',
            'montant' => '100.00',
            'devise' => 'USD',
            'operateur' => 'mpesa',
            'numero_payeur_masque' => '+243*****0001',
            'reference_operateur' => 'sess_e06_ancien',
            'statut' => StatutPaiement::Echoue,
            'idempotency_key' => '88888888-8888-4888-8888-888888888888',
            'initiated_at' => now()->subHour(),
            'failed_at' => now()->subHour(),
        ]);

        Paiement::query()->create([
            'id_commande' => $this->commande->id_commande,
            'reference_interne' => 'PAY-TEST-E06-002',
            'montant' => '100.00',
            'devise' => 'USD',
            'operateur' => 'airtel_money',
            'numero_payeur_masque' => '+243*****0002',
            'reference_operateur' => 'sess_e06_recent',
            'statut' => StatutPaiement::EnAttente,
            'idempotency_key' => '99999999-9999-4999-8999-999999999999',
            'initiated_at' => now(),
        ]);

        $this->actingAs($this->client)
            ->getJson($this->url())
            ->assertOk()
            ->assertJsonPath('meta.total', 2)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.reference_interne', 'PAY-TEST-E06-002')
            ->assertJsonPath('data.0.statut', 'en_attente')
            ->assertJsonPath('data.0.operateur', 'airtel_money')
            ->assertJsonPath('data.1.reference_interne', 'PAY-TEST-E06-001')
            ->assertJsonPath('data.1.statut', 'echoue');
    }

    public function test_une_commande_sans_paiement_renvoie_une_liste_vide(): void
    {
        $this->actingAs($this->client)
            ->getJson($this->url())
            ->assertOk()
            ->assertJsonPath('meta.total', 0)
            ->assertJsonCount(0, 'data');
    }

    public function test_un_visiteur_ne_peut_pas_consulter_les_paiements(): void
    {
        $this->getJson($this->url())->assertUnauthorized();
    }

    public function test_un_autre_client_ne_peut_pas_consulter_ces_paiements(): void
    {
        $this->actingAs($this->autreClient)
            ->getJson($this->url())
            ->assertForbidden();
    }

    public function test_le_prestataire_ne_peut_pas_consulter_les_paiements(): void
    {
        $this->actingAs($this->prestataire)
            ->getJson($this->url())
            ->assertForbidden();
    }

    public function test_une_commande_inconnue_renvoie_404(): void
    {
        $this->actingAs($this->client)
            ->getJson('/api/v1/commandes/999999/paiements')
            ->assertNotFound();
    }


    public function test_un_admin_actif_peut_consulter_les_paiements_d_une_commande(): void
    {
        $admin = Utilisateur::query()->create([
            'telephone' => '+243810007004',
            'mot_de_passe_hash' => Hash::make('password'),
            'nom' => 'Admin',
            'prenom' => 'Test',
            'role' => RoleUtilisateur::Administrateur,
            'statut_compte' => StatutCompte::Actif,
            'telephone_verifie_at' => now(),
        ]);

        Paiement::query()->create([
            'id_commande' => $this->commande->id_commande,
            'reference_interne' => 'PAY-TEST-E07-001',
            'montant' => '100.00',
            'devise' => 'USD',
            'operateur' => 'mpesa',
            'numero_payeur_masque' => '+243*****0001',
            'reference_operateur' => 'sess_e07',
            'statut' => StatutPaiement::EnAttente,
            'idempotency_key' => '12121212-1212-4121-8121-121212121212',
            'initiated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->getJson($this->url())
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.reference_interne', 'PAY-TEST-E07-001')
            ->assertJsonPath('data.0.statut', 'en_attente');
    }

    public function test_un_admin_suspendu_ne_peut_pas_consulter_les_paiements(): void
    {
        $admin = Utilisateur::query()->create([
            'telephone' => '+243810007005',
            'mot_de_passe_hash' => Hash::make('password'),
            'nom' => 'Admin',
            'prenom' => 'Suspendu',
            'role' => RoleUtilisateur::Administrateur,
            'statut_compte' => StatutCompte::Suspendu,
            'telephone_verifie_at' => now(),
        ]);

        $this->actingAs($admin)
            ->getJson($this->url())
            ->assertForbidden();
    }

    private function url(): string
    {
        return '/api/v1/commandes/'.$this->commande->id_commande.'/paiements';
    }
}