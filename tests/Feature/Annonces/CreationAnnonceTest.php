<?php

namespace Tests\Feature\Annonces;

use App\Enums\RoleUtilisateur;
use App\Enums\StatutAnnonce;
use App\Enums\StatutCompte;
use App\Enums\StatutDisponibilite;
use App\Models\Categorie;
use App\Models\ProfilPrestataire;
use App\Models\Utilisateur;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreationAnnonceTest extends TestCase
{
    use RefreshDatabase;

    private Utilisateur $prestataire;

    private Categorie $categorie;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withHeader('Origin', 'http://localhost:8000');

        $this->prestataire = Utilisateur::query()->create([
            'telephone' => '+243810000060',
            'mot_de_passe_hash' => Hash::make('password'),
            'nom' => 'Kalilwa',
            'prenom' => 'Jean',
            'role' => RoleUtilisateur::Prestataire,
            'statut_compte' => StatutCompte::Actif,
            'telephone_verifie_at' => now(),
        ]);

        ProfilPrestataire::query()->create([
            'id_utilisateur' => $this->prestataire->id_utilisateur,
            'nom_professionnel' => 'Studio Kalilwa',
            'bio' => 'Graphiste numerique',
            'competences' => 'Logo, identite visuelle',
            'statut_disponibilite' => StatutDisponibilite::Disponible,
        ]);

        $this->categorie = Categorie::query()->create([
            'nom' => 'Graphisme',
            'description' => 'Services graphiques a distance',
            'est_active' => true,
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'id_categorie' => $this->categorie->id_categorie,
            'titre' => 'Creation de logo',
            'description' => 'Logo professionnel pour entreprise',
            'prix_base' => '50.00',
            'delai_livraison_jours' => 5,
            'nombre_revisions' => 2,
            'livrables_inclus' => 'Fichiers PNG et SVG',
            'elements_requis_client' => 'Nom de la marque et couleurs',
        ], $overrides);
    }

    public function test_un_prestataire_actif_peut_creer_une_annonce(): void
    {
        $response = $this->actingAs($this->prestataire)
            ->postJson('/api/v1/annonces', $this->payload());

        $response->assertCreated()
            ->assertJsonPath('annonce.statut', 'brouillon')
            ->assertJsonPath('annonce.titre', 'Creation de logo');

        $this->assertDatabaseHas('annonces', [
            'titre' => 'Creation de logo',
            'statut' => StatutAnnonce::Brouillon->value,
        ]);
    }

    public function test_un_visiteur_ne_peut_pas_creer_une_annonce(): void
    {
        $this->postJson('/api/v1/annonces', $this->payload())
            ->assertUnauthorized();
    }

    public function test_un_client_ne_peut_pas_creer_une_annonce(): void
    {
        $client = Utilisateur::query()->create([
            'telephone' => '+243810000061',
            'mot_de_passe_hash' => Hash::make('password'),
            'nom' => 'Client',
            'prenom' => 'Test',
            'role' => RoleUtilisateur::Client,
            'statut_compte' => StatutCompte::Actif,
            'telephone_verifie_at' => now(),
        ]);

        $this->actingAs($client)
            ->postJson('/api/v1/annonces', $this->payload())
            ->assertForbidden();
    }

    public function test_un_titre_manquant_est_refuse(): void
    {
        $this->actingAs($this->prestataire)
            ->postJson('/api/v1/annonces', $this->payload([
                'titre' => '',
            ]))
            ->assertUnprocessable();
    }

    public function test_une_categorie_inactive_est_refusee(): void
    {
        $inactive = Categorie::query()->create([
            'nom' => 'Categorie inactive',
            'est_active' => false,
        ]);

        $this->actingAs($this->prestataire)
            ->postJson('/api/v1/annonces', $this->payload([
                'id_categorie' => $inactive->id_categorie,
            ]))
            ->assertUnprocessable();
    }

    public function test_le_statut_envoye_par_le_client_est_refuse(): void
    {
        $this->actingAs($this->prestataire)
            ->postJson('/api/v1/annonces', $this->payload([
                'statut' => 'publiee',
            ]))
            ->assertUnprocessable();

        $this->assertDatabaseMissing('annonces', [
            'titre' => 'Creation de logo',
        ]);
    }
}