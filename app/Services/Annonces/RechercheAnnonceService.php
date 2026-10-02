<?php

namespace App\Services\Annonces;

use App\Enums\StatutAnnonce;
use App\Models\Annonce;
use Illuminate\Support\Collection;

class RechercheAnnonceService
{
    /**
     * @return array{data: Collection<int, Annonce>, meta: array{limit: int, offset: int, total: int}}
     */
    public function rechercher(
        ?string $q,
        ?int $idCategorie,
        ?int $limit,
        ?int $offset
    ): array {
        $limit = $limit ?? 20;
        $offset = $offset ?? 0;

        $query = Annonce::query()
            ->with([
                'categorie',
                'prestataire',
            ])
            ->where('statut', StatutAnnonce::Publiee);

        if ($q !== null && trim($q) !== '') {
            $term = '%' . trim($q) . '%';

            $query->where(function ($builder) use ($term) {
                $builder
                    ->where('titre', 'ilike', $term)
                    ->orWhere('description', 'ilike', $term)
                    ->orWhere('mots_cles', 'ilike', $term);
            });
        }

        if ($idCategorie !== null) {
            $query->where('id_categorie', $idCategorie);
        }

        $total = (clone $query)->count();

        $annonces = $query
            ->orderByDesc('published_at')
            ->offset($offset)
            ->limit($limit)
            ->get();

        return [
            'data' => $annonces,
            'meta' => [
                'limit' => $limit,
                'offset' => $offset,
                'total' => $total,
            ],
        ];
    }
}