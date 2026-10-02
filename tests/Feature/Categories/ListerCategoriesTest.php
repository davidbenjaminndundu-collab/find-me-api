<?php

namespace Tests\Feature\Categories;

use App\Models\Categorie;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListerCategoriesTest extends TestCase
{
    use RefreshDatabase;

    private Categorie $categorieActive;

    private Categorie $categorieInactive;

    protected function setUp(): void
    {
        parent::setUp();

        $this->categorieActive = Categorie::query()->create([
            'nom' => 'Graphisme',
            'description' => 'Services graphiques',
            'icone' => null,
            'est_active' => true,
        ]);

        $this->categorieInactive = Categorie::query()->create([
            'nom' => 'Ancienne catégorie',
            'description' => 'Plus utilisée',
            'icone' => null,
            'est_active' => false,
        ]);
    }

    public function test_un_visiteur_peut_lister_les_categories_sans_authentification(): void
    {
        $this->assertGuest();

        $response = $this->getJson('/api/v1/categories');

        $response
            ->assertOk()
            ->assertJsonPath('meta.total', 2)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.nom', 'Ancienne catégorie')
            ->assertJsonPath('data.1.nom', 'Graphisme');
    }

    public function test_le_filtre_est_active_retourne_uniquement_les_categories_actives(): void
    {
        $response = $this->getJson('/api/v1/categories?est_active=1');

        $response
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath(
                'data.0.id_categorie',
                $this->categorieActive->id_categorie
            )
            ->assertJsonPath('data.0.est_active', true);
    }

    public function test_la_pagination_est_appliquee(): void
    {
        $this->getJson('/api/v1/categories?limit=1&offset=0')
            ->assertOk()
            ->assertJsonPath('meta.limit', 1)
            ->assertJsonPath('meta.offset', 0)
            ->assertJsonPath('meta.total', 2)
            ->assertJsonCount(1, 'data');
    }
}