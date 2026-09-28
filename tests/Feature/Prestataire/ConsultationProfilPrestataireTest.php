<?php

namespace Tests\Feature\Prestataire;

use App\Enums\RoleUtilisateur;
use App\Enums\StatutCompte;
use App\Enums\StatutDisponibilite;
use App\Models\ProfilPrestataire;
use App\Models\Utilisateur;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ConsultationProfilPrestataireTest extends TestCase
{
    use RefreshDatabase;

    private Utilisateur $prestataire;

    private ProfilPrestataire $profil;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prestataire = Utilisateur::query()->create([
            'telephone' => '+243812345678',
            'mot_de_passe_hash' => Hash::make('Password123!'),
            'nom' => 'Mbambi',
            'prenom' => 'Benjamin',
            'email' => 'benjamin@example.com',
            'role' => RoleUtilisateur::Prestataire,
            'statut_compte' => StatutCompte::Actif,
            'telephone_verifie_at' => now(),
        ]);

        $this->profil = ProfilPrestataire::query()->create([
            'id_utilisateur' =>
                $this->prestataire->id_utilisateur,

            'nom_professionnel' =>
                'Kingdom Design',

            'bio' =>
                'Graphiste spécialisé dans les identités visuelles.',

            'competences' =>
                'Figma, Photoshop, Illustrator',

            'portfolio_url' =>
                'https://portfolio.example.com',

            'statut_disponibilite' =>
                StatutDisponibilite::Disponible,
        ]);
    }

    public function test_le_profil_public_ne_revele_pas_les_donnees_sensibles(): void
    {
        $response = $this->getJson(
            "/api/v1/prestataires/{$this->profil->id_prestataire}"
        );

        $response->assertOk();

        $response
            ->assertJsonMissingPath(
                'profil.utilisateur.telephone'
            )
            ->assertJsonMissingPath(
                'profil.utilisateur.email'
            )
            ->assertJsonMissingPath(
                'profil.utilisateur.mot_de_passe_hash'
            )
            ->assertJsonMissingPath(
                'profil.utilisateur.statut_compte'
            );
    }

    public function test_un_visiteur_peut_consulter_un_profil_prestataire_public(): void
    {
        $response = $this->getJson(
            "/api/v1/prestataires/{$this->profil->id_prestataire}"
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'profil.id_prestataire',
                $this->profil->id_prestataire
            )
            ->assertJsonPath(
                'profil.nom_professionnel',
                'Kingdom Design'
            )
            ->assertJsonPath(
                'profil.bio',
                'Graphiste spécialisé dans les identités visuelles.'
            )
            ->assertJsonPath(
                'profil.statut_disponibilite',
                'disponible'
            )
            ->assertJsonPath(
                'profil.utilisateur.nom',
                'Mbambi'
            )
            ->assertJsonPath(
                'profil.utilisateur.prenom',
                'Benjamin'
            );
    }

    public function test_un_profil_inexistant_retourne_404(): void
    {
        $response = $this->getJson(
            '/api/v1/prestataires/999999'
        );

        $response->assertNotFound();
    }

    public function test_le_profil_d_un_prestataire_suspendu_n_est_pas_public(): void
    {
        $this->prestataire->update([
            'statut_compte' => StatutCompte::Suspendu,
        ]);

        $response = $this->getJson(
            "/api/v1/prestataires/{$this->profil->id_prestataire}"
        );

        $response->assertNotFound();
    }

    public function test_un_profil_associe_a_un_client_n_est_pas_public(): void
    {
        $this->prestataire->update([
            'role' => RoleUtilisateur::Client,
        ]);

        $response = $this->getJson(
            "/api/v1/prestataires/{$this->profil->id_prestataire}"
        );

        $response->assertNotFound();
    }
}